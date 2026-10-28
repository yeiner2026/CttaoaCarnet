<?php
// Controladores del panel administrativo.
require_once __DIR__ . '/import.php';

function vista_admin($v, $d = []) { render('admin/' . $v, $d, 'admin'); }
function paginar($countSql, $sql, $params, $por = 25) {
    $total = (int)val($countSql, $params); $paginas = max(1, (int)ceil($total / $por));
    $page = min($paginas, max(1, (int)get('page', 1)));
    return ['items' => rows($sql . ' LIMIT ' . (int)$por . ' OFFSET ' . (int)(($page - 1) * $por), $params), 'total' => $total, 'page' => $page, 'paginas' => $paginas];
}
function qs($extra = []) { $p = array_merge($_GET, $extra); unset($p['page']); return http_build_query($p); }
function subir_archivo($campo) {
    $f = $_FILES[$campo] ?? null;
    if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) return false;
    return $f;
}

// ---------- sesión ----------
function ctl_admin_login() {
    if (admin_actual()) redirect(admin_url('/'));
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificar_csrf();
        $usuario = mb_substr(post('usuario'), 0, 60); $ip = client_ip();
        $porU = intento('adm:' . $ip . ':' . mb_strtolower($usuario), 'admin', $usuario . ' · ' . $ip); $porIp = intento('admip:' . $ip, 'ip-admin', $ip);
        $bl = array_values(array_filter([$porU, $porIp], 'intento_bloqueado'));
        if ($bl) {
            $n = max(array_map('intento_minutos', $bl));
            flash('error', 'Demasiados intentos. Intenta de nuevo en ' . $n . ' minutos.');
        } else {
            $u = row('SELECT * FROM usuarios WHERE usuario=? AND activo=1', [$usuario]);
            $ok = $u && password_verify($_POST['password'] ?? '', $u['password_hash']);
            if (!$u) dummy_verify($_POST['password'] ?? '');
            if ($ok) {
                intento_limpiar($porU); session_regenerate_id(true);
                $_SESSION['admin_id'] = (int)$u['id']; $_SESSION['admin_last'] = time();
                audit('login_admin', 'Usuario', $u['id'], 'ip=' . $ip, $u['id'], $u['usuario']);
                $antes = (int)val("SELECT COUNT(*) FROM eventos_seguridad WHERE tipo='admin_login' AND detalle=?", [$u['usuario']]);
                $conIp = (int)val("SELECT COUNT(*) FROM eventos_seguridad WHERE tipo='admin_login' AND detalle=? AND ip=?", [$u['usuario'], $ip]);
                if ($antes > 0 && $conIp === 0) evento_seguridad('admin_ip_nueva', $u['usuario']);
                evento_seguridad('admin_login', $u['usuario']);
                q('UPDATE usuarios SET ultimo_acceso=?, ultima_ip=? WHERE id=?', [now(), $ip, $u['id']]);
                redirect(admin_url('/'));
            }
            intento_fallo($porU, (int)cfg('admin_max_intentos'), (int)cfg('admin_bloqueo_minutos'));
            intento_fallo($porIp, (int)cfg('admin_ip_max_intentos'), (int)cfg('admin_bloqueo_minutos'));
            audit('login_admin_fallido', 'Usuario', '', "usuario='" . mb_substr($usuario, 0, 50) . "' ip=" . $ip, null, '');
            evento_seguridad('admin_fallo', mb_substr($usuario, 0, 60));
            flash('error', 'Usuario o contraseña incorrectos.');
        }
    }
    render('admin/login', [], 'publico');
}
function ctl_admin_logout() {
    solo_post(); unset($_SESSION['admin_id'], $_SESSION['admin_last']); session_regenerate_id(true);
    if (post('motivo') === 'inactividad') flash('warning', 'Tu sesión se cerró por inactividad.'); else flash('success', 'Sesión cerrada.');
    redirect(admin_url('/login'));
}
function ctl_cuenta() {
    $u = requiere_admin();
    $p = $u['persona_id'] ? row('SELECT * FROM personas WHERE id=?', [$u['persona_id']]) : null;
    $errCarne = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificar_csrf(); $accion = post('accion', 'password');
        if ($accion === 'carne' && !$p) {
            // El administrador registra sus propios datos y queda con su carnet.
            $doc = preg_replace('/[\s.]/', '', post('numero_documento'));
            if ($doc !== '' && val('SELECT id FROM personas WHERE numero_documento=?', [$doc])) {
                $errCarne['numero_documento'] = 'Esa cédula ya está registrada. Pide a un administrador principal que vincule tu cuenta a ese registro (Usuarios y permisos → Vincular persona).';
            } else {
                $tp = post('tipo_persona'); $tipo = in_array($tp, ['instructor', 'contratista'], true) ? $tp : 'funcionario';
                list($errCarne, $d) = validar_persona(['tipo_persona' => $tipo, 'tipo_documento' => post('tipo_documento', 'CC'), 'numero_documento' => $doc,
                    'nombres' => post('nombres'), 'apellidos' => post('apellidos'), 'fecha_expedicion' => post('fecha_expedicion'),
                    'email' => post('email') !== '' ? post('email') : $u['email'], 'dependencia' => post('dependencia'), 'rh' => post('rh')]);
                if (!$errCarne) {
                    $pid = guardar_persona($d);
                    q('UPDATE usuarios SET persona_id=?, nombre=?, email=? WHERE id=?', [$pid, mb_substr($d['nombres'] . ' ' . $d['apellidos'], 0, 100), $d['email'], $u['id']]);
                    $f = subir_archivo('foto');
                    if ($f) { $e2 = ''; if (!guardar_foto_persona($pid, $f['tmp_name'], $e2)) flash('warning', 'Tu carnet se creó, pero la foto no: ' . $e2); }
                    audit('crear_carne_propio', 'Persona', $pid, $d['nombres'] . ' ' . $d['apellidos']);
                    flash('success', 'Listo: tu carnet quedó creado. Sube tu foto y pide que lo activen para usarlo.'); redirect(admin_url('/mi-carne'));
                }
            }
        } elseif ($accion === 'datos') {
            $usuario = post('usuario'); $email = post('email'); $err = [];
            if (!preg_match('/^[A-Za-z0-9_.-]{3,60}$/', $usuario)) $err[] = 'Usuario: 3 a 60 caracteres (letras, números, punto, guion).';
            elseif (val('SELECT id FROM usuarios WHERE usuario=? AND id<>?', [$usuario, $u['id']])) $err[] = 'Ese nombre de usuario ya existe.';
            if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190)) $err[] = 'Correo electrónico no válido.';
            if ($p) {
                $nombres = post('nombres'); $apellidos = post('apellidos'); $dep = post('dependencia'); $fx = null; $rh = post('rh');
                if (!in_array($rh, GRUPOS_RH, true)) $err[] = 'Elige tu tipo de sangre (RH).';
                if ($nombres === '' || mb_strlen($nombres) > 100) $err[] = 'Indica tus nombres (máx. 100 caracteres).';
                if ($apellidos === '' || mb_strlen($apellidos) > 100) $err[] = 'Indica tus apellidos (máx. 100 caracteres).';
                if ($p['tipo_persona'] !== 'aprendiz' && ($dep === '' || mb_strlen($dep) > 200)) $err[] = 'Indica tu cargo, área o dependencia.';
                list($fx, $ef) = parsear_fecha(post('fecha_expedicion')); if ($ef) $err[] = $ef; elseif (!$fx) $err[] = 'La fecha de expedición es obligatoria.';
                $nombre = trim($nombres . ' ' . $apellidos);
            } else {
                $nombre = post('nombre');
                if ($nombre === '' || mb_strlen($nombre) > 100) $err[] = 'Indica tu nombre (máx. 100 caracteres).';
            }
            if ($err) foreach ($err as $m) flash('error', $m);
            else {
                q('UPDATE usuarios SET usuario=?, nombre=?, email=? WHERE id=?', [$usuario, mb_substr($nombre, 0, 100), $email, $u['id']]);
                if ($p) {
                    q('UPDATE personas SET nombres=?, apellidos=?, email=?, actualizada=? WHERE id=?', [$nombres, $apellidos, $email, now(), $p['id']]);
                    if ($p['tipo_persona'] !== 'aprendiz') q('UPDATE personas SET dependencia=? WHERE id=?', [$dep, $p['id']]);
                    q('UPDATE personas SET fecha_expedicion=?, rh=?, rh_verificado=1 WHERE id=?', [$fx, $rh, $p['id']]);
                    recalcular_registro($p['id']);
                }
                audit('editar_cuenta', 'Usuario', $u['id'], 'Datos personales actualizados');
                flash('success', 'Tus datos se guardaron.'); redirect(admin_url('/cuenta'));
            }
        } else {
            $n = $_POST['nueva'] ?? '';
            if (!password_verify($_POST['actual'] ?? '', $u['password_hash'])) flash('error', 'La contraseña actual no es correcta.');
            elseif (strlen($n) < 10 || ctype_digit($n)) flash('error', 'La nueva contraseña debe tener al menos 10 caracteres y no ser solo números.');
            elseif ($n !== ($_POST['nueva2'] ?? '')) flash('error', 'Las contraseñas nuevas no coinciden.');
            else { q('UPDATE usuarios SET password_hash=? WHERE id=?', [password_hash($n, PASSWORD_DEFAULT), $u['id']]); session_regenerate_id(true); audit('cambio_password', 'Usuario', $u['id']); flash('success', 'Contraseña actualizada.'); redirect(admin_url('/cuenta')); }
        }
        $u = row('SELECT * FROM usuarios WHERE id=?', [$u['id']]);
        $p = $u['persona_id'] ? row('SELECT * FROM personas WHERE id=?', [$u['persona_id']]) : null;
    }
    $accesos = rows("SELECT fecha, detalle FROM historial WHERE usuario_id=? AND accion='login_admin' ORDER BY id DESC LIMIT 5", [$u['id']]);
    $acciones = rows("SELECT fecha, accion, objeto, objeto_id, detalle FROM historial WHERE usuario_id=? AND accion NOT IN ('login_admin','login_admin_fallido') ORDER BY id DESC LIMIT 15", [$u['id']]);
    vista_admin('cuenta', ['u' => $u, 'p' => $p, 'accesos' => $accesos, 'acciones' => $acciones, 'errCarne' => $errCarne]);
}
/** «Mi carnet»: el administrador ve su propio carnet desde el panel. */
function ctl_admin_mi_carne() {
    $u = requiere_admin();
    if (!$u['persona_id']) { flash('info', 'Primero completa tus datos para tener tu carnet.'); redirect(admin_url('/cuenta') . '#carne'); }
    $p = persona($u['persona_id']); if (!$p) error_pagina(404);
    vista_admin('mi_carne', ['p' => $p, 'faltan' => persona_faltantes($p)]);
}
function ctl_admin_mi_foto() {
    $u = requiere_admin(); solo_post();
    if (!$u['persona_id']) redirect(admin_url('/cuenta') . '#carne');
    $f = subir_archivo('foto');
    if (!$f) flash('error', 'Selecciona una imagen.');
    else { $e = ''; if (guardar_foto_persona($u['persona_id'], $f['tmp_name'], $e)) flash('success', 'Tu fotografía quedó guardada.'); else flash('error', $e); }
    redirect(admin_url('/mi-carne'));
}

