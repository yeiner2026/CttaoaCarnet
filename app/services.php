<?php
require_once __DIR__ . '/xlsx.php';

// ================= PERSONAS =================
const SQL_PERSONA = 'SELECT p.*, f.numero AS ficha_numero, f.id AS ficha_pk, pr.nombre AS programa_nombre,
    c.id AS carne_id, c.token, c.estado AS carne_estado
    FROM personas p LEFT JOIN fichas f ON f.id=p.ficha_id LEFT JOIN programas pr ON pr.id=f.programa_id
    LEFT JOIN carnes c ON c.persona_id=p.id';

function persona($id) { return row(SQL_PERSONA . ' WHERE p.id=?', [$id]); }
function titulo_nombre($s) { return mb_convert_case(mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string)$s)), 'UTF-8'), MB_CASE_TITLE, 'UTF-8'); }
function persona_nombres_tc($p) { return titulo_nombre($p['nombres'] ?? ''); }
function persona_apellidos_tc($p) { return titulo_nombre($p['apellidos'] ?? ''); }
function persona_nombre($p) { return trim(persona_nombres_tc($p) . ' ' . persona_apellidos_tc($p)); }
function persona_programa_o_area($p) { return $p['tipo_persona'] === 'aprendiz' ? (string)($p['programa_nombre'] ?? '') : (string)$p['dependencia']; }
function persona_rh_visible($p) { return (string)($p['rh'] ?? ''); }
function persona_faltantes($p) {
    $f = [];
    if ($p['nombres'] === '') $f[] = 'nombres';
    if ($p['apellidos'] === '') $f[] = 'apellidos';
    if ($p['numero_documento'] === '') $f[] = 'documento';
    if ($p['foto'] === '') $f[] = 'fotografía';
    if (($p['rh'] ?? '') === '') $f[] = 'tipo de sangre (RH)';
    if (empty($p['fecha_expedicion'])) $f[] = 'fecha de expedición';
    if ($p['tipo_persona'] === 'aprendiz' && empty($p['ficha_id'])) $f[] = 'ficha';
    if ($p['tipo_persona'] !== 'aprendiz' && $p['dependencia'] === '') $f[] = 'área/dependencia';
    return $f;
}
// ---------- estado del aprendiz en su formación ----------
const ESTADOS_FORMACION = [   // código => [etiqueta, ¿vigente? (puede usar el carnet)]
    'en_formacion'      => ['En formación', true],
    'etapa_practica'    => ['En etapa práctica', true],
    'retiro_voluntario' => ['Retiro voluntario', false],
    'cancelado'         => ['Cancelado', false],
    'certificado'       => ['Certificado (egresado)', false],
];
/** Estado efectivo: solo aplica a aprendices; vacío se toma como «en formación». */
function formacion_codigo($p) { return ($p['tipo_persona'] ?? '') === 'aprendiz' ? ((string)($p['estado_formacion'] ?? '') ?: 'en_formacion') : ''; }
function formacion_etiqueta($c) { return ESTADOS_FORMACION[$c][0] ?? ''; }
function formacion_vigente($c) { return $c === '' || !empty(ESTADOS_FORMACION[$c][1]); }
/** Lo que impide activar un carnet: datos incompletos o estado de formación no vigente. */
function persona_no_activable($p) {
    $f = persona_faltantes($p); $c = formacion_codigo($p);
    if ($c !== '' && !formacion_vigente($c)) $f[] = 'estado en la formación vigente (hoy: ' . formacion_etiqueta($c) . ')';
    return $f;
}
/** Si el aprendiz ya no está vigente (retiro, cancelado, certificado), su carnet activo pasa a inactivo. Devuelve true si lo desactivó. */
function aplicar_efecto_formacion($pid) {
    $p = persona($pid); if (!$p) return false;
    $c = formacion_codigo($p);
    if ($c !== '' && !formacion_vigente($c) && ($p['carne_estado'] ?? '') === 'activo') {
        $a = admin_actual();
        q('UPDATE carnes SET estado=?, estado_cambiado=?, estado_cambiado_por=? WHERE id=?', ['inactivo', now(), $a ? $a['id'] : null, $p['carne_id']]);
        audit('estado', 'Carne', $p['carne_id'], 'activo → inactivo (estado en la formación: ' . formacion_etiqueta($c) . ')');
        return true;
    }
    return false;
}
/** Cambia el estado de formación de un aprendiz. Devuelve [cambió, desactivó_carnet]. */
function cambiar_estado_formacion($pid, $nuevo) {
    $p = row('SELECT * FROM personas WHERE id=?', [$pid]);
    if (!$p || $p['tipo_persona'] !== 'aprendiz' || !isset(ESTADOS_FORMACION[$nuevo])) return [false, false];
    $antes = formacion_codigo($p);
    if ($antes === $nuevo && ($p['estado_formacion'] ?? '') === $nuevo) return [false, false];
    q('UPDATE personas SET estado_formacion=?, estado_formacion_fecha=?, actualizada=? WHERE id=?', [$nuevo, now(), now(), $pid]);
    audit('estado_formacion', 'Persona', $pid, formacion_etiqueta($antes) . ' → ' . formacion_etiqueta($nuevo));
    return [true, aplicar_efecto_formacion($pid)];
}
/** Resultado para la página pública de verificación: [vigente(bool), título, detalle]. */
function estado_verificacion($p) {
    $c = formacion_codigo($p);
    if ($c !== '' && !formacion_vigente($c)) return [false, mb_strtoupper(formacion_etiqueta($c)), 'Estado del aprendiz en su formación'];
    if (($p['carne_estado'] ?? '') === 'activo') return [true, 'ACTIVO', $c !== '' ? formacion_etiqueta($c) : ucfirst(mb_strtolower(TIPOS_PERSONA[$p['tipo_persona']]))];
    return [false, 'NO VIGENTE', 'El carnet no está activo'];
}
function carne_es_borrador($p) { return ($p['carne_estado'] ?? 'pendiente') === 'pendiente' || persona_faltantes($p); }
function nuevo_token() { return strtoupper(bin2hex(random_bytes(6))); }

