<?php
// Controladores públicos (titular).
const COOKIE_DISP = 'cttaoa_disp';
const VIDA_DISP = 15552000;      // 180 días
const VIDA_FLUJO_PIN = 600;      // 10 min
const GENERICO = 'Los datos no coinciden con el registro. Verifica e intenta de nuevo, o comunícate con el administrador del complejo.';

function plantilla_actual() { return row('SELECT * FROM plantilla ORDER BY id DESC LIMIT 1'); }
function carne_html($p, $opts = []) {
    $pl = plantilla_actual();
    ob_start();
    $chica = !empty($opts['chica']); $demo = !empty($opts['demo']);
    $barcode = barcode_svg($p['numero_documento']); $borrador = isset($opts['borrador']) ? $opts['borrador'] : carne_es_borrador($p);
    include APP_ROOT . '/app/views/partials/carne.php';
    return ob_get_clean();
}

// --- vinculación del dispositivo (cookie firmada) ---
function disp_poner($pid) {
    $payload = $pid . '.' . time();
    $v = $payload . '.' . hash_hmac('sha256', $payload, cfg('secret_key'));
    setcookie(COOKIE_DISP, $v, ['expires' => time() + VIDA_DISP, 'path' => base_path() ?: '/', 'secure' => es_https(), 'httponly' => true, 'samesite' => 'Lax']);
}
function disp_olvidar() { setcookie(COOKIE_DISP, '', ['expires' => time() - 3600, 'path' => base_path() ?: '/', 'secure' => es_https(), 'httponly' => true, 'samesite' => 'Lax']); }
function disp_leer() {
    $p = explode('.', $_COOKIE[COOKIE_DISP] ?? '');
    if (count($p) !== 3) return null;
    if (!hash_equals(hash_hmac('sha256', $p[0] . '.' . $p[1], cfg('secret_key')), $p[2])) return null;
    if (time() - (int)$p[1] > VIDA_DISP) return null;
    return row('SELECT * FROM personas WHERE id=? AND pin_configurado=1', [(int)$p[0]]);
}
function iniciar_sesion_titular($p) {
    session_regenerate_id(true);
    $_SESSION['titular_id'] = (int)$p['id']; $_SESSION['titular_last'] = time();
}
function msg_bloqueo($r) {
    if (intento_manual($r)) return 'Tu acceso está bloqueado por seguridad. Comunícate con el administrador del complejo para que lo desbloquee.';
    return 'Demasiados intentos. Intenta de nuevo en ' . texto_duracion(intento_minutos($r)) . '. Si necesitas ayuda, comunícate con el administrador del complejo.';
}
function enmascarar_doc($d) { return strlen($d) > 4 ? '****' . substr($d, -4) : '****'; }
/** Si la persona tiene una cuenta de administrador ACTIVA, devuelve su rol y permisos (para mostrarle el acceso al panel). */
function acceso_admin_de_persona($pid) { return row('SELECT rol, permisos FROM usuarios WHERE persona_id=? AND activo=1', [$pid]); }
function acceso_admin_actual() { $t = titular_actual(); return $t ? acceso_admin_de_persona($t['id']) : null; }
function acceso_admin_texto($u) {
    if ($u['rol'] === 'principal') return 'Administrador principal: tienes acceso a todas las funciones del panel.';
    $lista = []; foreach (array_filter(explode(',', (string)$u['permisos'])) as $k) $lista[] = PERMISOS[$k] ?? $k;
    return 'Administrador autorizado: ' . ($lista ? 'en el panel verás solo lo que puedes hacer (' . implode(', ', $lista) . ').' : 'aún no tienes permisos asignados.');
}
function titular_tiene_correo($p) { return trim((string)($p['email'] ?? '')) !== ''; }
function titular_acepto($p) { return ($p['terminos_version'] ?? '') === cfg('politica_version', '1.0'); }
/** Guarda la prueba de la autorización (fecha, versión e IP). */
function registrar_aceptacion($pid, $doc = '') {
    q('UPDATE personas SET terminos_fecha=?, terminos_version=?, terminos_ip=? WHERE id=?', [now(), cfg('politica_version', '1.0'), client_ip(), $pid]);
    audit('aceptar_terminos', 'Persona', $pid, 'Versión ' . cfg('politica_version', '1.0') . ' · IP ' . client_ip(), null, 'titular:' . enmascarar_doc($doc));
}
function registrar_acceso_titular($pid) { try { q('INSERT INTO accesos_titular (persona_id, fecha) VALUES (?,?)', [$pid, now()]); } catch (Exception $e) { } }
function dummy_verify($pin) { static $h = null; if ($h === null) $h = password_hash('dummy-' . bin2hex(random_bytes(4)), PASSWORD_DEFAULT); password_verify($pin, $h); }