// ---------- inicio ----------
function ctl_admin_inicio() {
    requiere_admin();
    $ind = [
        'Total de personas' => (int)val('SELECT COUNT(*) FROM personas'),
        'Carnets activos' => (int)val("SELECT COUNT(*) FROM carnes WHERE estado='activo'"),
        'Carnets inactivos' => (int)val("SELECT COUNT(*) FROM carnes WHERE estado='inactivo'"),
        'Sin fotografía' => (int)val("SELECT COUNT(*) FROM personas WHERE foto=''"),
        'Registros incompletos' => (int)val("SELECT COUNT(*) FROM personas WHERE estado_registro='pendiente'"),
        'Total de fichas' => (int)val('SELECT COUNT(*) FROM fichas'),
    ];
    foreach (['fotos', 'institucional', 'importaciones', 'tmp'] as $d) {
        if (!is_writable(APP_ROOT . '/storage/' . $d)) { flash('warning', 'La carpeta storage/' . $d . ' no tiene permiso de escritura: no se podrán guardar archivos. Dale permisos 755 o 775 en el administrador de archivos del hosting.'); break; }
    }
    $faltan = array_filter(['gd' => 'GD (imágenes)', 'zip' => 'Zip (leer Excel)', 'mbstring' => 'mbstring', 'ctype' => 'ctype', 'pdo_mysql' => 'PDO MySQL'], function ($k) { return !extension_loaded($k); }, ARRAY_FILTER_USE_KEY);
    if ($faltan) flash('warning', 'Faltan extensiones de PHP en el hosting: ' . implode(', ', $faltan) . '. Actívalas desde el panel del hosting (Seleccionar versión de PHP → Extensiones).');
    // --- datos de las gráficas ---
    $hoy = strtotime('today'); $dow = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']; $porDia = [];
    foreach (rows('SELECT DATE(fecha) AS d, COUNT(*) AS n FROM accesos_titular WHERE fecha >= ? GROUP BY DATE(fecha)', [date('Y-m-d 00:00:00', $hoy - 6 * 86400)]) as $r) $porDia[$r['d']] = (int)$r['n'];
    $ing = ['labels' => [], 'vals' => []];
    for ($i = 6; $i >= 0; $i--) { $t = $hoy - $i * 86400; $ing['labels'][] = $dow[(int)date('w', $t)] . ' ' . date('d', $t); $ing['vals'][] = $porDia[date('Y-m-d', $t)] ?? 0; }
    $total = $ind['Total de personas'];
    $estados = ['activo' => 0, 'inactivo' => 0, 'pendiente' => 0];
    foreach (rows('SELECT estado, COUNT(*) AS n FROM carnes GROUP BY estado') as $r) $estados[$r['estado']] = (int)$r['n'];
    $tipos = ['aprendiz' => 0, 'instructor' => 0, 'funcionario' => 0, 'contratista' => 0];
    foreach (rows('SELECT tipo_persona, COUNT(*) AS n FROM personas GROUP BY tipo_persona') as $r) $tipos[$r['tipo_persona']] = (int)$r['n'];
    $activaron = (int)val('SELECT COUNT(*) FROM personas WHERE pin_configurado=1');
    $semana = (int)val('SELECT COUNT(DISTINCT persona_id) FROM accesos_titular WHERE fecha >= ?', [date('Y-m-d 00:00:00', $hoy - 6 * 86400)]);
    vista_admin('inicio', ['indicadores' => $ind, 'ing' => $ing, 'estados' => $estados, 'tipos' => $tipos, 'activaron' => $activaron, 'total' => $total,
                           'semana' => $semana, 'alertasFichas' => alertas_fichas(), 'sinCarne' => empty(admin_actual()['persona_id']), 'ingresosSemana' => array_sum($ing['vals']), 'alertas' => count(alertas_sospechosas(true)), 'puedeConfig' => tiene('configuracion')]);
}