function recalcular_registro($id) {
    $p = row('SELECT * FROM personas WHERE id=?', [$id]); if (!$p) return;
    if ($p['estado_registro'] !== 'retirado') {
        q('UPDATE personas SET estado_registro=?, actualizada=? WHERE id=?', [persona_faltantes($p) ? 'pendiente' : 'registrado', now(), $id]);
    }
    if (!val('SELECT id FROM carnes WHERE persona_id=?', [$id])) {
        for ($i = 0; $i < 5; $i++) {
            try { q('INSERT INTO carnes (persona_id, token, estado) VALUES (?,?,?)', [$id, nuevo_token(), 'pendiente']); break; }
            catch (PDOException $e) { if ($i === 4) throw $e; }
        }
    }
}
/** Días de aviso antes de que termine una ficha (config.php → ficha_aviso_dias, por defecto 5). */
function ficha_aviso_dias() { return max(1, min(60, (int)cfg('ficha_aviso_dias', 5))); }
/** Estado de vigencia de una ficha: clase sin_fecha | ok | pronto | vencida, días que faltan y un texto corto. */
function ficha_vigencia($f) {
    $fin = $f['fecha_fin'] ?? null;
    if (!$fin) return ['clase' => 'sin_fecha', 'dias' => null, 'texto' => 'Sin fecha de fin'];
    $dias = (int)round((strtotime($fin) - strtotime(date('Y-m-d'))) / 86400);
    if ($dias < 0) return ['clase' => 'vencida', 'dias' => $dias, 'texto' => 'Terminó el ' . fecha_legible($fin)];
    if ($dias <= ficha_aviso_dias()) return ['clase' => 'pronto', 'dias' => $dias, 'texto' => $dias === 0 ? 'Termina hoy' : ($dias === 1 ? 'Termina mañana' : 'Termina en ' . $dias . ' días')];
    return ['clase' => 'ok', 'dias' => $dias, 'texto' => 'Termina el ' . fecha_legible($fin)];
}
/** Guarda las fechas de la ficha. Si se amplía el plazo, la ficha vuelve a vigilarse (los carnets ya inactivos NO se reactivan solos). */
function ficha_fijar_fechas($id, $inicio, $fin) {
    $f = row('SELECT * FROM fichas WHERE id=?', [$id]); if (!$f) return;
    $c = ['fecha_inicio' => $inicio ?: null, 'fecha_fin' => $fin ?: null];
    if (!$fin || ($fin >= date('Y-m-d') && (string)$f['fecha_fin'] !== $fin)) $c['vencida_procesada'] = 0;
    update('fichas', $c, $id);
}
/** Cuando una ficha termina, TODOS sus carnets activos pasan a inactivo (una sola vez). La ficha no se borra ni se desactiva. */
function procesar_fichas_vencidas() {
    static $hecho = false; if ($hecho) return; $hecho = true;
    try {
        foreach (rows('SELECT id, numero FROM fichas WHERE fecha_fin IS NOT NULL AND fecha_fin < ? AND vencida_procesada=0', [date('Y-m-d')]) as $f) {
            $n = q("UPDATE carnes c JOIN personas p ON p.id=c.persona_id SET c.estado='inactivo', c.estado_cambiado=?, c.estado_cambiado_por=NULL WHERE p.ficha_id=? AND c.estado='activo'", [now(), $f['id']])->rowCount();
            q('UPDATE fichas SET vencida_procesada=1 WHERE id=?', [$f['id']]);
            audit('ficha_vencida', 'Ficha', $f['id'], 'La ficha ' . $f['numero'] . ' terminó: ' . $n . ' carnet(s) pasaron a inactivo', 0, 'sistema');
        }
    } catch (Throwable $e) { @file_put_contents(APP_ROOT . '/storage/error.log', '[' . date('c') . '] fichas vencidas: ' . $e->getMessage() . "\n", FILE_APPEND); }
}
/** Fichas que terminan en los próximos días de aviso o que terminaron en los últimos 60 días (para el inicio y las alertas). */
function alertas_fichas() {
    $hoy = date('Y-m-d'); $lista = [];
    foreach (rows('SELECT f.id, f.numero, f.fecha_inicio, f.fecha_fin, f.vencida_procesada, pr.nombre AS programa FROM fichas f JOIN programas pr ON pr.id=f.programa_id WHERE f.fecha_fin IS NOT NULL AND f.fecha_fin BETWEEN ? AND ? ORDER BY f.fecha_fin',
                  [date('Y-m-d', strtotime('-60 days')), date('Y-m-d', strtotime('+' . ficha_aviso_dias() . ' days'))]) as $f) {
        $v = ficha_vigencia($f); if ($v['clase'] === 'ok') continue; $f['clase'] = $v['clase']; $f['texto'] = $v['texto']; $lista[] = $f;
    }
    return $lista;
}
/** Si la ficha ya existe sin fechas, se las pone; si ya tiene, se conservan. Devuelve true si se aplicaron. */
function ficha_fechas_si_vacias($id, $inicio, $fin) {
    $f = row('SELECT * FROM fichas WHERE id=?', [$id]); if (!$f || (!$inicio && !$fin)) return false;
    if ($f['fecha_inicio'] || $f['fecha_fin']) return false;
    ficha_fijar_fechas($id, $inicio, $fin); return true;
}
function ficha_obtener_o_crear($numero, $programa) {
    $f = row('SELECT * FROM fichas WHERE numero=?', [$numero]);
    if ($f) return $f;
    $pid = val('SELECT id FROM programas WHERE nombre=?', [$programa]);
    if (!$pid) $pid = insert('programas', ['nombre' => $programa]);
    $id = insert('fichas', ['numero' => $numero, 'programa_id' => $pid, 'activa' => 1, 'creada' => now()]);
    return row('SELECT * FROM fichas WHERE id=?', [$id]);
}
function programa_id($nombre) {
    $pid = val('SELECT id FROM programas WHERE nombre=?', [$nombre]);
    return $pid ?: insert('programas', ['nombre' => $nombre]);
}
function eliminar_archivo_privado($rel) {
    if ($rel === '' || $rel === null) return;
    $ruta = APP_ROOT . '/storage/' . $rel;
    if (strpos($rel, '..') === false && is_file($ruta)) @unlink($ruta);
}