function ctl_inicio() {
    if (titular_actual()) redirect(url('/mi-carne'));
    $recordada = disp_leer(); $tab = $recordada ? 'pin' : 'activar';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificar_csrf();
        $accion = post('accion', 'pin'); if (!in_array($accion, ['pin', 'activar', 'olvide'], true)) $accion = 'pin';
        $tab = $accion;
        $doc = preg_replace('/[\s.]/', '', post('documento'));
        if ($accion === 'pin' && $recordada && $doc === '') $doc = $recordada['numero_documento'];
        if ($accion === 'olvide') {            // no cuenta como intento de PIN; responde siempre igual (no revela si el documento existe)
            if ($doc !== '') solicitar_restablecer_pin($doc);
            flash('info', 'Si el documento está registrado y tiene un correo, te enviamos un enlace para crear un PIN nuevo. Revisa tu bandeja de entrada y la carpeta de correo no deseado. El enlace vence en ' . max(5, (int)config_db()['recuperacion_minutos']) . ' minutos.', true);
            render('public/home', ['recordada' => $recordada, 'tab' => 'olvide'], 'inicio'); return;
        }
        if ($doc === '') { flash('error', GENERICO); render('public/home', ['recordada' => $recordada, 'tab' => $tab], 'inicio'); return; }
        // El bloqueo es SOLO por número de documento (no por IP): así un WiFi compartido no afecta a nadie.
        $int = intento('pin:' . $doc, 'titular', $doc);
        if (intento_bloqueado($int)) {
            flash('error', msg_bloqueo($int));
        } else {
            $persona = $doc !== '' ? row('SELECT * FROM personas WHERE numero_documento=?', [$doc]) : null;
            if ($accion === 'pin') {
                $pin = post('pin');
                $ok = $persona && $persona['pin_configurado'] && preg_match('/^\d{4}$/', $pin) && password_verify($pin, (string)$persona['pin_hash']);
                if (!$persona || !$persona['pin_configurado']) dummy_verify($pin);
                if ($ok) { intento_limpiar($int); iniciar_sesion_titular($persona); registrar_acceso_titular($persona['id']); disp_poner($persona['id']); redirect(url('/mi-carne')); }
            } elseif ($accion === 'activar') {
                if (post('acepto') !== '1') {          // sin aceptar los términos no se valida (y no cuenta como intento fallido)
                    flash('error', 'Debes aceptar los términos y condiciones y la política de privacidad para continuar.');
                    render('public/home', ['recordada' => $recordada, 'tab' => 'activar'], 'inicio'); return;
                }
                list($fecha) = parsear_fecha(post('fecha_expedicion'));
                if ($persona && $fecha && $persona['fecha_expedicion'] === $fecha) {
                    intento_limpiar($int);
                    if (!$persona['pin_configurado'] || !titular_acepto($persona)) registrar_aceptacion($persona['id'], $doc);
                    if ($persona['pin_configurado']) { flash('info', 'Ya activaste tu acceso. Ingresa con tu PIN. Si lo olvidaste, usa «Olvidé PIN»: te enviaremos un enlace a tu correo.'); redirect(url('/')); }
                    $_SESSION['pin_flow'] = ['pk' => (int)$persona['id'], 'motivo' => 'activacion', 'ts' => time()];
                    redirect(url('/crear-pin'));
                }
            }
            $cfg = config_db();
            evento_seguridad('titular_fallo', 'documento ' . enmascarar_doc($doc) . ' (' . $accion . ')');
            $max = max(1, (int)$cfg['max_intentos_pin']);
            intento_fallo($int, $max, (int)$cfg['bloqueo_minutos'], !empty($cfg['bloqueo_manual']), true);
            $int = intento('pin:' . $doc, 'titular', $doc);
            // Mismo mensaje exista o no el documento (no se revela si está registrado); se informa cuántos intentos quedan.
            $quedan = intentos_restantes($int, $max);
            flash('error', intento_bloqueado($int) ? msg_bloqueo($int) : GENERICO . ' Te quedan ' . $quedan . ' intento' . ($quedan === 1 ? '' : 's') . ' antes de un bloqueo temporal.');
        }
    }
    render('public/home', ['recordada' => $recordada, 'tab' => $tab], 'inicio');
}