// ---------- fichas ----------
function ctl_fichas() {
    requiere_admin('consultar'); $q = get('ficha'); $w = ''; $p = [];
    if ($q !== '') { $w = ' WHERE f.numero LIKE ? OR pr.nombre LIKE ?'; $p = ['%' . $q . '%', '%' . $q . '%']; }
    $pg = paginar('SELECT COUNT(*) FROM fichas f JOIN programas pr ON pr.id=f.programa_id' . $w,
        'SELECT f.*, pr.nombre AS programa, (SELECT COUNT(*) FROM personas x WHERE x.ficha_id=f.id) AS n FROM fichas f JOIN programas pr ON pr.id=f.programa_id' . $w . ' ORDER BY f.numero', $p, 20);
    vista_admin('fichas', ['q' => $q, 'pg' => $pg, 'total' => (int)val('SELECT COUNT(*) FROM fichas')]);
}
function ficha_o_404($id) {
    $f = row('SELECT f.*, pr.nombre AS programa FROM fichas f JOIN programas pr ON pr.id=f.programa_id WHERE f.id=?', [$id]);
    if (!$f) error_pagina(404); return $f;
}
function ctl_ficha_detalle($id) {
    requiere_admin('consultar'); $f = ficha_o_404($id); $filtro = get('filtro');
    $w = ' WHERE p.ficha_id=?';
    $map = ['activos' => " AND c.estado='activo'", 'inactivos' => " AND c.estado='inactivo'", 'sin_foto' => " AND p.foto=''", 'incompletos' => " AND p.estado_registro='pendiente'", 'pendientes' => " AND c.estado='pendiente'"];
    $w .= $map[$filtro] ?? '';
    $base = ' FROM personas p JOIN carnes c ON c.persona_id=p.id' . $w;
    $pg = paginar('SELECT COUNT(*)' . $base, 'SELECT p.*, c.estado AS carne_estado' . $base . ' ORDER BY p.apellidos, p.nombres', [$id]);
    vista_admin('ficha', ['f' => $f, 'pg' => $pg, 'filtro' => $filtro, 'st' => ficha_estadisticas($id)]);
}
/** Conteos de la ficha completa (no dependen del filtro ni de la página). */
function ficha_estadisticas($fid) {
    $st = ['total' => 0, 'activos' => 0, 'inactivos' => 0, 'pendientes' => 0, 'listos' => 0, 'listos_inactivos' => 0, 'incompletos' => 0, 'no_vigentes' => 0];
    foreach (rows('SELECT p.*, c.estado AS carne_estado FROM personas p JOIN carnes c ON c.persona_id=p.id WHERE p.ficha_id=?', [$fid]) as $p) {
        $st['total']++;
        if ($p['carne_estado'] === 'activo') { $st['activos']++; continue; }
        $st[$p['carne_estado'] === 'inactivo' ? 'inactivos' : 'pendientes']++;
        $c = formacion_codigo($p);
        if ($c !== '' && !formacion_vigente($c)) { $st['no_vigentes']++; continue; }   // retiro, cancelado o certificado: no se activa
        if (persona_faltantes($p)) $st['incompletos']++;
        elseif ($p['carne_estado'] === 'inactivo') $st['listos_inactivos']++;
        else $st['listos']++;
    }
    return $st;
}
/** Cambia el estado en la formación de TODOS los aprendices de la ficha (p. ej. pasar la ficha a etapa práctica). */
/** Envía un correo de prueba para comprobar la configuración (solo administradores principales). */
function ctl_correo_prueba() {
    requiere_principal(); solo_post(); verificar_csrf(); $para = trim(post('correo'));
    if (!filter_var($para, FILTER_VALIDATE_EMAIL)) { flash('error', 'Escribe un correo válido para la prueba.'); redirect(admin_url('/configuracion') . '#correo'); }
    list($ok, $err) = enviar_correo($para, 'Prueba de correo · Carnet digital CTTAOA', "Este es un correo de prueba del carnet digital del CTTAOA.\n\nSi lo recibiste, el envío de correos está bien configurado.", '');
    audit('correo_prueba', 'Configuracion', '', $ok ? 'Correo de prueba enviado' : 'Falló: ' . $err);
    flash($ok ? 'success' : 'error', $ok ? 'Correo de prueba enviado a ' . $para . ' (modo ' . correo_cfg()['modo'] . '). Revisa también la carpeta de correo no deseado.' : 'No se pudo enviar el correo: ' . $err, !$ok);
    redirect(admin_url('/configuracion') . '#correo');
}
function ctl_ficha_fechas($id) {
    requiere_admin('editar'); solo_post(); verificar_csrf(); $f = ficha_o_404($id);
    list($ini, $e1) = parsear_fecha(post('fecha_inicio'), true, 'fecha de inicio de la ficha'); list($fin, $e2) = parsear_fecha(post('fecha_fin'), true, 'fecha de fin de la ficha');
    if ($e1 || $e2) { flash('error', 'Escribe fechas válidas.'); redirect(admin_url('/fichas/' . $id)); }
    if ($ini && $fin && $fin < $ini) { flash('error', 'La fecha de fin no puede ser anterior a la de inicio.'); redirect(admin_url('/fichas/' . $id)); }
    ficha_fijar_fechas($id, $ini, $fin);
    audit('ficha_fechas', 'Ficha', $id, 'Ficha ' . $f['numero'] . ': inicio ' . ($ini ?: '—') . ', fin ' . ($fin ?: '—'));
    flash('success', $fin ? 'Fechas de la ficha guardadas.' : 'Fechas quitadas: la ficha ya no tiene fecha de fin.'); redirect(admin_url('/fichas/' . $id));
}
function ctl_ficha_estado_formacion($id) {
    requiere_admin('editar'); solo_post(); $f = ficha_o_404($id); $nuevo = post('estado_formacion');
    if (!isset(ESTADOS_FORMACION[$nuevo])) { flash('error', 'Estado no válido.'); redirect(admin_url('/fichas/' . $id)); }
    $cambiados = $desact = 0;
    foreach (rows("SELECT id FROM personas WHERE ficha_id=? AND tipo_persona='aprendiz'", [$id]) as $r) { list($c, $d) = cambiar_estado_formacion($r['id'], $nuevo); $cambiados += $c ? 1 : 0; $desact += $d ? 1 : 0; }
    audit('estado_formacion_ficha', 'Ficha', $id, 'Ficha ' . $f['numero'] . ' → ' . formacion_etiqueta($nuevo) . ': ' . $cambiados . ' aprendices' . ($desact ? ', ' . $desact . ' carnets desactivados' : ''));
    $msg = $cambiados ? $cambiados . ' aprendiz(ces) pasaron a «' . formacion_etiqueta($nuevo) . '».' : 'Todos ya estaban en «' . formacion_etiqueta($nuevo) . '».';
    if ($desact) $msg .= ' ' . $desact . ' carnet(s) activo(s) se desactivaron.';
    flash($cambiados ? 'success' : 'info', $msg); redirect(admin_url('/fichas/' . $id));
}
/** Activa de una vez todos los carnets COMPLETOS de la ficha. Los incompletos no se tocan. */
function ctl_ficha_activar_todos($id) {
    requiere_admin('estado'); solo_post(); $f = ficha_o_404($id); $a = admin_actual();
    $incluir = post('incluir_inactivos') === '1'; $act = $inc = $omit = $nov = 0;
    $pdo = db(); $pdo->beginTransaction();
    try {
        foreach (rows('SELECT p.*, c.id AS carne_id, c.estado AS carne_estado FROM personas p JOIN carnes c ON c.persona_id=p.id WHERE p.ficha_id=?', [$id]) as $p) {
            if ($p['carne_estado'] === 'activo') continue;
            $cf = formacion_codigo($p);
            if ($cf !== '' && !formacion_vigente($cf)) { $nov++; continue; }   // retiro voluntario, cancelado o certificado
            if ($p['carne_estado'] === 'inactivo' && !$incluir) { $omit++; continue; }
            if (persona_no_activable($p)) { $inc++; continue; }
            q('UPDATE carnes SET estado=?, estado_cambiado=?, estado_cambiado_por=? WHERE id=?', ['activo', now(), $a['id'], $p['carne_id']]); $act++;
        }
        $pdo->commit();
    } catch (Exception $ex) { $pdo->rollBack(); throw $ex; }
    audit('activar_ficha', 'Ficha', $id, 'Ficha ' . $f['numero'] . ': ' . $act . ' activados, ' . $inc . ' incompletos sin activar' . ($omit ? ', ' . $omit . ' desactivados omitidos' : ''));
    $msg = $act . ' carnet(s) activado(s).';
    if ($inc) $msg .= ' ' . $inc . ' no se activaron por datos o foto incompletos: filtra «Datos incompletos» para completarlos.';
    if ($omit) $msg .= ' ' . $omit . ' desactivado(s) se dejaron como están.';
    if ($nov) $msg .= ' ' . $nov . ' no se activaron porque su estado en la formación no es vigente (retiro voluntario, cancelado o certificado).';
    flash($act ? 'success' : 'warning', $act ? $msg : 'No había carnets completos por activar. ' . ($inc ? $inc . ' tienen datos o foto incompletos. ' : '') . ($nov ? $nov . ' no se activaron porque su estado en la formación no es vigente (retiro voluntario, cancelado o certificado).' : ''));
    redirect(admin_url('/fichas/' . $id));
}
/** Desactiva de una vez todos los carnets ACTIVOS de la ficha. */
function ctl_ficha_desactivar_todos($id) {
    requiere_admin('estado'); solo_post(); $f = ficha_o_404($id); $a = admin_actual(); $n = 0;
    $pdo = db(); $pdo->beginTransaction();
    try {
        foreach (rows("SELECT c.id AS carne_id FROM personas p JOIN carnes c ON c.persona_id=p.id WHERE p.ficha_id=? AND c.estado='activo'", [$id]) as $p) {
            q('UPDATE carnes SET estado=?, estado_cambiado=?, estado_cambiado_por=? WHERE id=?', ['inactivo', now(), $a['id'], $p['carne_id']]); $n++;
        }
        $pdo->commit();
    } catch (Exception $ex) { $pdo->rollBack(); throw $ex; }
    audit('desactivar_ficha', 'Ficha', $id, 'Ficha ' . $f['numero'] . ': ' . $n . ' desactivados');
    flash($n ? 'success' : 'warning', $n ? $n . ' carnet(s) desactivado(s).' : 'No había carnets activos en esta ficha.');
    redirect(admin_url('/fichas/' . $id));
}
function ctl_ficha_eliminar($id) {
    requiere_admin('editar'); $f = ficha_o_404($id);
    $integrantes = rows('SELECT p.*, c.estado AS carne_estado FROM personas p JOIN carnes c ON c.persona_id=p.id WHERE p.ficha_id=?', [$id]);
    $activos = count(array_filter($integrantes, function ($p) { return $p['carne_estado'] === 'activo'; }));
    $admins = (int)val('SELECT COUNT(*) FROM usuarios u JOIN personas p ON p.id=u.persona_id WHERE p.ficha_id=?', [$id]);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificar_csrf();
        if ($admins > 0) flash('error', 'No se puede eliminar: hay integrantes con acceso de administrador. Retíraselo primero en Usuarios y permisos.');
        elseif (post('confirmar') !== (string)$f['numero']) flash('error', 'El número escrito no coincide con la ficha. No se eliminó nada.');
        else {
            $docs = array_column($integrantes, 'numero_documento'); $fotos = array_column($integrantes, 'foto'); $pdo = db(); $pdo->beginTransaction();
            try {
                q('DELETE FROM personas WHERE ficha_id=?', [$id]);   // carnes y códigos se borran en cascada
                q('DELETE FROM fichas WHERE id=?', [$id]);
                $pdo->commit();
            } catch (Exception $e) { $pdo->rollBack(); throw $e; }
            foreach ($fotos as $ft) eliminar_archivo_privado($ft);
            audit('eliminar_ficha', 'Ficha', $id, 'Ficha ' . $f['numero'] . ' (' . $f['programa'] . '); ' . count($docs) . ' personas eliminadas: ' . implode(', ', $docs));
            flash('success', 'Ficha ' . $f['numero'] . ' eliminada junto con ' . count($docs) . ' integrante(s).');
            redirect(admin_url('/fichas'));
        }
    }
    vista_admin('ficha_eliminar', ['f' => $f, 'total' => count($integrantes), 'activos' => $activos, 'admins' => $admins]);
}