// ================= VALIDACIÓN DEL FORMULARIO DE PERSONA =================
/** Lee una fecha (aaaa-mm-dd o dd/mm/aaaa). Por defecto es una fecha pasada (expedición); con $futuro=true admite fechas futuras (p. ej. el fin de una ficha). */
function parsear_fecha($v, $futuro = false, $etq = 'fecha de expedición') {
    $v = trim((string)$v);
    if ($v === '') return [null, null];
    $f = null;
    foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'd/m/y'] as $fmt) {
        $d = DateTime::createFromFormat('!' . $fmt, $v);
        $err = DateTime::getLastErrors();
        if ($d && (!$err || ($err['warning_count'] == 0 && $err['error_count'] == 0))) { $f = $d; break; }
    }
    if (!$f) return [null, $etq . " inválida ('" . $v . "'); usa dd/mm/aaaa"];
    if (($f->format('Y-m-d') > date('Y-m-d') && !$futuro) || (int)$f->format('Y') < 1900 || (int)$f->format('Y') > 2100) return [null, $etq . ' fuera de rango'];
    return [$f->format('Y-m-d'), null];
}
function validar_persona($in, $idActual = null) {
    $e = []; $d = [];
    $d['tipo_persona'] = $in['tipo_persona'] ?? '';
    if (!isset(TIPOS_PERSONA[$d['tipo_persona']])) $e['tipo_persona'] = 'Elige el tipo de persona.';
    $d['tipo_documento'] = (string)($in['tipo_documento'] ?? '');
    if (!isset(TIPOS_DOC[$d['tipo_documento']])) $e['tipo_documento'] = $d['tipo_documento'] === '' ? 'Elige el tipo de documento.' : 'Tipo de documento no válido.';
    $doc = preg_replace('/[\s.]/', '', (string)($in['numero_documento'] ?? ''));
    $d['numero_documento'] = $doc;
    if ($doc === '' || !ctype_digit($doc)) $e['numero_documento'] = 'El documento solo debe contener números.';
    elseif (strlen($doc) > 20) $e['numero_documento'] = 'Documento demasiado largo.';
    else {
        $dup = val('SELECT id FROM personas WHERE numero_documento=?' . ($idActual ? ' AND id<>?' : ''), $idActual ? [$doc, $idActual] : [$doc]);
        if ($dup) $e['numero_documento'] = 'Ya existe una persona con este documento.';
    }
    $d['nombres'] = titulo_nombre($in['nombres'] ?? ''); $d['apellidos'] = titulo_nombre($in['apellidos'] ?? '');
    if ($d['nombres'] === '' || mb_strlen($d['nombres']) > 100) $e['nombres'] = 'Indica los nombres (máx. 100 caracteres).';
    if ($d['apellidos'] === '' || mb_strlen($d['apellidos']) > 100) $e['apellidos'] = 'Indica los apellidos (máx. 100 caracteres).';
    list($fe, $ef) = parsear_fecha($in['fecha_expedicion'] ?? ''); $d['fecha_expedicion'] = $fe;
    if ($ef) $e['fecha_expedicion'] = $ef; elseif (!$fe) $e['fecha_expedicion'] = 'La fecha de expedición es obligatoria.';
    $d['email'] = trim((string)($in['email'] ?? ''));
    $ee = validar_correo_titular($d['email'], $idActual); if ($ee) $e['email'] = $ee;      // obligatorio, válido y sin repetir
    $d['rh'] = (string)($in['rh'] ?? '');
    if ($d['rh'] === '') $e['rh'] = 'El tipo de sangre (RH) es obligatorio.';
    elseif (!in_array($d['rh'], GRUPOS_RH, true)) $e['rh'] = 'RH no válido.';
    $d['rh_verificado'] = $d['rh'] !== '' ? 1 : 0;   // el RH es obligatorio y se imprime siempre en el carnet
    $d['observaciones'] = trim((string)($in['observaciones'] ?? ''));
    $d['dependencia'] = trim((string)($in['dependencia'] ?? ''));
    $d['estado_formacion'] = '';
    if ($d['tipo_persona'] === 'aprendiz') {
        $ef = (string)($in['estado_formacion'] ?? '');
        if ($ef !== '' && !isset(ESTADOS_FORMACION[$ef])) $e['estado_formacion'] = 'Estado en la formación no válido.';
        $d['estado_formacion'] = $ef;    // vacío = conservar el actual (o «en formación» si es nuevo)
    }
    $d['_ficha'] = null; $d['_programa_cambia'] = false; $d['_ficha_numero'] = trim((string)($in['ficha_numero'] ?? '')); $d['_programa'] = trim((string)($in['programa_nombre'] ?? ''));
    if ($d['tipo_persona'] === 'aprendiz') {
        $d['dependencia'] = '';
        $num = $d['_ficha_numero']; $prog = $d['_programa'];
        if ($num === '') $e['ficha_numero'] = 'El aprendiz requiere número de ficha.';
        elseif (strlen($num) > 20) $e['ficha_numero'] = 'Número de ficha demasiado largo.';
        else {
            $f = row('SELECT f.*, p.nombre AS programa FROM fichas f JOIN programas p ON p.id=f.programa_id WHERE f.numero=?', [$num]);
            if (!$f) { if ($prog === '') $e['programa_nombre'] = 'Ficha nueva: indica el programa de formación.'; }
            elseif ($prog !== '' && mb_strtolower($prog) !== mb_strtolower($f['programa'])) {
                $otros = val('SELECT COUNT(*) FROM personas WHERE ficha_id=?' . ($idActual ? ' AND id<>?' : ''), $idActual ? [$f['id'], $idActual] : [$f['id']]);
                if ($otros > 0 && empty($in['actualizar_programa_ficha']))
                    $e['programa_nombre'] = 'La ficha ' . $num . ' pertenece al programa «' . $f['programa'] . '» y tiene otros aprendices. Para cambiar el programa de toda la ficha marca la casilla; para mover a esta persona a otro grupo cambia el número de ficha.';
                else $d['_programa_cambia'] = true;
            }
            $d['_ficha'] = $f;
        }
    } else {
        if ($d['dependencia'] === '') $e['dependencia'] = 'Indica el área o dependencia.';
    }
    return [$e, $d];
}
function guardar_persona($d, $idActual = null) {
    $pdo = db(); $pdo->beginTransaction();
    try {
        $ficha_id = null;
        if ($d['tipo_persona'] === 'aprendiz') {
            $f = $d['_ficha'];
            if (!$f) { $f = ficha_obtener_o_crear($d['_ficha_numero'], $d['_programa']); }
            elseif ($d['_programa_cambia']) q('UPDATE fichas SET programa_id=? WHERE id=?', [programa_id($d['_programa']), $f['id']]);
            $ficha_id = $f['id'];
        }
        $campos = ['tipo_persona' => $d['tipo_persona'], 'tipo_documento' => $d['tipo_documento'], 'numero_documento' => $d['numero_documento'],
                   'nombres' => $d['nombres'], 'apellidos' => $d['apellidos'], 'fecha_expedicion' => $d['fecha_expedicion'], 'email' => $d['email'],
                   'rh' => $d['rh'], 'rh_verificado' => $d['rh_verificado'], 'ficha_id' => $ficha_id, 'dependencia' => $d['dependencia'],
                   'observaciones' => $d['observaciones'], 'actualizada' => now()];
        $actualEF = $idActual ? (string)val('SELECT estado_formacion FROM personas WHERE id=?', [$idActual]) : '';
        if ($d['tipo_persona'] === 'aprendiz') {
            $nuevoEF = $d['estado_formacion'] !== '' ? $d['estado_formacion'] : ($actualEF !== '' ? $actualEF : 'en_formacion');
            $campos['estado_formacion'] = $nuevoEF;
            if (!$idActual || $nuevoEF !== ($actualEF !== '' ? $actualEF : 'en_formacion')) $campos['estado_formacion_fecha'] = now();
        } else { $campos['estado_formacion'] = ''; }
        if ($idActual) { update('personas', $campos, $idActual); $id = $idActual; }
        else { $campos['creada'] = now(); $campos['estado_registro'] = 'pendiente'; $id = insert('personas', $campos); }
        recalcular_registro($id);
        if (!empty($campos['estado_formacion'])) {
            $antesEF = $actualEF !== '' ? $actualEF : 'en_formacion';
            if ($idActual && $campos['estado_formacion'] !== $antesEF) audit('estado_formacion', 'Persona', $id, formacion_etiqueta($antesEF) . ' → ' . formacion_etiqueta($campos['estado_formacion']));
            aplicar_efecto_formacion($id);
        }
        $pdo->commit();
        return $id;
    } catch (Exception $ex) { $pdo->rollBack(); throw $ex; }
}

