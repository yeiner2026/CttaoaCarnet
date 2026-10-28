<?php
// Mantenimiento automático (sin cron): limpieza de historial, copia de seguridad diaria y alertas.

function mantenimiento_si_toca() {
    static $hecho = false;
    if ($hecho) return; $hecho = true;
    procesar_fichas_vencidas();
    try {
        $cfg = config_db();
        $ult = $cfg['ultima_limpieza'] ? strtotime($cfg['ultima_limpieza']) : 0;
        if (time() - $ult >= 3600) {                       // una vez por hora como máximo
            q('UPDATE configuracion SET ultima_limpieza=? WHERE id=1', [now()]);
            limpiar_datos_antiguos($cfg);
        }
        if (copia_pendiente($cfg)) crear_copia();
    } catch (Throwable $e) {
        @file_put_contents(APP_ROOT . '/storage/error.log', '[' . date('c') . '] mantenimiento: ' . $e->getMessage() . "\n", FILE_APPEND);
    }
}

function limpiar_datos_antiguos($cfg) {
    q('DELETE FROM restablecer_pin WHERE creado < ?', [date('Y-m-d H:i:s', time() - 7 * 86400)]);
    $dias = max(1, (int)$cfg['historial_dias']);
    q('DELETE FROM historial WHERE fecha < ?', [date('Y-m-d H:i:s', time() - $dias * 86400)]);
    $tope = (int)cfg('historial_max_filas', 20000);                       // además, un tope de filas
    if ((int)val('SELECT COUNT(*) FROM historial') > $tope) {
        $corte = val('SELECT id FROM historial ORDER BY id DESC LIMIT 1 OFFSET ' . ($tope - 1));
        if ($corte) q('DELETE FROM historial WHERE id < ?', [$corte]);
    }
    // Registros que figuran como «registrado» pero les falta un dato hoy obligatorio (RH o fecha de expedición) pasan a «pendiente»,
    // para que aparezcan en el filtro «Datos incompletos» y en los indicadores.
    q("UPDATE personas SET estado_registro='pendiente' WHERE estado_registro='registrado' AND (rh='' OR fecha_expedicion IS NULL)");
    q('DELETE FROM eventos_seguridad WHERE fecha < ?', [date('Y-m-d H:i:s', time() - 30 * 86400)]);
    q('DELETE FROM accesos_titular WHERE fecha < ?', [date('Y-m-d H:i:s', time() - 90 * 86400)]);
    q('DELETE FROM eventos_verificacion WHERE fecha < ?', [date('Y-m-d H:i:s', time() - 90 * 86400)]);
    q('DELETE FROM consultas_verificacion WHERE fecha < ?', [date('Y-m-d H:i:s', time() - max(1, (int)cfg('consultas_dias', 90)) * 86400)]);   // por defecto 90 días (clave consultas_dias en config.php)
    q('DELETE FROM intentos_acceso WHERE ultimo < ? AND (bloqueado_hasta IS NULL OR bloqueado_hasta < ?)', [date('Y-m-d H:i:s', time() - 2 * 86400), now()]);   // los contadores sin bloqueo caducan a las 24 h
}

// ---------------- copia de seguridad ----------------
function ruta_copia() { return APP_ROOT . '/storage/backups/copia_cttaoa.sql'; }
function copia_info() {
    $r = ruta_copia();
    return is_file($r) ? ['existe' => true, 'fecha' => date('Y-m-d H:i:s', filemtime($r)), 'bytes' => filesize($r)] : ['existe' => false, 'fecha' => null, 'bytes' => 0];
}
function copia_pendiente($cfg) {
    $horas = max(1, (int)($cfg['copia_horas'] ?? 24));
    $ult = !empty($cfg['ultima_copia']) ? strtotime($cfg['ultima_copia']) : 0;
    return time() - $ult >= $horas * 3600;
}
/** Vuelca TODA la base de datos a un .sql restaurable (sin mysqldump). Reemplaza la copia anterior. */
function crear_copia() {
    $dir = APP_ROOT . '/storage/backups';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    if (!is_dir($dir) || !is_writable($dir)) throw new RuntimeException('La carpeta storage/backups no tiene permiso de escritura.');
    $tmp = $dir . '/copia_tmp.sql'; $f = fopen($tmp, 'wb');
    if (!$f) throw new RuntimeException('No se pudo crear el archivo de copia.');
    $pdo = db();
    fwrite($f, "-- Copia de seguridad CTTAOA · " . date('Y-m-d H:i:s') . "\n-- Restaurar: phpMyAdmin → Importar (sobre la base cttaoa).\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");
    foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $t) {
        $crear = $pdo->query('SHOW CREATE TABLE `' . $t . '`')->fetch(PDO::FETCH_NUM)[1];
        fwrite($f, "DROP TABLE IF EXISTS `$t`;\n$crear;\n\n");
        $st = $pdo->query('SELECT * FROM `' . $t . '`'); $cols = null; $lote = [];
        while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
            if ($cols === null) $cols = '`' . implode('`,`', array_keys($r)) . '`';
            $vals = []; foreach ($r as $v) $vals[] = $v === null ? 'NULL' : $pdo->quote((string)$v);
            $lote[] = '(' . implode(',', $vals) . ')';
            if (count($lote) >= 200) { fwrite($f, "INSERT INTO `$t` ($cols) VALUES\n" . implode(",\n", $lote) . ";\n"); $lote = []; }
        }
        if ($lote) fwrite($f, "INSERT INTO `$t` ($cols) VALUES\n" . implode(",\n", $lote) . ";\n");
        fwrite($f, "\n");
    }
    fwrite($f, "SET FOREIGN_KEY_CHECKS=1;\n"); fclose($f);
    $final = ruta_copia(); @unlink($final);
    if (!rename($tmp, $final)) throw new RuntimeException('No se pudo guardar la copia.');
    q('UPDATE configuracion SET ultima_copia=? WHERE id=1', [now()]);
    return filesize($final);
}