// ---------- personas ----------
function persona_o_404($id) { $p = persona($id); if (!$p) error_pagina(404); return $p; }
function ctl_personas() {
    requiere_admin('consultar'); $q = get('q'); $tipo = get('tipo', 'todos'); $w = []; $p = [];
    if ($tipo === 'no_aprendices') $w[] = "(p.tipo_persona<>'aprendiz' OR u.id IS NOT NULL)";   // incluye a los administradores
    elseif (in_array($tipo, ['aprendiz', 'instructor', 'funcionario', 'contratista'], true)) { $w[] = 'p.tipo_persona=?'; $p[] = $tipo; }
    if ($q !== '') { $w[] = '(p.numero_documento LIKE ? OR p.nombres LIKE ? OR p.apellidos LIKE ?)'; array_push($p, '%' . $q . '%', '%' . $q . '%', '%' . $q . '%'); }
    $where = $w ? ' WHERE ' . implode(' AND ', $w) : '';
    $base = ' FROM personas p LEFT JOIN fichas f ON f.id=p.ficha_id JOIN carnes c ON c.persona_id=p.id LEFT JOIN usuarios u ON u.persona_id=p.id' . $where;
    $pg = paginar('SELECT COUNT(*)' . $base, 'SELECT p.*, f.numero AS ficha_numero, c.estado AS carne_estado, u.rol AS admin_rol' . $base . ' ORDER BY p.apellidos, p.nombres', $p);
    vista_admin('personas', ['q' => $q, 'tipo' => $tipo, 'pg' => $pg]);
}
function ctl_persona_ver($id) {
    requiere_admin('consultar'); $p = persona_o_404($id);
    vista_admin('persona', ['p' => $p, 'adm' => usuario_de_persona($id)]);
}
function ctl_persona_form($id = null) {
    requiere_admin('editar'); $inst = $id ? persona_o_404($id) : null; $err = []; $v = [];
    if ($inst) $v = $inst + ['ficha_numero' => $inst['ficha_numero'], 'programa_nombre' => $inst['programa_nombre']];
    else $v = ['tipo_persona' => 'aprendiz', 'tipo_documento' => 'CC'];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificar_csrf();
        list($err, $d) = validar_persona($_POST, $id);
        $v = $_POST + $v;
        if (!$err) {
            $antes = $inst ? $inst['nombres'] . ' ' . $inst['apellidos'] . ' / ' . $inst['numero_documento'] : '';
            $nid = guardar_persona($d, $id);
            $f = subir_archivo('foto_nueva');
            if ($f) { $e2 = ''; if (!guardar_foto_persona($nid, $f['tmp_name'], $e2)) flash('warning', 'Persona guardada, pero la foto no: ' . $e2); }
            audit($inst ? 'editar' : 'crear', 'Persona', $nid, $inst ? $antes . ' → ' . $d['nombres'] . ' ' . $d['apellidos'] . ' / ' . $d['numero_documento'] : $d['nombres'] . ' ' . $d['apellidos']);
            flash('success', 'Registro de ' . $d['nombres'] . ' ' . $d['apellidos'] . ' guardado.');
            if (!$inst && !empty($_POST['guardar_otro'])) redirect(admin_url('/personas/nueva'));
            redirect(admin_url('/personas/' . $nid));
        }
    }
    vista_admin('persona_form', ['inst' => $inst, 'v' => $v, 'err' => $err, 'programas' => rows('SELECT nombre FROM programas ORDER BY nombre')]);
}
function ctl_persona_foto($id) {
    requiere_admin('fotos'); solo_post(); persona_o_404($id); $f = subir_archivo('foto');
    if (!$f) flash('error', 'Selecciona una imagen.');
    else { $e = ''; if (guardar_foto_persona($id, $f['tmp_name'], $e)) flash('success', 'Fotografía guardada.'); else flash('error', $e); }
    redirect(admin_url('/personas/' . $id));
}
function ctl_persona_estado($id) {
    requiere_admin('estado'); solo_post(); $p = persona_o_404($id); $nuevo = post('estado');
    if (!in_array($nuevo, ['activo', 'inactivo'], true)) flash('error', 'Estado no válido.');
    elseif ($nuevo === 'activo' && persona_no_activable($p)) flash('error', 'No se puede activar: ' . implode(', ', persona_no_activable($p)) . '.');
    else {
        $a = admin_actual();
        q('UPDATE carnes SET estado=?, estado_cambiado=?, estado_cambiado_por=? WHERE id=?', [$nuevo, now(), $a['id'], $p['carne_id']]);
        audit('estado', 'Carne', $p['carne_id'], $p['carne_estado'] . ' → ' . $nuevo);
        flash('success', 'Carnet ' . ($nuevo === 'activo' ? 'activado' : 'desactivado') . '.');
    }
    redirect(post('volver') === 'mi-carne' ? admin_url('/mi-carne') : admin_url('/personas/' . $id));
}
function ctl_persona_eliminar($id) {
    requiere_admin('editar'); solo_post(); $p = persona_o_404($id);
    if (usuario_de_persona($id)) { flash('error', 'Esta persona tiene acceso de administrador. Un administrador principal debe retirárselo primero (Usuarios y permisos → Quitar acceso).'); redirect(admin_url('/personas/' . $id)); }
    if ($p['carne_estado'] === 'activo') { flash('error', 'No se elimina una persona con carnet activo. Desactívalo primero.'); redirect(admin_url('/personas/' . $id)); }
    audit('eliminar', 'Persona', $id, persona_nombre($p) . ' / ' . $p['numero_documento']);
    q('DELETE FROM personas WHERE id=?', [$id]); eliminar_archivo_privado($p['foto']);
    flash('success', 'Registro de ' . persona_nombre($p) . ' eliminado.'); redirect(admin_url('/fichas'));
}