// ================= IMÁGENES (GD) =================
function cargar_imagen($ruta, &$error) {
    if (!extension_loaded('gd')) { $error = 'El hosting no tiene la extensión GD (imágenes) activada.'; return null; }
    if (filesize($ruta) > cfg('max_foto_bytes')) { $error = 'La imagen supera los ' . round(cfg('max_foto_bytes') / 1048576) . ' MB.'; return null; }
    $info = @getimagesize($ruta);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) { $error = $info ? 'Solo se permiten imágenes JPG o PNG.' : 'El archivo no es una imagen válida.'; return null; }
    if ($info[0] > 6000 || $info[1] > 6000 || $info[0] < 20 || $info[1] < 20) { $error = 'Dimensiones no permitidas (máximo 6000 px).'; return null; }
    $img = $info[2] === IMAGETYPE_JPEG ? @imagecreatefromjpeg($ruta) : @imagecreatefrompng($ruta);
    if (!$img) { $error = 'El archivo no es una imagen válida.'; return null; }
    return $img;
}
function reducir($img, $maxw, $maxh, $alpha) {
    $w = imagesx($img); $h = imagesy($img);
    $r = min($maxw / $w, $maxh / $h, 1); $nw = max(1, (int)round($w * $r)); $nh = max(1, (int)round($h * $r));
    $dst = imagecreatetruecolor($nw, $nh);
    if ($alpha) { imagealphablending($dst, false); imagesavealpha($dst, true); imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127)); }
    else { imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); }
    imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
    return $dst;
}
/** Valida y guarda una fotografía normalizada (JPEG). Devuelve el nombre relativo o null. */
function procesar_foto($ruta, &$error) {
    $img = cargar_imagen($ruta, $error); if (!$img) return null;
    $dst = reducir($img, 450, 600, false);   // suficiente para el carnet y mucho más liviana
    imageinterlace($dst, true);
    $rel = 'fotos/' . bin2hex(random_bytes(16)) . '.jpg';
    imagejpeg($dst, APP_ROOT . '/storage/' . $rel, 82);
    return $rel;
}
/** Logotipo/firma: PNG que conserva la transparencia. */
function procesar_logo($ruta, &$error) {
    $img = cargar_imagen($ruta, $error); if (!$img) return null;
    imagealphablending($img, false); imagesavealpha($img, true);
    $dst = reducir($img, 800, 800, true);
    $rel = 'institucional/' . bin2hex(random_bytes(16)) . '.png';
    imagepng($dst, APP_ROOT . '/storage/' . $rel, 6);
    return $rel;
}
function guardar_foto_persona($pid, $ruta_tmp, &$error) {
    $rel = procesar_foto($ruta_tmp, $error); if (!$rel) return false;
    $viejo = val('SELECT foto FROM personas WHERE id=?', [$pid]);
    $a = admin_actual();
    q('UPDATE personas SET foto=?, foto_subida_por=?, foto_fecha=? WHERE id=?', [$rel, $a ? $a['id'] : null, now(), $pid]);
    eliminar_archivo_privado($viejo); recalcular_registro($pid);
    audit('foto', 'Persona', $pid, 'Fotografía cargada o reemplazada');
    return true;
}