// ---------------- alertas de actividad sospechosa ----------------
function alertas_sospechosas($solo_nuevas = false) {
    $cfg = config_db();
    if (empty($cfg['alertas_activas'])) return [];
    $out = []; $umbral = min(20, max(1, (int)$cfg['alerta_fallos']));   // cuentas bloqueadas en 1 hora
    // Al pulsar «Revisar» se guarda una marca; con $solo_nuevas solo cuentan los sucesos posteriores a esa marca.
    $visto = $solo_nuevas ? val("SELECT MAX(fecha) FROM eventos_seguridad WHERE tipo='alertas_vistas'") : null;
    $desde = function ($seg) use ($visto) { $d = date('Y-m-d H:i:s', time() - $seg); return ($visto && $visto > $d) ? $visto : $d; };
    $hora = $desde(3600); $dia = $desde(86400); $semana = $desde(7 * 86400);
    // Las alertas de titulares se basan en BLOQUEOS de cuentas (por documento), no en la IP: así el WiFi compartido no genera falsas alarmas.
    $n = (int)val("SELECT COUNT(DISTINCT detalle) FROM eventos_seguridad WHERE tipo='bloqueo' AND detalle LIKE 'titular:%' AND fecha > ?", [$hora]);
    if ($n >= $umbral) $out[] = ['alta', $n . ' cuentas de titulares se bloquearon en la última hora: puede ser un intento de adivinar PINs o un uso indebido.'];
    $f = (int)val("SELECT COUNT(DISTINCT detalle) FROM eventos_seguridad WHERE tipo='bloqueo_fuerte' AND fecha > ?", [$dia]);
    if ($f > 0) $out[] = ['alta', $f . ' cuenta(s) llegaron al bloqueo de 24 horas por fallos repetidos (ver «Cuentas bloqueadas»).'];
    foreach (rows("SELECT detalle, COUNT(*) AS n FROM eventos_seguridad WHERE tipo='admin_fallo' AND fecha > ? GROUP BY detalle HAVING COUNT(*) >= 3 ORDER BY n DESC LIMIT 5", [$hora]) as $r)
        $out[] = ['alta', 'Varios intentos fallidos (' . $r['n'] . ') contra la cuenta de administrador «' . $r['detalle'] . '» en la última hora.'];
    $b = (int)val("SELECT COUNT(*) FROM eventos_seguridad WHERE tipo='bloqueo' AND fecha > ?", [$dia]);
    if ($b > 0) $out[] = ['media', $b . ' bloqueo(s) de cuentas en las últimas 24 horas (ver «Cuentas bloqueadas»).'];
    // Página pública de verificación: solo AVISA (no bloquea a nadie) si una misma IP consulta muchísimos documentos distintos.
    $lim = (int)cfg('verificar_alerta', 100);
    if ($lim > 0) foreach (rows('SELECT ip, COUNT(DISTINCT documento) AS n FROM consultas_verificacion WHERE fecha > ? GROUP BY ip HAVING COUNT(DISTINCT documento) >= ? ORDER BY n DESC LIMIT 3', [$hora, $lim]) as $r)
        $out[] = ['media', 'Desde la IP ' . $r['ip'] . ' se consultaron ' . $r['n'] . ' documentos distintos en la página pública de verificación en la última hora (¿descarga masiva de datos?).'];
    foreach (rows("SELECT detalle, ip, fecha FROM eventos_seguridad WHERE tipo='admin_ip_nueva' AND fecha > ? ORDER BY id DESC LIMIT 5", [$semana]) as $r)
        $out[] = ['media', 'El administrador «' . $r['detalle'] . '» ingresó desde una IP nueva (' . $r['ip'] . ') el ' . hora_legible($r['fecha']) . '.'];
    return $out;
}