// ---------- importación ----------
function ctl_importar() {
    requiere_admin('importar');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificar_csrf(); $f = subir_archivo('archivo');
        if (!$f || !preg_match('/\.xlsx$/i', $f['name']) || $f['size'] > cfg('max_excel_bytes')) flash('error', 'Solo se aceptan archivos .xlsx de máximo 5 MB.');
        else {
            $rel = 'importaciones/' . bin2hex(random_bytes(16)) . '.xlsx'; move_uploaded_file($f['tmp_name'], APP_ROOT . '/storage/' . $rel);
            try { xlsx_hojas(APP_ROOT . '/storage/' . $rel); }
            catch (Exception $e) { eliminar_archivo_privado($rel); flash('error', 'El archivo no es un Excel válido.'); redirect(admin_url('/importar')); }
            $a = admin_actual();
            $id = insert('importaciones', ['archivo' => $rel, 'nombre_original' => mb_substr(basename($f['name']), 0, 200), 'estado' => 'cargado', 'usuario_id' => $a['id'], 'creada' => now()]);
            redirect(admin_url('/importar/' . $id));
        }
    }
    vista_admin('importar', ['historial' => rows('SELECT * FROM importaciones ORDER BY id DESC LIMIT 10')]);
}
function ctl_importar_plantilla() {
    requiere_admin('importar');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="plantilla_importacion_cttaoa.xlsx"');
    readfile(APP_ROOT . '/app/data/plantilla_importacion_cttaoa.xlsx'); exit;
}
function ctl_importar_detalle($id) {
    requiere_admin('importar'); $imp = row('SELECT * FROM importaciones WHERE id=?', [$id]); if (!$imp) error_pagina(404);
    $ruta = APP_ROOT . '/storage/' . $imp['archivo'];
    if ($imp['estado'] !== 'cargado') { vista_admin('importar_detalle', ['imp' => $imp, 'resultado' => $imp['informe'], 'hojas' => [], 'hoja' => '', 'cols' => [], 'sel' => [], 'campos' => []]); return; }
    $hojas = xlsx_hojas($ruta); $hoja = post('hoja') ?: get('hoja') ?: $hojas[0]; if (!in_array($hoja, $hojas, true)) $hoja = $hojas[0];
    list($cols) = imp_leer($ruta, $hoja); $sel = imp_mapeo_auto($cols); $filas = null; $res = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificar_csrf(); $accion = post('accion');
        if ($accion === 'cancelar') { q("UPDATE importaciones SET estado='cancelado' WHERE id=?", [$id]); eliminar_archivo_privado($imp['archivo']); flash('info', 'Importación cancelada.'); redirect(admin_url('/importar')); }
        if ($accion !== 'recargar') {
            $mapeo = []; foreach (array_keys(CAMPOS_IMPORT) as $c) $mapeo[$c] = post('map_' . $c);
            $sel = array_filter($mapeo, function ($x) { return $x !== ''; });
            $act = []; foreach ($_POST as $k => $v) if (strpos($k, 'decision_') === 0 && $v === 'actualizar' && ctype_digit(substr($k, 9))) $act[] = (int)substr($k, 9);
            list($filas, $res) = imp_analizar($ruta, $hoja, $mapeo);
            if (isset($res['fallo'])) { flash('error', $res['fallo']); $filas = $res = null; }
            else {
                foreach ($filas as &$f) $f['decision'] = in_array($f['fila'], $act, true) ? 'actualizar' : 'omitir'; unset($f);
                if ($accion === 'confirmar') {
                    if ($res['error']) flash('error', 'Hay filas con error. Corrige el archivo y vuelve a cargarlo; no se guardó nada.');
                    else {
                        $r = imp_aplicar($filas, post('actualizar') === '1', $act);
                        $inf = json_encode($r + ['nuevo' => $res['nuevo'], 'total' => $res['total'], 'fichas' => $res['fichas']], JSON_UNESCAPED_UNICODE);
                        q("UPDATE importaciones SET estado='confirmado', hoja=?, informe=? WHERE id=?", [$hoja, $inf, $id]);
                        audit('importar', 'Importacion', $id, $inf);
                        flash('success', 'Importación completada: ' . $r['creados'] . ' creados, ' . $r['actualizados'] . ' actualizados, ' . $r['omitidos'] . ' omitidos (ya registrados).');
                        redirect(admin_url('/importar/' . $id));
                    }
                }
            }
        }
    }
    $campos = []; foreach (array_keys(CAMPOS_IMPORT) as $c) $campos[] = [$c, isset($sel[$c]) ? (string)$sel[$c] : ''];
    vista_admin('importar_detalle', ['imp' => $imp, 'resultado' => null, 'hojas' => $hojas, 'hoja' => $hoja, 'cols' => $cols, 'campos' => $campos, 'filas' => $filas, 'res' => $res]);
}