// ================= CÓDIGO DE BARRAS (Code128) =================
function code128_bits($texto) {
    $T = require APP_ROOT . '/app/data/code128.php';
    $texto = (string)$texto; $v = [];
    if (ctype_digit($texto) && strlen($texto) >= 4) {
        $n = strlen($texto);
        if ($n % 2 === 0) { $v[] = 105; for ($i = 0; $i < $n; $i += 2) $v[] = (int)substr($texto, $i, 2); }
        else { $v[] = 104; $v[] = ord($texto[0]) - 32; $v[] = 99; for ($i = 1; $i < $n; $i += 2) $v[] = (int)substr($texto, $i, 2); }
    } else { $v[] = 104; foreach (str_split($texto) as $ch) $v[] = ord($ch) - 32; }
    $s = $v[0]; for ($i = 1; $i < count($v); $i++) $s += $v[$i] * $i;
    $v[] = $s % 103; $v[] = 106;
    $bits = ''; foreach ($v as $x) $bits .= $T[$x];
    return $bits . '11';
}
function barcode_svg($texto) {
    $bits = code128_bits($texto); $q = 10; $n = strlen($bits); $r = ''; $i = 0;
    while ($i < $n) {
        if ($bits[$i] === '1') { $j = $i; while ($j < $n && $bits[$j] === '1') $j++; $r .= '<rect x="' . ($i + $q) . '" y="0" width="' . ($j - $i) . '" height="60"/>'; $i = $j; }
        else $i++;
    }
    $w = $n + 2 * $q;
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $w . ' 60" preserveAspectRatio="none" shape-rendering="crispEdges" role="img" aria-label="Código de barras"><rect width="' . $w . '" height="60" fill="#fff"/><g fill="#000">' . $r . '</g></svg>';
}