function ctl_crear_pin() {
    $flow = $_SESSION['pin_flow'] ?? null;
    if (!$flow || time() - $flow['ts'] > VIDA_FLUJO_PIN) { unset($_SESSION['pin_flow']); flash('error', 'La verificación expiró. Inicia el proceso de nuevo.'); redirect(url('/')); }
    $persona = row('SELECT * FROM personas WHERE id=?', [$flow['pk']]);
    if (!$persona) { unset($_SESSION['pin_flow']); redirect(url('/')); }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificar_csrf();
        $email = trim(post('email')); $err = validar_correo_titular($email, $persona['id']) ?: validar_pin(post('pin'), post('pin2'));
        if ($err) flash('error', $err);
        else {
            q('UPDATE personas SET email=?, actualizada=? WHERE id=?', [$email, now(), $persona['id']]);
            establecer_pin($persona['id'], post('pin'));
            audit('pin_creado', 'Persona', $persona['id'], 'motivo: activacion', null, 'titular:' . $persona['numero_documento']);
            unset($_SESSION['pin_flow']); $persona['email'] = $email;
            iniciar_sesion_titular($persona); registrar_acceso_titular($persona['id']); disp_poner($persona['id']);
            flash('success', 'Tu PIN quedó guardado. La próxima vez ingresa solo con tu PIN; si lo olvidas, te enviaremos un enlace a tu correo.');
            redirect(url('/mi-carne'));
        }
    }
    render('public/crear_pin', ['persona' => $persona, 'email' => trim((string)($_POST['email'] ?? $persona['email']))], 'publico');
}

/** Enlace del correo «Olvidé mi PIN»: la persona elige un PIN nuevo. El administrador nunca ve ni genera PIN ni códigos. */
function ctl_restablecer_pin() {
    $token = (string)($_POST['t'] ?? $_GET['t'] ?? '');
    $r = restablecer_buscar($token);
    if (!$r) { render('public/restablecer_pin', ['valido' => false, 'token' => '', 'persona' => null], 'publico'); return; }
    $persona = row('SELECT * FROM personas WHERE id=?', [$r['persona_id']]);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificar_csrf();
        $err = validar_pin(post('pin'), post('pin2'));
        if ($err) flash('error', $err);
        else {
            establecer_pin($persona['id'], post('pin'));
            q('UPDATE restablecer_pin SET usado=1 WHERE persona_id=? AND usado=0', [$persona['id']]);
            intento_limpiar(intento('pin:' . $persona['numero_documento'], 'titular', $persona['numero_documento']));     // levanta el bloqueo por intentos fallidos
            audit('pin_recuperado', 'Persona', $persona['id'], 'PIN restablecido con el enlace del correo', null, 'titular:' . enmascarar_doc($persona['numero_documento']));
            evento_seguridad('pin_restablecido', 'documento ' . enmascarar_doc($persona['numero_documento']));
            flash('success', 'Listo: tu PIN nuevo quedó guardado. Ingresa con él.'); redirect(url('/'));
        }
    }
    render('public/restablecer_pin', ['valido' => true, 'token' => $token, 'persona' => $persona], 'publico');
}

/** Quien aún no tiene correo (porque lo importó el administrador sin él) debe agregarlo antes de ver su carnet. */
function ctl_agregar_correo() {
    $t = titular_actual(); if (!$t) redirect(url('/'));
    if (!titular_acepto($t)) redirect(url('/aceptar-terminos'));
    if (titular_tiene_correo($t)) redirect(url('/mi-carne'));
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificar_csrf(); $email = trim(post('email')); $err = validar_correo_titular($email, $t['id']);
        if ($err) flash('error', $err);
        else { q('UPDATE personas SET email=?, actualizada=? WHERE id=?', [$email, now(), $t['id']]); audit('correo_agregado', 'Persona', $t['id'], 'El titular agregó su correo', null, 'titular:' . enmascarar_doc($t['numero_documento'])); flash('success', 'Correo guardado.'); redirect(url('/mi-carne')); }
    }
    render('public/agregar_correo', ['email' => trim((string)($_POST['email'] ?? ''))], 'publico');
}