// ---------- fotos masivas ----------
function ctl_fotos_masivas() {
    requiere_admin('fotos'); $items = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificar_csrf(); $sesion = $_SESSION['fotos_tmp'] ?? [];
        if (post('accion') === 'confirmar') {
            $ok = $fallos = 0; $marcados = (array)($_POST['aplicar'] ?? []);
            foreach ($sesion as $it) {
                $ruta = APP_ROOT . '/storage/' . $it['tmp'];
                if (in_array($it['tmp'], $marcados, true) && $it['persona'] && is_file($ruta)) { $e = ''; if (guardar_foto_persona($it['persona'], $ruta, $e)) $ok++; else $fallos++; }
                eliminar_archivo_privado($it['tmp']);
            }
            $_SESSION['fotos_tmp'] = [];
            audit('fotos_masivas', 'Persona', '', $ok . ' asociadas, ' . $fallos . ' con error');
            flash('success', 'Carga masiva: ' . $ok . ' fotografías asociadas, ' . $fallos . ' con error.'); redirect(admin_url('/fotos'));
        }
        foreach ($sesion as $it) eliminar_archivo_privado($it['tmp']);
        $items = []; $vistos = []; $guardar = [];
        $arch = $_FILES['fotos'] ?? null;
        if ($arch && is_array($arch['name'])) for ($i = 0; $i < count($arch['name']) && $i < 300; $i++) {
            if ($arch['error'][$i] !== UPLOAD_ERR_OK || !is_uploaded_file($arch['tmp_name'][$i])) continue;
            $stem = str_replace([' ', '.'], '', pathinfo($arch['name'][$i], PATHINFO_FILENAME));
            $it = ['archivo' => mb_substr(basename($arch['name'][$i]), 0, 100), 'documento' => $stem, 'persona' => null, 'nombre' => '', 'estado' => '', 'tmp' => null];
            $err = ''; $img = cargar_imagen($arch['tmp_name'][$i], $err);
            if (!$img) { $it['estado'] = 'Inválida: ' . $err; $items[] = $it; continue; }
            if (isset($vistos[$stem])) $it['estado'] = 'Duplicada en el lote';
            else {
                $vistos[$stem] = 1; $m = rows('SELECT id, nombres, apellidos FROM personas WHERE numero_documento=? LIMIT 2', [$stem]);
                if (!$m) $it['estado'] = 'Sin coincidencia';
                elseif (count($m) > 1) $it['estado'] = 'Ambigua (varias personas con ese documento)';
                else { $it['persona'] = (int)$m[0]['id']; $it['nombre'] = $m[0]['nombres'] . ' ' . $m[0]['apellidos']; $it['estado'] = 'Coincide';
                       $it['tmp'] = 'tmp/' . bin2hex(random_bytes(16)) . '.img'; move_uploaded_file($arch['tmp_name'][$i], APP_ROOT . '/storage/' . $it['tmp']); }
            }
            $items[] = $it;
        }
        $_SESSION['fotos_tmp'] = array_values(array_filter($items, function ($x) { return $x['tmp']; }));
    }
    vista_admin('fotos', ['items' => $items]);
}

// ---------- plantilla ----------
function ctl_plantilla_admin() {
    requiere_admin('plantilla'); $pl = plantilla_actual(); $err = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificar_csrf();
        $d = ['regional' => post('regional'), 'complejo' => post('complejo'), 'texto_reverso' => post('texto_reverso'), 'etiqueta_firma' => post('etiqueta_firma'), 'texto_perdida' => post('texto_perdida')];
        foreach (['regional' => 100, 'complejo' => 200, 'etiqueta_firma' => 60] as $k => $max) if ($d[$k] === '' || mb_strlen($d[$k]) > $max) $err[] = $k . ': obligatorio (máx. ' . $max . ' caracteres).';
        if ($d['texto_reverso'] === '' || mb_strlen($d['texto_reverso']) > 1500) $err[] = 'texto_reverso: obligatorio (máx. 1500 caracteres).';
        if (mb_strlen($d['texto_perdida']) > 600) $err[] = 'texto_perdida: máximo 600 caracteres.';
        $nuevos = [];
        foreach (['logo', 'firma'] as $campo) {
            $f = subir_archivo($campo);
            if ($f === false) $err[] = $campo . ': error al subir el archivo.';
            elseif ($f) { $e = ''; $rel = procesar_logo($f['tmp_name'], $e); if ($rel) $nuevos[$campo] = $rel; else $err[] = $campo . ': ' . $e; }
        }
        if ($err) foreach ($nuevos as $rel) eliminar_archivo_privado($rel);
        else {
            foreach ($nuevos as $campo => $rel) { eliminar_archivo_privado($pl[$campo]); $d[$campo] = $rel; }
            $d['actualizada'] = now(); update('plantilla', $d, $pl['id']);
            audit('plantilla', 'Plantilla', $pl['id'], 'Plantilla actualizada');
            flash('success', 'Plantilla guardada. Los carnets nuevos y existentes la usan desde ahora.'); redirect(admin_url('/plantilla'));
        }
        $pl = $d + $pl;
    }
    $demo = ['tipo_persona' => 'aprendiz', 'tipo_documento' => 'TI', 'numero_documento' => '1000000001', 'nombres' => 'Nombre', 'apellidos' => 'Apellido Demostración', 'rh' => 'O+', 'rh_verificado' => 1,
             'dependencia' => '', 'foto' => '', 'ficha_id' => 1, 'ficha_numero' => '0000000', 'programa_nombre' => 'Tecnología en Programa de Ejemplo', 'carne_estado' => 'activo', 'id' => 0];
    vista_admin('plantilla', ['pl' => $pl, 'demo' => $demo, 'err' => $err]);
}

