<?php
// ============================================================
//  CONFIGURACIÓN DEL SISTEMA  -  edita SOLO este archivo
// ============================================================
return [
    // ------------------------------------------------------------
    //  BASE DE DATOS: deja ACTIVO un solo bloque y comenta el otro.
    //  - Para usar en el HOSTING: quita los /* */ del bloque del hosting
    //    y comenta (pon /* */) el bloque de XAMPP.
    //  - Para usar en XAMPP: haz lo contrario.
    // ------------------------------------------------------------

    // ===== HOSTING (InfinityFree) =====
    /*
    'db_host' => 'sql213.ezyro.com',
    'db_port' => 3306,
    'db_name' => 'ezyro_43159770_cttaoa',
    'db_user' => 'ezyro_43159770',
    'db_pass' => '2218c7b4179',
    */

    // ===== XAMPP (computador local) =====
    'db_host' => 'localhost',
    'db_port' => 3306,
    'db_name' => 'cttaoa',
    'db_user' => 'root',
    'db_pass' => '',

    // --- Seguridad ---
    // Clave larga y secreta (firma la cookie del dispositivo). No la compartas.
    'secret_key'   => 'PzyBPdkHY92TDtzmwjfOYJcg7Av8DtXWG-w7u8oG5eOd8iB03BDosCqS4X70HRul',
    // Ruta del panel: https://tudominio.com/<admin_prefix>/login
    'admin_prefix' => 'admin/gestion-2026',
    // IP autorizadas para el panel (vacío = cualquiera). Ej: ['190.1.2.3']
    'admin_allowed_ips' => [],
    // true SOLO si tu hosting está detrás de un proxy/Cloudflare (usa X-Forwarded-For)
    'trusted_proxy' => false,

    // --- Ajustes ---
    'debug'    => false,                 // true solo mientras pruebas
    'timezone' => 'America/Bogota',
    'titular_sesion'          => 5 * 60,        // cierre por inactividad del titular (segundos)
    'admin_sesion'            => 15 * 60,       // cierre por inactividad del administrador
    'admin_max_intentos'      => 5,             // fallos usuario+IP antes de bloquear
    'admin_ip_max_intentos'   => 20,            // fallos por IP (cualquier usuario)
    'admin_bloqueo_minutos'   => 15,
    'max_foto_bytes'  => 3 * 1024 * 1024,        // peso máximo de cada fotografía (3 MB)
    'max_excel_bytes' => 5 * 1024 * 1024,
    'consultas_dias'      => 90,                // días que se conservan las consultas de /verificar (luego se borran solas)
    'verificar_alerta'    => 100,               // avisa (sin bloquear) si una IP consulta tantos documentos distintos en 1 hora en /verificar; 0 = sin aviso
    'historial_max_filas' => 20000,             // tope de filas del historial (además de los días)

    // --- Fichas ---
    'ficha_aviso_dias' => 5,                    // días antes de la fecha de fin de una ficha en que el panel avisa

    // --- Correo (recuperación de PIN) ---
    // Solo el DOMINIO del sitio (sin carpeta ni barra al final). Lo usan los enlaces de los correos. Ej.: 'https://midominio.com'  ·  en XAMPP: 'http://localhost'
    'sitio_url' => 'http://localhost',
    'correo' => [
        'modo'   => 'archivo',                     // 'mail' (función mail() del hosting) · 'smtp' (recomendado) · 'archivo' (solo pruebas: no envía)
        'desde'  => 'carnet@localhost.test',                         // remitente, ej.: 'no-responder@tudominio.com' (con Gmail debe ser tu mismo Gmail)
        'nombre' => 'Carnet digital CTTAOA',
        'smtp'   => [
            'host' => '',                       // Gmail: smtp.gmail.com · Brevo: smtp-relay.brevo.com · Outlook: smtp-mail.outlook.com
            'puerto' => 587,                    // 587 con seguridad 'tls'  ·  465 con seguridad 'ssl'
            'seguridad' => 'tls',               // 'tls' | 'ssl' | 'ninguna'
            'usuario' => '',                    // Gmail: tu correo completo
            'clave' => '',                      // Gmail: «contraseña de aplicación» de 16 letras (no tu contraseña normal)
        ],
    ],

    // --- Política de privacidad y términos (página /privacidad) ---
    'politica_version'  => '1.1',                       // si cambias el texto de fondo, sube la versión (1.1, 2.0...) y todos deberán aceptar de nuevo
    'politica_fecha'    => '21 de octubre de 2026',
    'politica_borrador' => false,                       // true = muestra el aviso «documento base, pendiente de aprobación del SENA»; ponlo en false cuando el SENA lo apruebe
    'responsable_nombre'    => 'Servicio Nacional de Aprendizaje – SENA · Regional Antioquia · Complejo Tecnológico, Turístico y Agroindustrial del Occidente Antioqueño (CTTAOA)',
    'responsable_direccion' => 'Calle 11 N° 12-42, Santa Fe de Antioquia',
    'responsable_correo'    => 'cttao@sena.edu.co',                      // correo institucional para consultas y reclamos de datos personales
    'responsable_telefono'  => '',

    // --- Pie de página (firma del proyecto) que aparece en todas las pantallas ---
    'footer' => '© 2026 CTTAOA · Desarrollado por Yeiner Castillo · Ficha 3134228',
];
