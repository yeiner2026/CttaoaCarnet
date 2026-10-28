<?php
// Núcleo: configuración, base de datos, sesión, CSRF, cabeceras, bloqueos, auditoría.
define('APP_ROOT', dirname(__DIR__));
$GLOBALS['CFG'] = require (getenv('CTTAOA_CONFIG') ?: APP_ROOT . '/config.php');
date_default_timezone_set(cfg('timezone', 'America/Bogota'));
mb_internal_encoding('UTF-8');

function cfg($k, $d = null) { return array_key_exists($k, $GLOBALS['CFG']) ? $GLOBALS['CFG'][$k] : $d; }

// --- compatibilidad PHP 7.4 ---
if (!function_exists('str_starts_with')) { function str_starts_with($h, $n) { return $n === '' || strpos($h, $n) === 0; } }
if (!function_exists('str_contains')) { function str_contains($h, $n) { return $n === '' || strpos($h, $n) !== false; } }

const PERMISOS = [
    'consultar' => 'Consultar carnets', 'editar' => 'Crear y editar registros', 'importar' => 'Importar Excel',
    'fotos' => 'Cargar fotografías', 'plantilla' => 'Modificar plantillas', 'estado' => 'Activar y desactivar carnets',
    'historial' => 'Consultar historiales',
    'configuracion' => 'Modificar configuración de seguridad',
];
const TIPOS_PERSONA = ['aprendiz' => 'APRENDIZ', 'instructor' => 'INSTRUCTOR', 'funcionario' => 'FUNCIONARIO', 'contratista' => 'CONTRATISTA'];
const TIPOS_DOC = ['CC' => 'C.C.', 'TI' => 'T.I.', 'CE' => 'C.E.', 'PPT' => 'PPT', 'PEP' => 'PEP', 'PAS' => 'Pasaporte'];
const GRUPOS_RH = ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];
const ESTADOS_CARNE = ['pendiente' => 'Pendiente de activación', 'activo' => 'Activo', 'inactivo' => 'Inactivo'];
const ESTADOS_REGISTRO = ['pendiente' => 'Pendiente de completar', 'registrado' => 'Registrado', 'retirado' => 'Retirado / archivado'];
const MSG_INACTIVO = 'Tu carnet se encuentra inactivo. Comunícate con el administrador del complejo para obtener más información.';