// ---------- usuarios (solo administradores principales) ----------
function ctl_usuarios_listar() {
    return rows("SELECT u.*, p.nombres, p.apellidos, p.numero_documento, p.tipo_persona FROM usuarios u LEFT JOIN personas p ON p.id=u.persona_id ORDER BY FIELD(u.rol,'principal','admin'), u.usuario");
}
/** Devuelve el id de una persona existente, o ['nueva' => datos validados] para crearla. */
function resolver_persona_admin($in, &$err) {
    $doc = preg_replace('/[\s.]/', '', (string)($in['cedula'] ?? ''));
    if ($doc === '' || !ctype_digit($doc)) { $err[] = 'Escribe una cédula válida (solo números).'; return null; }
    $p = row('SELECT * FROM personas WHERE numero_documento=?', [$doc]);
    if ($p) {
        if (usuario_de_persona($p['id'])) { $err[] = 'Esa persona ya tiene un acceso de administrador.'; return null; }
        return (int)$p['id'];
    }
    list($e, $d) = validar_persona(['tipo_persona' => 'funcionario', 'tipo_documento' => $in['tipo_documento'] ?? 'CC', 'numero_documento' => $doc, 'nombres' => $in['nombres'] ?? '',
        'apellidos' => $in['apellidos'] ?? '', 'fecha_expedicion' => $in['fecha_expedicion'] ?? '', 'email' => $in['email'] ?? '', 'dependencia' => $in['dependencia'] ?? '', 'rh' => $in['rh'] ?? '']);
    if ($e) { foreach ($e as $m) $err[] = $m; return null; }
    return ['nueva' => $d];
}
function persona_para_admin($ref) {
    if (is_array($ref)) {
        $pid = guardar_persona($ref['nueva']);
        $f = subir_archivo('foto_nueva');
        if ($f) { $e = ''; if (!guardar_foto_persona($pid, $f['tmp_name'], $e)) flash('warning', 'Persona creada, pero la foto no: ' . $e); }
        audit('crear', 'Persona', $pid, $ref['nueva']['nombres'] . ' ' . $ref['nueva']['apellidos'] . ' (administrador)');
        return $pid;
    }
    return $ref;
}
function ctl_usuarios() {
    requiere_principal(); $err = []; $cedula = preg_replace('/[\s.]/', '', $_SERVER['REQUEST_METHOD'] === 'POST' ? post('cedula') : get('cedula'));
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificar_csrf(); $us = post('usuario'); $pw = $_POST['password'] ?? ''; $rol = post('rol') === 'principal' ? 'principal' : 'admin';
        if (!preg_match('/^[A-Za-z0-9_.-]{3,60}$/', $us)) $err[] = 'Usuario: 3 a 60 caracteres (letras, números, punto, guion).';
        elseif (val('SELECT id FROM usuarios WHERE usuario=?', [$us])) $err[] = 'Ese usuario ya existe.';
        if (strlen($pw) < 10 || ctype_digit($pw)) $err[] = 'La contraseña debe tener al menos 10 caracteres y no ser solo números.';
        $ref = resolver_persona_admin($_POST, $err);
        if (!$err) {
            $perm = implode(',', array_values(array_intersect((array)($_POST['permisos'] ?? []), array_keys(PERMISOS))));
            $pid = persona_para_admin($ref);
            $nid = insert('usuarios', ['usuario' => $us, 'nombre' => mb_substr(persona_nombre(persona($pid)), 0, 100), 'persona_id' => $pid, 'password_hash' => password_hash($pw, PASSWORD_DEFAULT),
                                       'rol' => $rol, 'permisos' => $rol === 'admin' ? $perm : '', 'activo' => 1, 'creado' => now()]);
            audit('crear_admin', 'Usuario', $nid, $us . ' (' . $rol . '): ' . $perm); flash('success', 'Administrador creado. Su carnet quedó registrado en «Personas».'); redirect(admin_url('/usuarios'));
        }
    }
    $encontrada = $cedula !== '' ? row('SELECT * FROM personas WHERE numero_documento=?', [$cedula]) : null;
    vista_admin('usuarios', ['admins' => ctl_usuarios_listar(), 'err' => $err, 'cedula' => $cedula, 'encontrada' => $encontrada,
                             'yaAdmin' => $encontrada ? usuario_de_persona($encontrada['id']) : null]);
}
function ctl_usuario_vincular($id) {
    requiere_principal(); $u = row('SELECT * FROM usuarios WHERE id=?', [$id]); if (!$u) error_pagina(404);
    if ($u['persona_id']) { flash('info', 'Ese usuario ya tiene una persona vinculada.'); redirect(admin_url('/usuarios')); }
    $err = []; $cedula = preg_replace('/[\s.]/', '', $_SERVER['REQUEST_METHOD'] === 'POST' ? post('cedula') : get('cedula'));
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificar_csrf(); $ref = resolver_persona_admin($_POST, $err);
        if (!$err) {
            $pid = persona_para_admin($ref);
            q('UPDATE usuarios SET persona_id=?, nombre=? WHERE id=?', [$pid, mb_substr(persona_nombre(persona($pid)), 0, 100), $id]);
            audit('vincular_admin', 'Usuario', $id, $u['usuario'] . ' ↔ persona ' . $pid); flash('success', 'Persona vinculada. Ya aparece en «Personas».'); redirect(admin_url('/usuarios'));
        }
    }
    $encontrada = $cedula !== '' ? row('SELECT * FROM personas WHERE numero_documento=?', [$cedula]) : null;
    vista_admin('usuario_vincular', ['u' => $u, 'err' => $err, 'cedula' => $cedula, 'encontrada' => $encontrada, 'yaAdmin' => $encontrada ? usuario_de_persona($encontrada['id']) : null]);
}
function ctl_usuario_permisos($id) {
    $yo = requiere_principal(); solo_post();
    $u = row('SELECT * FROM usuarios WHERE id=?', [$id]); if (!$u) error_pagina(404);
    if ((int)$u['id'] === (int)$yo['id']) { flash('error', 'No puedes cambiar tu propia cuenta desde aquí. Usa «Mi cuenta».'); redirect(admin_url('/usuarios')); }
    $perm = implode(',', array_values(array_intersect((array)($_POST['permisos'] ?? []), array_keys(PERMISOS))));
    $activo = post('activo') === '1' ? 1 : 0; $accion = post('accion', 'guardar');
    if (($accion === 'principal' || post('rol') === 'principal') && $u['rol'] === 'admin') {
        q("UPDATE usuarios SET rol='principal', permisos='', activo=1 WHERE id=?", [$id]); audit('promover_principal', 'Usuario', $id, $u['usuario']);
        flash('success', $u['usuario'] . ' ahora es administrador principal.');
    } elseif ($accion === 'autorizado' && $u['rol'] === 'principal') {
        q("UPDATE usuarios SET rol='admin', permisos=?, activo=? WHERE id=?", [$perm, $activo, $id]); audit('degradar_principal', 'Usuario', $id, $u['usuario'] . ': ' . $perm);
        flash('success', $u['usuario'] . ' pasó a administrador autorizado con los permisos marcados.');
    } elseif ($u['rol'] === 'admin') {
        q('UPDATE usuarios SET permisos=?, activo=? WHERE id=?', [$perm, $activo, $id]); audit('permisos', 'Usuario', $id, $perm . ($activo ? '' : ' (cuenta desactivada)'));
        flash('success', 'Permisos actualizados.');
    } else {
        q('UPDATE usuarios SET activo=? WHERE id=?', [$activo, $id]); audit('estado_cuenta', 'Usuario', $id, $u['usuario'] . ($activo ? ': activada' : ': desactivada'));
        flash('success', $activo ? 'Cuenta activada.' : 'Cuenta desactivada: ya no puede entrar al panel.');
    }
    redirect(admin_url('/usuarios'));
}
function ctl_usuario_quitar($id) {
    $yo = requiere_principal(); solo_post();
    $u = row('SELECT * FROM usuarios WHERE id=?', [$id]); if (!$u) error_pagina(404);
    if ((int)$u['id'] === (int)$yo['id']) { flash('error', 'No puedes quitarte el acceso a ti mismo.'); redirect(admin_url('/usuarios')); }
    q('DELETE FROM usuarios WHERE id=?', [$id]); audit('quitar_admin', 'Usuario', $id, $u['usuario'] . ' (' . $u['rol'] . ')');
    flash('success', 'Acceso de administrador retirado. La persona y su carnet se conservan.'); redirect(admin_url('/usuarios'));
}