// ================= PIN Y CÓDIGOS DE RECUPERACIÓN =================
function pin_debil($pin) {
    return count(array_unique(str_split($pin))) === 1 || in_array($pin, ['0123','1234','2345','3456','4567','5678','6789','9876','8765','7654','6543','5432','4321','3210'], true);
}
function validar_pin($pin, $pin2) {
    if (!preg_match('/^\d{4}$/', $pin)) return 'El PIN debe tener exactamente 4 dígitos.';
    if ($pin !== $pin2) return 'Los PIN no coinciden.';
    if (pin_debil($pin)) return 'Elige un PIN menos predecible (evita 1234, 0000, etc.).';
    return '';
}
function establecer_pin($pid, $pin) {
    q('UPDATE personas SET pin_hash=?, pin_configurado=1, actualizada=? WHERE id=?', [password_hash($pin, PASSWORD_DEFAULT), now(), $pid]);
    $doc = val('SELECT numero_documento FROM personas WHERE id=?', [$pid]);
    intento_limpiar(intento('pin:' . $doc));
}
/** ¿Ese correo ya está registrado con otra persona? (sin distinguir mayúsculas) */
function correo_en_uso($email, $excluirId = null) {
    return (bool)val('SELECT id FROM personas WHERE LOWER(email)=LOWER(?)' . ($excluirId ? ' AND id<>?' : '') . ' LIMIT 1', $excluirId ? [$email, $excluirId] : [$email]);
}
/** Mensaje de error si el correo no sirve (vacío si está bien). */
function validar_correo_titular($email, $idPersona = null) {
    $email = trim((string)$email);
    if ($email === '') return 'El correo electrónico es obligatorio.';
    if (mb_strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) return 'Escribe un correo electrónico válido.';
    if (correo_en_uso($email, $idPersona)) return 'Ese correo ya está registrado con otra persona.';
    return '';
}
/**
 * «Olvidé mi PIN»: si el documento existe y tiene correo, envía un enlace seguro de un solo uso.
 * Siempre responde igual desde afuera (no revela si el documento existe). Límites: 3 por hora por persona y 10 por hora por IP.
 */
