<?php
// Controlador frontal: todas las rutas pasan por aquí (ver .htaccess).
require __DIR__ . '/app/core.php';
require __DIR__ . '/app/services.php';
require __DIR__ . '/app/public.php';
require __DIR__ . '/app/correo.php';
require __DIR__ . '/app/mantenimiento.php';
require __DIR__ . '/app/pdf.php';
require __DIR__ . '/app/admin.php';
require __DIR__ . '/app/helpers_views.php';

ini_set('display_errors', cfg('debug') ? '1' : '0');
error_reporting(cfg('debug') ? E_ALL : (E_ALL & ~E_DEPRECATED & ~E_NOTICE));
set_exception_handler(function ($ex) {
    @file_put_contents(APP_ROOT . '/storage/error.log', '[' . date('c') . '] ' . get_class($ex) . ': ' . $ex->getMessage() . ' @ ' . $ex->getFile() . ':' . $ex->getLine() . "\n", FILE_APPEND);
    if (!headers_sent()) http_response_code(500);
    echo cfg('debug') ? '<pre>' . e($ex) . '</pre>' : '<h1>Error del servidor</h1><p>Ocurrió un problema. Intenta de nuevo más tarde.</p>';
    exit;
});

$ruta = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$base = base_path();
if ($base !== '' && strpos($ruta, $base) === 0) $ruta = substr($ruta, strlen($base));
if ($ruta === '' ) $ruta = '/';
if ($ruta !== '/') $ruta = rtrim($ruta, '/');
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$prefijo = '/' . cfg('admin_prefix');
$esPanel = ($ruta === $prefijo || strpos($ruta, $prefijo . '/') === 0);

// Lista blanca de IP para el panel: para otras IP la ruta "no existe".
if ($esPanel && cfg('admin_allowed_ips') && !in_array(client_ip(), cfg('admin_allowed_ips'), true)) { http_response_code(404); exit('Not Found'); }

cabeceras_seguridad(true, $esPanel);
iniciar_sesion();

function despachar($tabla, $ruta, $metodo) {
    foreach ($tabla as $r) {
        list($m, $patron, $fn) = $r;
        if ($m !== '*' && $m !== $metodo) continue;
        if (preg_match('#^' . $patron . '$#', $ruta, $mm)) { array_shift($mm); call_user_func_array($fn, $mm); return true; }
    }
    return false;
}

if ($esPanel) {
    $sub = substr($ruta, strlen($prefijo)); if ($sub === '' || $sub === false) $sub = '/';
    $tabla = [
        ['*', '/login', 'ctl_admin_login'], ['POST', '/logout', 'ctl_admin_logout'], ['*', '/', 'ctl_admin_inicio'], ['*', '/cuenta', 'ctl_cuenta'], ['GET', '/mi-carne', 'ctl_admin_mi_carne'], ['POST', '/mi-carne/foto', 'ctl_admin_mi_foto'],
        ['GET', '/fichas', 'ctl_fichas'], ['GET', '/fichas/(\d+)', 'ctl_ficha_detalle'], ['*', '/fichas/(\d+)/eliminar', 'ctl_ficha_eliminar'], ['POST', '/fichas/(\d+)/activar-todos', 'ctl_ficha_activar_todos'], ['POST', '/fichas/(\d+)/fechas', 'ctl_ficha_fechas'], ['POST', '/fichas/(\d+)/estado-formacion', 'ctl_ficha_estado_formacion'], ['POST', '/fichas/(\d+)/desactivar-todos', 'ctl_ficha_desactivar_todos'],
        ['GET', '/personas', 'ctl_personas'], ['*', '/personas/nueva', 'ctl_persona_form'], ['GET', '/personas/(\d+)', 'ctl_persona_ver'],
        ['*', '/personas/(\d+)/editar', 'ctl_persona_form'], ['POST', '/personas/(\d+)/foto', 'ctl_persona_foto'], ['POST', '/personas/(\d+)/estado', 'ctl_persona_estado'],
        ['POST', '/personas/(\d+)/eliminar', 'ctl_persona_eliminar'],
        ['*', '/importar', 'ctl_importar'], ['GET', '/importar/plantilla', 'ctl_importar_plantilla'], ['*', '/importar/(\d+)', 'ctl_importar_detalle'],
        ['*', '/fotos', 'ctl_fotos_masivas'], ['*', '/plantilla', 'ctl_plantilla_admin'], ['*', '/usuarios', 'ctl_usuarios'], ['POST', '/usuarios/(\d+)/permisos', 'ctl_usuario_permisos'], ['*', '/usuarios/(\d+)/vincular', 'ctl_usuario_vincular'], ['POST', '/usuarios/(\d+)/quitar', 'ctl_usuario_quitar'],
        ['GET', '/historial', 'ctl_historial'], ['GET', '/consultas', 'ctl_consultas'], ['GET', '/personas/(\d+)/pdf', 'ctl_persona_pdf'], ['*', '/configuracion', 'ctl_configuracion'], ['POST', '/configuracion/copia', 'ctl_config_copia'], ['POST', '/configuracion/correo-prueba', 'ctl_correo_prueba'], ['GET', '/configuracion/copia/descargar', 'ctl_config_descargar'], ['POST', '/configuracion/desbloquear', 'ctl_config_desbloquear'], ['POST', '/configuracion/alertas/revisar', 'ctl_config_alertas_vistas'],
    ];
    if (!despachar($tabla, $sub, $metodo)) error_pagina(404);
    exit;
}
$tabla = [
    ['*', '/', 'ctl_inicio'], ['*', '/crear-pin', 'ctl_crear_pin'], ['*', '/restablecer-pin', 'ctl_restablecer_pin'], ['*', '/agregar-correo', 'ctl_agregar_correo'], ['GET', '/privacidad', 'ctl_privacidad'], ['*', '/verificar', 'ctl_verificar_publico'], ['*', '/aceptar-terminos', 'ctl_aceptar_terminos'], ['GET', '/mi-carne', 'ctl_mi_carne'], ['GET', '/ayuda', 'ctl_ayuda'],
    ['POST', '/salir', 'ctl_salir'], ['POST', '/cambiar-documento', 'ctl_cambiar_documento'],
    ['GET', '/privado/foto/(\d+)', 'ctl_foto'], ['GET', '/privado/institucional/(logo|firma)', 'ctl_institucional'],
    ['GET', '/(?:api/)?verificar/([A-Za-z0-9]{1,12})', 'ctl_verificar'], ['GET', '/api/mi-carne', 'ctl_api_mi_carne'], ['GET', '/mi-carne/pdf', 'ctl_mi_carne_pdf'], ['GET', '/api/personas', 'ctl_api_personas'], ['GET', '/ping', 'ctl_ping'],
];
if (!despachar($tabla, $ruta, $metodo)) error_pagina(404);