// ---------- historial y configuración ----------
/** El administrador descarga el carnet de una persona en PDF (se abre con el número de documento de esa persona). */
function ctl_persona_pdf($id) {
    requiere_admin('consultar'); $p = persona_o_404($id);
    audit('descarga_pdf', 'Persona', $id, 'Descarga del carnet en PDF (administrador)');
    pdf_enviar_carne($p);
}
/** Consultas hechas en la página pública /verificar (quién consultó qué documento y cuándo). */
function ctl_consultas() {
    requiere_admin('historial');
    $hoy = date('Y-m-d 00:00:00'); $sem = date('Y-m-d 00:00:00', time() - 6 * 86400);
    $res = ['hoy' => (int)val('SELECT COUNT(*) FROM consultas_verificacion WHERE fecha >= ?', [$hoy]), 'semana' => (int)val('SELECT COUNT(*) FROM consultas_verificacion WHERE fecha >= ?', [$sem]),
            'no_existe' => (int)val("SELECT COUNT(*) FROM consultas_verificacion WHERE resultado='no_existe' AND fecha >= ?", [$sem])];
    $pg = paginar('SELECT COUNT(*) FROM consultas_verificacion', 'SELECT c.*, p.nombres, p.apellidos FROM consultas_verificacion c LEFT JOIN personas p ON p.id=c.persona_id ORDER BY c.id DESC', [], 40);
    vista_admin('consultas', ['pg' => $pg, 'res' => $res]);
}
function ctl_historial() {
    requiere_admin('historial'); $w = ''; $p = [];
    if (get('objeto') !== '') { $w = ' WHERE objeto=? AND objeto_id=?'; $p = [get('objeto'), get('id')]; }
    $pg = paginar('SELECT COUNT(*) FROM historial' . $w, 'SELECT * FROM historial' . $w . ' ORDER BY id DESC', $p, 40);
    vista_admin('historial', ['pg' => $pg, 'dias' => (int)config_db()['historial_dias']]);
}
function ctl_configuracion() {
    requiere_admin('configuracion'); $cfg = config_db(); $err = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificar_csrf();
        $campos = ['max_intentos_pin' => [1, 10], 'bloqueo_minutos' => [1, 1440], 'recuperacion_minutos' => [5, 1440], 'historial_dias' => [1, 365], 'alerta_fallos' => [1, 20], 'copia_horas' => [1, 168]];
        $v = [];
        foreach ($campos as $k => $lim) {
            $x = post($k); $v[$k] = $x;
            if (!ctype_digit($x) || (int)$x < $lim[0] || (int)$x > $lim[1]) $err[$k] = 'Debe ser un número entre ' . $lim[0] . ' y ' . $lim[1] . '.';
        }
        $v['bloqueo_manual'] = post('bloqueo_manual') === '1' ? 1 : 0; $v['alertas_activas'] = post('alertas_activas') === '1' ? 1 : 0;
        if (!$err) {
            $adm = admin_actual();
            q('UPDATE configuracion SET max_intentos_pin=?, bloqueo_minutos=?, recuperacion_minutos=?, historial_dias=?, alerta_fallos=?, copia_horas=?, bloqueo_manual=?, alertas_activas=?, actualizada=?, actualizada_por=? WHERE id=1',
              [(int)$v['max_intentos_pin'], (int)$v['bloqueo_minutos'], (int)$v['recuperacion_minutos'], (int)$v['historial_dias'], (int)$v['alerta_fallos'], (int)$v['copia_horas'], $v['bloqueo_manual'], $v['alertas_activas'], now(), $adm['id']]);
            $cambios = []; foreach ($v as $k => $x) if ((string)$cfg[$k] !== (string)$x) $cambios[] = "$k {$cfg[$k]}→$x";
            audit('configuracion', 'Configuracion', 1, $cambios ? implode('; ', $cambios) : 'sin cambios');
            flash('success', 'Configuración guardada. Se aplica de inmediato.'); redirect(admin_url('/configuracion'));
        }
        $cfg = $v + $cfg;
    }
    $bloq = rows('SELECT * FROM intentos_acceso WHERE bloqueado_hasta > ? ORDER BY bloqueado_hasta DESC', [now()]);
    foreach ($bloq as &$b) {
        $b['segundos'] = max(0, strtotime($b['bloqueado_hasta']) - time()); $b['manual'] = intento_manual($b); $b['nombre'] = '';
        $b['nivel'] = $b['tipo'] === 'titular' ? max(1, intdiv((int)$b['fallos'], max(1, (int)$cfg['max_intentos_pin']))) : 0;
        if ($b['tipo'] === 'titular') { $pn = row('SELECT nombres, apellidos FROM personas WHERE numero_documento=?', [$b['etiqueta']]); if ($pn) $b['nombre'] = $pn['nombres'] . ' ' . $pn['apellidos']; }
    } unset($b);
    vista_admin('configuracion', ['cfg' => $cfg, 'err' => $err, 'bloqueadas' => $bloq, 'copia' => copia_info(), 'alertasFichas' => alertas_fichas(), 'alertas' => alertas_sospechosas()]);
}
function ctl_config_copia() {
    requiere_admin('configuracion'); solo_post();
    try { $b = crear_copia(); audit('copia_seguridad', 'Base de datos', '', 'Copia manual de ' . round($b / 1024) . ' KB'); flash('success', 'Copia de seguridad creada (' . round($b / 1024) . ' KB). Reemplazó a la anterior.'); }
    catch (Throwable $e) { flash('error', 'No se pudo crear la copia: ' . $e->getMessage()); }
    redirect(admin_url('/configuracion'));
}
function ctl_config_descargar() {
    requiere_admin('configuracion'); $i = copia_info(); if (!$i['existe']) error_pagina(404, 'Todavía no hay una copia de seguridad.');
    audit('descarga_copia', 'Base de datos', '', 'Descarga de la copia del ' . $i['fecha']);
    header('Content-Type: application/sql'); header('Content-Disposition: attachment; filename="copia_cttaoa_' . date('Ymd_Hi', strtotime($i['fecha'])) . '.sql"'); header('Content-Length: ' . $i['bytes']);
    readfile(ruta_copia()); exit;
}
function ctl_config_alertas_vistas() {
    requiere_admin('configuracion'); solo_post();
    evento_seguridad('alertas_vistas', 'revisadas por ' . admin_actual()['usuario']);
    audit('alertas_revisadas', 'Configuracion', 1, 'Alertas marcadas como revisadas');
    redirect(admin_url('/configuracion#alertas'));
}
function ctl_config_desbloquear() {
    requiere_admin('configuracion'); solo_post(); $r = row('SELECT * FROM intentos_acceso WHERE clave=?', [post('clave')]);
    if (!$r) flash('error', 'No se encontró ese bloqueo.');
    else { intento_limpiar($r); audit('desbloquear', 'Acceso', '', $r['tipo'] . ': ' . $r['etiqueta']); flash('success', 'Desbloqueado: ' . $r['etiqueta'] . '. Ya puede volver a intentarlo.'); }
    redirect(admin_url('/configuracion'));
}