function solicitar_restablecer_pin($doc) {
    $p = $doc !== '' ? row('SELECT * FROM personas WHERE numero_documento=?', [$doc]) : null;
    if (!$p || !$p['pin_configurado'] || trim((string)$p['email']) === '') return false;
    $hace = date('Y-m-d H:i:s', time() - 3600);
    if ((int)val('SELECT COUNT(*) FROM restablecer_pin WHERE persona_id=? AND creado>?', [$p['id'], $hace]) >= 3) return false;
    if ((int)val('SELECT COUNT(*) FROM restablecer_pin WHERE ip=? AND creado>?', [client_ip(), $hace]) >= 10) return false;
    $min = max(5, (int)config_db()['recuperacion_minutos']); $token = bin2hex(random_bytes(32));
    q('UPDATE restablecer_pin SET usado=1 WHERE persona_id=? AND usado=0', [$p['id']]);       // solo vale el enlace más reciente
    insert('restablecer_pin', ['persona_id' => $p['id'], 'token_hash' => hash('sha256', $token), 'creado' => now(), 'vence' => date('Y-m-d H:i:s', time() + $min * 60), 'usado' => 0, 'ip' => client_ip()]);
    $link = correo_sitio() . url('/restablecer-pin') . '?t=' . $token; $nombre = explode(' ', persona_nombres_tc($p))[0];
    $texto = "Hola, " . $nombre . ".\n\nRecibimos una solicitud para crear un PIN nuevo en el carnet digital del CTTAOA.\n\nAbre este enlace para elegir tu PIN nuevo (vence en " . $min . " minutos y solo sirve una vez):\n" . $link .
             "\n\nSi tú no lo pediste, ignora este mensaje: tu PIN actual no cambia y nadie puede cambiarlo sin este enlace.\n\n— Carnet digital CTTAOA";
    $html = '<div style="font-family:Arial,Helvetica,sans-serif;max-width:480px;margin:auto;color:#1f2a1f"><h2 style="color:#2a7d28;margin-bottom:4px">Carnet digital CTTAOA</h2><p>Hola, <strong>' . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') .
            '</strong>.</p><p>Recibimos una solicitud para crear un PIN nuevo. El enlace vence en <strong>' . $min . ' minutos</strong> y solo sirve una vez.</p><p style="margin:24px 0"><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') .
            '" style="background:#2a7d28;color:#fff;padding:12px 22px;border-radius:8px;text-decoration:none;font-weight:bold">Crear mi PIN nuevo</a></p><p style="font-size:13px;color:#555">Si el botón no funciona, copia y pega este enlace en tu navegador:<br>' .
            htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '</p><p style="font-size:13px;color:#555">Si tú no lo pediste, ignora este mensaje: tu PIN actual no cambia.</p></div>';
    list($ok, $err) = enviar_correo($p['email'], 'Crea tu PIN nuevo · Carnet digital CTTAOA', $texto, $html);
    if (!$ok) @file_put_contents(APP_ROOT . '/storage/error.log', '[' . date('c') . '] correo de PIN: ' . $err . "\n", FILE_APPEND);
    audit('pin_solicitado', 'Persona', $p['id'], $ok ? 'Enlace enviado al correo registrado' : 'No se pudo enviar el correo', null, 'titular:' . enmascarar_doc($p['numero_documento']));
    return $ok;
}
/** Busca un enlace de restablecimiento válido (no usado y sin vencer). Devuelve la fila o null. */
function restablecer_buscar($token) {
    if (!preg_match('/^[a-f0-9]{64}$/', (string)$token)) return null;
    return row('SELECT * FROM restablecer_pin WHERE token_hash=? AND usado=0 AND vence>?', [hash('sha256', $token), now()]);
}