/** Página pública /verificar: vigilantes y empresas consultan por número de documento (escrito o leído con la cámara). */
function ctl_verificar_publico() {
    procesar_fichas_vencidas();
    header('Permissions-Policy: camera=(self), microphone=(), geolocation=()');   // permite la cámara solo en esta página
    header('Cache-Control: no-store, no-cache, must-revalidate'); header('Pragma: no-cache');
    $res = null; $doc = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificar_csrf();
        $doc = preg_replace('/\D/', '', (string)post('documento'));
        if (strlen($doc) < 5 || strlen($doc) > 15) flash('error', 'Escribe un número de documento válido (solo números).');
        else {
            $p = row('SELECT p.*, c.estado AS carne_estado FROM personas p LEFT JOIN carnes c ON c.persona_id=p.id WHERE p.numero_documento=?', [$doc]);
            if ($p) {
                list($ok, $titulo, $detalle) = estado_verificacion($p);
                $res = ['encontrado' => true, 'vigente' => $ok, 'titulo' => $titulo, 'detalle' => $detalle, 'nombre' => persona_nombre($p), 'documento' => $doc];
                $resultado = $ok ? 'activo' : 'no_vigente';
            } else { $res = ['encontrado' => false, 'documento' => $doc]; $resultado = 'no_existe'; }
            try { q('INSERT INTO consultas_verificacion (documento, persona_id, resultado, ip, fecha) VALUES (?,?,?,?,?)', [$doc, $p ? $p['id'] : null, $resultado, client_ip(), now()]); } catch (Exception $e) { }
        }
    }
    render('public/verificar', ['res' => $res, 'doc' => $doc], 'verificar');
}
/** El titular descarga su carnet en PDF (se abre con su número de documento). */
function ctl_mi_carne_pdf() {
    procesar_fichas_vencidas();
    $t = titular_actual(); if (!$t) redirect(url('/'));
    if (!titular_acepto($t)) redirect(url('/aceptar-terminos'));
    if (!titular_tiene_correo($t)) redirect(url('/agregar-correo'));
    $p = persona($t['id']); if (!$p) error_pagina(404);
    audit('descarga_pdf', 'Persona', $p['id'], 'Descarga del carnet en PDF', null, 'titular:' . enmascarar_doc($p['numero_documento']));
    pdf_enviar_carne($p);
}
function ctl_privacidad() { render('public/privacidad', [], 'legal'); }
function ctl_aceptar_terminos() {
    $t = titular_actual(); if (!$t) redirect(url('/'));
    if (titular_acepto($t)) redirect(url('/mi-carne'));
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificar_csrf();
        if (post('acepto') !== '1') flash('error', 'Debes aceptar los términos y condiciones y la política de privacidad para continuar.');
        else { registrar_aceptacion($t['id'], $t['numero_documento']); redirect(url('/mi-carne')); }
    }
    render('public/aceptar_terminos', [], 'publico');
}
function ctl_mi_carne() {
    procesar_fichas_vencidas();
    $t = titular_actual(); if (!$t) redirect(url('/'));
    if (!titular_acepto($t)) redirect(url('/aceptar-terminos'));
    if (!titular_tiene_correo($t)) redirect(url('/agregar-correo'));
    render('public/mi_carne', ['p' => persona($t['id']), 'adm' => acceso_admin_de_persona($t['id'])], 'titular');
}
function ctl_ayuda() { if (!titular_actual()) redirect(url('/')); render('public/ayuda', [], 'titular'); }
function ctl_salir() {
    solo_post();
    unset($_SESSION['titular_id'], $_SESSION['titular_last']); session_regenerate_id(true);
    if (post('motivo') === 'inactividad') flash('warning', 'Tu sesión se cerró por inactividad.'); else flash('success', 'Sesión cerrada.');
    redirect(url('/'));
}
function ctl_cambiar_documento() {
    solo_post();
    unset($_SESSION['titular_id'], $_SESSION['titular_last']); session_regenerate_id(true); disp_olvidar(); redirect(url('/'));
}