// --- utilidades ---
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function now() { return date('Y-m-d H:i:s'); }
function es_https() {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
    return cfg('trusted_proxy') && (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}
function client_ip() {
    if (cfg('trusted_proxy') && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $p = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']); return trim($p[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '';
}
function base_path() {
    static $b = null;
    if ($b === null) { $d = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')); $b = rtrim($d, '/'); }
    return $b;
}
function url($p = '/') { return base_path() . $p; }
function admin_url($p = '') { return url('/' . cfg('admin_prefix') . $p); }
function asset($f) { $r = APP_ROOT . '/assets/' . $f; return url('/assets/' . $f) . '?v=' . (is_file($r) ? filemtime($r) : 1); }
function post($k, $d = '') { $v = $_POST[$k] ?? $d; return is_string($v) ? trim($v) : $d; }
function get($k, $d = '') { $v = $_GET[$k] ?? $d; return is_string($v) ? trim($v) : $d; }
function redirect($to) { header('Location: ' . $to); exit; }
function json_out($data, $code = 200) {
    http_response_code($code); header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE); exit;
}
function fecha_legible($d) { return $d ? date('d/m/Y', strtotime($d)) : ''; }
function hora_legible($dt) { return $dt ? date('d/m/Y H:i', strtotime($dt)) : ''; }

// --- base de datos ---
function db() {
    static $pdo = null;
    if ($pdo) return $pdo;
    $dsn = 'mysql:host=' . cfg('db_host') . ';port=' . cfg('db_port', 3306) . ';dbname=' . cfg('db_name') . ';charset=utf8mb4';
    $pdo = new PDO($dsn, cfg('db_user'), cfg('db_pass'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
function q($sql, $p = []) { $s = db()->prepare($sql); $s->execute($p); return $s; }
function rows($sql, $p = []) { return q($sql, $p)->fetchAll(); }
function row($sql, $p = []) { $r = q($sql, $p)->fetch(); return $r === false ? null : $r; }
function val($sql, $p = []) { $r = q($sql, $p)->fetchColumn(); return $r === false ? null : $r; }
function insert($tabla, $datos) {
    $c = array_keys($datos);
    q('INSERT INTO ' . $tabla . ' (' . implode(',', $c) . ') VALUES (' . implode(',', array_fill(0, count($c), '?')) . ')', array_values($datos));
    return (int)db()->lastInsertId();
}
function update($tabla, $datos, $id) {
    $set = implode(',', array_map(function ($c) { return $c . '=?'; }, array_keys($datos)));
    q('UPDATE ' . $tabla . ' SET ' . $set . ' WHERE id=?', array_merge(array_values($datos), [$id]));
}

// --- sesión ---
function iniciar_sesion() {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    ini_set('session.use_strict_mode', '1'); ini_set('session.use_only_cookies', '1');
    session_name('CTTAOASESS');
    session_set_cookie_params(['lifetime' => 0, 'path' => base_path() ?: '/', 'secure' => es_https(), 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
function csrf_token() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field() { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }
function verificar_csrf() {
    // Si el servidor descarta un envío por ser demasiado grande, $_POST llega vacío: no es un problema de sesión.
    if (empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        http_response_code(413);
        render('error', ['codigo' => 413, 'mensaje' => 'Lo que intentas enviar es demasiado grande para este servidor (límite por envío: ' . ini_get('post_max_size') . '; por archivo: ' . ini_get('upload_max_filesize') . '). Sube una imagen más liviana (por ejemplo, menos de 2 MB) o menos archivos a la vez.'], 'publico'); exit;
    }
    $t = $_POST['_csrf'] ?? '';
    if (!is_string($t) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $t)) {
        http_response_code(403); render('error', ['codigo' => 403, 'mensaje' => 'La sesión del formulario expiró. Recarga la página e inténtalo de nuevo.'], 'publico'); exit;
    }
}
function solo_post() { if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit('Método no permitido'); } verificar_csrf(); }
function flash($tipo, $msg, $fijo = false) { $_SESSION['flash'][] = [$tipo, $msg, $fijo]; }
function flashes() { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

// --- vistas ---
function render($__vista, $__datos = [], $__layout = 'publico') {
    extract($__datos);
    $__ruta = APP_ROOT . '/app/views/' . $__vista . '.php';
    ob_start(); include $__ruta; $contenido = ob_get_clean();
    if ($__layout === 'ninguno') { echo $contenido; return; }
    include APP_ROOT . '/app/views/layout_' . $__layout . '.php';
}
function error_pagina($codigo, $mensaje = '') {
    http_response_code($codigo);
    $msg = $mensaje ?: ($codigo === 404 ? 'La página que buscas no existe.' : ($codigo === 403 ? 'No tienes permiso para esta acción.' : 'Error.'));
    render('error', ['codigo' => $codigo, 'mensaje' => $msg], 'publico'); exit;
}

// --- cabeceras de seguridad ---
function cabeceras_seguridad($sensible, $panel) {
    header_remove('X-Powered-By');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; script-src 'self' 'unsafe-inline' 'wasm-unsafe-eval' https://cdn.jsdelivr.net; font-src 'self' https://cdn.jsdelivr.net; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
    header('X-Frame-Options: DENY'); header('X-Content-Type-Options: nosniff'); header('Referrer-Policy: same-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    if (es_https()) header('Strict-Transport-Security: max-age=31536000');
    if ($panel) header('X-Robots-Tag: noindex, nofollow');
    if ($sensible) { header('Cache-Control: no-store, no-cache, must-revalidate'); header('Pragma: no-cache'); }
}

// --- bloqueos por intentos ---
const BLOQUEO_MANUAL_HASTA = '2099-12-31 23:59:59';
function intento($texto, $tipo = '', $etiqueta = '') {
    $clave = hash('sha256', $texto);
    $r = row('SELECT * FROM intentos_acceso WHERE clave=?', [$clave]);
    if (!$r) {
        q('INSERT IGNORE INTO intentos_acceso (clave, tipo, etiqueta, fallos, ultimo) VALUES (?,?,?,0,?)', [$clave, $tipo, mb_substr($etiqueta, 0, 120), now()]);
        $r = row('SELECT * FROM intentos_acceso WHERE clave=?', [$clave]);
    } elseif ($tipo !== '' && $r['tipo'] === '') {
        q('UPDATE intentos_acceso SET tipo=?, etiqueta=? WHERE clave=?', [$tipo, mb_substr($etiqueta, 0, 120), $clave]);
        $r['tipo'] = $tipo; $r['etiqueta'] = $etiqueta;
    }
    return $r;
}
function intento_bloqueado($r) { return !empty($r['bloqueado_hasta']) && strtotime($r['bloqueado_hasta']) > time(); }
function intento_manual($r) { return !empty($r['bloqueado_hasta']) && substr($r['bloqueado_hasta'], 0, 4) === '2099'; }
function intento_minutos($r) { return max(1, (int)ceil((strtotime($r['bloqueado_hasta']) - time()) / 60)); }
function intento_fallo($r, $max, $minutos, $manual = false, $escalar = false) {
    $max = max(1, (int)$max);
    $fallos = (int)$r['fallos']; $hasta = $r['bloqueado_hasta']; $nivel = 0; $bloqueo_nuevo = false;
    $vigente = $hasta && strtotime($hasta) > time();
    if ($escalar) {
        // Bloqueo escalonado (titulares, por número de documento): los fallos seguidos NO se reinician al vencer un bloqueo,
        // y cada bloqueo seguido dura más: 1.º = bloqueo_minutos, 2.º = 1 hora, 3.º o más = 24 horas.
        // Se reinicia con un acierto (intento_limpiar), con el desbloqueo del administrador o tras 24 h sin ningún fallo.
        if (!$vigente) { $hasta = null; if (!empty($r['ultimo']) && strtotime($r['ultimo']) < time() - 86400) $fallos = 0; }
        $fallos++;
        if ($fallos % $max === 0 && !$vigente) {
            $nivel = intdiv($fallos, $max);
            $dur = $nivel === 1 ? $minutos : ($nivel === 2 ? max($minutos, 60) : max($minutos, 1440));
            $hasta = $manual ? BLOQUEO_MANUAL_HASTA : date('Y-m-d H:i:s', time() + $dur * 60); $bloqueo_nuevo = true;
        }
    } else {
        if ($hasta && strtotime($hasta) <= time()) { $fallos = 0; $hasta = null; }
        // Los contadores por IP (panel de administración) tienen una ventana de 1 hora.
        elseif (!$hasta && strpos((string)($r['tipo'] ?? ''), 'ip') === 0 && !empty($r['ultimo']) && strtotime($r['ultimo']) < time() - 3600) $fallos = 0;
        $fallos++;
        if ($fallos >= $max && !$hasta) { $hasta = $manual ? BLOQUEO_MANUAL_HASTA : date('Y-m-d H:i:s', time() + $minutos * 60); $bloqueo_nuevo = true; }
    }
    q('UPDATE intentos_acceso SET fallos=?, bloqueado_hasta=?, ultimo=? WHERE clave=?', [$fallos, $hasta, now(), $r['clave']]);
    if ($bloqueo_nuevo) {
        evento_seguridad('bloqueo', ($r['tipo'] ?? '') . ': ' . ($r['etiqueta'] ?? ''));
        if ($nivel >= 3) evento_seguridad('bloqueo_fuerte', ($r['tipo'] ?? '') . ': ' . ($r['etiqueta'] ?? ''));
    }
}
/** Intentos que le quedan a una cuenta antes del próximo bloqueo (bloqueo escalonado). */
function intentos_restantes($r, $max) { $max = max(1, (int)$max); return $max - ((int)$r['fallos'] % $max); }
function texto_duracion($min) {
    $min = max(1, (int)$min);
    if ($min < 60) return $min . ' minuto' . ($min === 1 ? '' : 's');
    $h = (int)ceil($min / 60);
    if ($h < 48) return $h . ' hora' . ($h === 1 ? '' : 's');
    $d = (int)ceil($h / 24); return $d . ' días';
}
function intento_limpiar($r) { q('UPDATE intentos_acceso SET fallos=0, bloqueado_hasta=NULL, ultimo=? WHERE clave=?', [now(), $r['clave']]); }
/** Registro liviano de sucesos de seguridad (alimenta las alertas). Se borra solo a los 30 días. */
function evento_seguridad($tipo, $detalle = '') {
    try { q('INSERT INTO eventos_seguridad (tipo, ip, detalle, fecha) VALUES (?,?,?,?)', [$tipo, client_ip(), mb_substr((string)$detalle, 0, 255), now()]); } catch (Exception $e) { /* nunca romper el flujo */ }
}
function config_db() {
    return row('SELECT * FROM configuracion WHERE id=1') ?: ['max_intentos_pin' => 3, 'bloqueo_minutos' => 15, 'recuperacion_minutos' => 30, 'bloqueo_manual' => 0, 'historial_dias' => 7,
        'alertas_activas' => 1, 'alerta_fallos' => 5, 'copia_horas' => 24, 'ultima_limpieza' => null, 'ultima_copia' => null];
}

// --- auditoría ---
function audit($accion, $objeto, $id = '', $detalle = '', $uid = null, $unombre = null) {
    if ($uid === null && ($a = admin_actual())) { $uid = $a['id']; $unombre = $a['usuario']; }
    q('INSERT INTO historial (usuario_id, usuario_nombre, accion, objeto, objeto_id, detalle, fecha) VALUES (?,?,?,?,?,?,?)',
      [$uid, mb_substr((string)$unombre, 0, 60), $accion, $objeto, (string)$id, mb_substr((string)$detalle, 0, 4000), now()]);
}

// --- autenticación ---
function admin_actual() {
    static $cache = null, $listo = false;
    if ($listo) return $cache;
    $listo = true;
    if (empty($_SESSION['admin_id'])) return null;
    if (time() - ($_SESSION['admin_last'] ?? 0) > cfg('admin_sesion')) { unset($_SESSION['admin_id'], $_SESSION['admin_last']); flash('warning', 'Tu sesión se cerró por inactividad.'); return null; }
    $u = row('SELECT * FROM usuarios WHERE id=? AND activo=1', [$_SESSION['admin_id']]);
    if (!$u) { unset($_SESSION['admin_id'], $_SESSION['admin_last']); return null; }
    $_SESSION['admin_last'] = time();
    return $cache = $u;
}
function permisos_de($u) {
    if (!$u) return [];
    if ($u['rol'] === 'principal') return array_keys(PERMISOS);
    return array_values(array_intersect(array_filter(explode(',', (string)$u['permisos'])), array_keys(PERMISOS)));
}
function tiene($perm) { return in_array($perm, permisos_de(admin_actual()), true); }
function requiere_admin($perm = null) {
    $u = admin_actual();
    if (!$u) redirect(admin_url('/login'));
    if (function_exists('mantenimiento_si_toca')) mantenimiento_si_toca();
    if ($perm && !tiene($perm)) error_pagina(403);
    return $u;
}
function es_principal() { $u = admin_actual(); return $u && $u['rol'] === 'principal'; }
function requiere_principal() {
    $u = admin_actual();
    if (!$u) redirect(admin_url('/login'));
    if (function_exists('mantenimiento_si_toca')) mantenimiento_si_toca();
    if ($u['rol'] !== 'principal') error_pagina(403, 'Solo un administrador principal puede hacer esto.');
    return $u;
}
function usuario_de_persona($pid) { return $pid ? row('SELECT * FROM usuarios WHERE persona_id=?', [$pid]) : null; }
function titular_actual() {
    static $cache = null, $listo = false;
    if ($listo) return $cache;
    $listo = true;
    if (empty($_SESSION['titular_id'])) return null;
    if (time() - ($_SESSION['titular_last'] ?? 0) > cfg('titular_sesion')) { unset($_SESSION['titular_id'], $_SESSION['titular_last']); flash('warning', 'Tu sesión se cerró por inactividad.'); return null; }
    $p = row('SELECT * FROM personas WHERE id=? AND pin_configurado=1', [$_SESSION['titular_id']]);
    if (!$p) { unset($_SESSION['titular_id'], $_SESSION['titular_last']); return null; }
    $_SESSION['titular_last'] = time();
    return $cache = $p;
}