// --- archivos privados ---
function enviar_archivo($ruta, $tipo, $cache = 'no-store') {
    if (!is_file($ruta)) error_pagina(404);
    header('Content-Type: ' . $tipo); header('Content-Length: ' . filesize($ruta)); header('Cache-Control: ' . $cache);
    readfile($ruta); exit;
}
function ctl_foto($id) {
    $t = titular_actual(); $propio = $t && (int)$t['id'] === (int)$id;
    if (!$propio && !tiene('consultar')) error_pagina(404);
    $f = val('SELECT foto FROM personas WHERE id=?', [$id]);
    if (!$f || strpos($f, '..') !== false) error_pagina(404);
    enviar_archivo(APP_ROOT . '/storage/' . $f, 'image/jpeg');
}
function ctl_institucional($nombre) {
    if (!in_array($nombre, ['logo', 'firma'], true) || (!titular_actual() && !admin_actual())) error_pagina(404);
    $f = val('SELECT ' . $nombre . ' FROM plantilla ORDER BY id DESC LIMIT 1');
    if (!$f || strpos($f, '..') !== false) error_pagina(404);
    enviar_archivo(APP_ROOT . '/storage/' . $f, 'image/png', 'private, max-age=3600');
}

// --- verificación (pública, sin datos personales) y API ---
function ctl_verificar($token) {
    $c = row('SELECT c.*, p.id AS pid FROM carnes c JOIN personas p ON p.id=c.persona_id WHERE c.token=?', [strtoupper($token)]);
    if (!$c) $res = 'invalido';
    else { $p = persona($c['pid']); $res = ($c['estado'] === 'activo' && !persona_faltantes($p)) ? 'activo' : ($c['estado'] === 'inactivo' ? 'inactivo' : 'pendiente'); }
    q('INSERT INTO eventos_verificacion (carne_id, resultado, fecha) VALUES (?,?,?)', [$c ? $c['id'] : null, $res, now()]);
    $et = ['activo' => 'Carnet activo y válido', 'inactivo' => 'Carnet inactivo', 'pendiente' => 'Carnet no activado', 'invalido' => 'Carnet inexistente o inválido'];
    json_out(['estado' => $res, 'mensaje' => $et[$res]], $res === 'invalido' ? 404 : 200);
}
function ctl_api_mi_carne() {
    procesar_fichas_vencidas();
    $t = titular_actual(); if (!$t) json_out(['detail' => 'No autenticado.'], 401);
    if (!titular_acepto($t)) json_out(['detail' => 'Debes aceptar los términos y condiciones.'], 403);
    if (!titular_tiene_correo($t)) json_out(['detail' => 'Agrega tu correo electrónico para continuar.'], 403);
    $p = persona($t['id']);
    $d = ['estado' => $p['carne_estado'], 'borrador' => carne_es_borrador($p), 'nombre' => persona_nombre($p), 'tipo_persona' => TIPOS_PERSONA[$p['tipo_persona']],
          'programa_o_area' => persona_programa_o_area($p), 'ficha' => $p['ficha_numero'], 'estado_formacion' => formacion_etiqueta(formacion_codigo($p))];
    if ($p['carne_estado'] === 'inactivo') $d['mensaje'] = MSG_INACTIVO;
    json_out($d);
}
function ctl_api_personas() {
    $u = admin_actual(); if (!$u) json_out(['detail' => 'No autenticado.'], 401);
    if (!tiene('consultar')) json_out(['detail' => 'Sin permiso.'], 403);
    $f = get('ficha'); $sql = SQL_PERSONA . ($f !== '' ? ' WHERE f.numero=?' : '') . ' ORDER BY p.apellidos LIMIT 500';
    json_out(array_map(function ($p) { return ['id' => (int)$p['id'], 'nombre' => persona_nombre($p), 'documento' => $p['numero_documento'], 'ficha' => $p['ficha_numero'], 'estado_carne' => $p['carne_estado']]; }, rows($sql, $f !== '' ? [$f] : [])));
}

// Mantiene viva la sesión mientras hay actividad (lo llama el JavaScript). 204 = ok, 401 = sesión vencida.
function ctl_ping() {
    $ok = get('r') === 'admin' ? (bool)admin_actual() : (bool)titular_actual();
    http_response_code($ok ? 204 : 401); exit;
}
