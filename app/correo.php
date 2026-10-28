<?php
// Envío de correos, sin librerías externas. Se configura en config.php → 'correo' (y 'sitio_url').
//   modo 'smtp'    → servidor SMTP: Gmail (contraseña de aplicación), Brevo, Outlook o el correo de tu hosting.
//   modo 'mail'    → la función mail() de PHP (lo más simple; depende de que el hosting la permita).
//   modo 'archivo' → no envía nada: guarda el correo en storage/correos/ (para probar sin enviar).

function correo_cfg() {
    $c = cfg('correo', []); if (!is_array($c)) $c = [];
    return $c + ['modo' => 'mail', 'desde' => '', 'nombre' => 'Carnet digital CTTAOA', 'smtp' => []];
}
/** Dirección base del sitio para los enlaces de los correos (config.php → sitio_url; si falta, la del servidor). */
function correo_sitio() {
    $u = trim((string)cfg('sitio_url', ''));
    // Solo cuenta el dominio (esquema + servidor + puerto): la carpeta donde está instalado el sistema se agrega sola, así que se ignora aunque la escriban.
    if ($u !== '') { $pu = parse_url($u); if (!empty($pu['scheme']) && !empty($pu['host'])) return strtolower($pu['scheme']) . '://' . $pu['host'] . (!empty($pu['port']) ? ':' . $pu['port'] : ''); }
    if ($u === '') { $h = preg_replace('/[^A-Za-z0-9.\-:]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost')); $u = (es_https() ? 'https' : 'http') . '://' . $h; }
    return rtrim($u, '/');
}
function correo_b64($t) { return '=?UTF-8?B?' . base64_encode((string)$t) . '?='; }
/** Arma el mensaje MIME (texto y, si se da, HTML). Devuelve [encabezados (con To y Subject), cuerpo]. */
function correo_construir($para, $asunto, $texto, $html, $desde, $nombre) {
    $dominio = substr((string)strrchr($desde, '@'), 1) ?: 'localhost'; $lim = 'b_' . bin2hex(random_bytes(12));
    $h = ['From: ' . correo_b64($nombre) . ' <' . $desde . '>', 'To: <' . $para . '>', 'Subject: ' . correo_b64($asunto), 'Date: ' . date('r'),
          'Message-ID: <' . bin2hex(random_bytes(10)) . '@' . $dominio . '>', 'MIME-Version: 1.0', 'X-Auto-Response-Suppress: All'];
    if ($html === '') { $h[] = 'Content-Type: text/plain; charset=UTF-8'; $h[] = 'Content-Transfer-Encoding: base64'; $cuerpo = chunk_split(base64_encode($texto), 76, "\r\n"); }
    else {
        $h[] = 'Content-Type: multipart/alternative; boundary="' . $lim . '"';
        $cuerpo = '--' . $lim . "\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($texto), 76, "\r\n")
                . '--' . $lim . "\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($html), 76, "\r\n") . '--' . $lim . "--\r\n";
    }
    return [$h, $cuerpo];
}
/** Envía un correo. Devuelve [true, ''] o [false, 'motivo']. Nunca lanza excepciones. */
function enviar_correo($para, $asunto, $texto, $html = '') {
    try {
        $c = correo_cfg(); $para = trim((string)$para);
        if (!filter_var($para, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $para)) return [false, 'El correo de destino no es válido.'];
        $desde = trim((string)$c['desde']);
        if ($desde === '' || !filter_var($desde, FILTER_VALIDATE_EMAIL)) $desde = 'no-responder@' . (parse_url(correo_sitio(), PHP_URL_HOST) ?: 'localhost');
        list($h, $cuerpo) = correo_construir($para, (string)$asunto, (string)$texto, (string)$html, $desde, (string)$c['nombre']);
        if ($c['modo'] === 'archivo') {
            $dir = APP_ROOT . '/storage/correos'; if (!is_dir($dir)) @mkdir($dir, 0775, true);
            $base = $dir . '/' . date('Ymd_His') . '_' . bin2hex(random_bytes(4));
            @file_put_contents($base . '.txt', "Para: " . $para . "\r\nAsunto: " . $asunto . "\r\n\r\n" . $texto . "\r\n");    // copia legible (el .eml viene codificado)
            $ok = @file_put_contents($base . '.eml', implode("\r\n", $h) . "\r\n\r\n" . $cuerpo);
            return $ok === false ? [false, 'No se pudo guardar el correo en storage/correos.'] : [true, ''];
        }
        if ($c['modo'] === 'smtp') return correo_smtp($c, $desde, $para, $h, $cuerpo);
        $cab = array_values(array_filter($h, function ($l) { return strpos($l, 'To:') !== 0 && strpos($l, 'Subject:') !== 0; }));
        $ok = @mail($para, correo_b64($asunto), $cuerpo, implode("\r\n", $cab));
        return $ok ? [true, ''] : [false, 'La función mail() del servidor no pudo enviar el correo (el hosting puede tenerla desactivada: usa el modo smtp).'];
    } catch (Throwable $e) { return [false, 'Error al enviar: ' . $e->getMessage()]; }
}
function correo_smtp($c, $desde, $para, $h, $cuerpo) {
    $s = (is_array($c['smtp']) ? $c['smtp'] : []) + ['host' => '', 'puerto' => 587, 'seguridad' => 'tls', 'usuario' => '', 'clave' => '', 'verificar_certificado' => true];
    if ($s['host'] === '') return [false, 'Falta el servidor SMTP (correo → smtp → host en config.php).'];
    $ver = (bool)$s['verificar_certificado'];
    $ctx = stream_context_create(['ssl' => ['verify_peer' => $ver, 'verify_peer_name' => $ver, 'allow_self_signed' => !$ver]]);
    $fp = @stream_socket_client(($s['seguridad'] === 'ssl' ? 'ssl://' : 'tcp://') . $s['host'] . ':' . (int)$s['puerto'], $en, $es, 15, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) return [false, 'No se pudo conectar con el servidor de correo (' . ($es ?: 'sin respuesta') . '). Algunos hosting bloquean el puerto ' . (int)$s['puerto'] . '.'];
    stream_set_timeout($fp, 20);
    $leer = function () use ($fp) { $r = ''; while (($l = fgets($fp, 1024)) !== false) { $r .= $l; if (strlen($l) < 4 || $l[3] !== '-') break; } return $r; };
    $paso = function ($cmd, $esperado) use ($fp, $leer) {
        if ($cmd !== null) fwrite($fp, $cmd . "\r\n");
        $r = $leer(); return in_array((int)substr($r, 0, 3), (array)$esperado, true) ? [true, $r] : [false, trim($r) ?: 'sin respuesta del servidor'];
    };
    $salir = function ($msg) use ($fp) { @fwrite($fp, "QUIT\r\n"); @fclose($fp); return [false, $msg]; };
    list($ok, $r) = $paso(null, 220); if (!$ok) return $salir('El servidor respondió: ' . $r);
    list($ok, $r) = $paso('EHLO localhost', 250); if (!$ok) return $salir('EHLO rechazado: ' . $r);
    if ($s['seguridad'] === 'tls') {
        list($ok, $r) = $paso('STARTTLS', 220); if (!$ok) return $salir('STARTTLS rechazado: ' . $r);
        if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) return $salir('No se pudo abrir la conexión segura (TLS).');
        list($ok, $r) = $paso('EHLO localhost', 250); if (!$ok) return $salir('EHLO rechazado: ' . $r);
    }
    if ((string)$s['usuario'] !== '') {
        list($ok, $r) = $paso('AUTH LOGIN', 334); if (!$ok) return $salir('El servidor no acepta AUTH LOGIN: ' . $r);
        list($ok, $r) = $paso(base64_encode((string)$s['usuario']), 334); if (!$ok) return $salir('Usuario rechazado: ' . $r);
        list($ok, $r) = $paso(base64_encode((string)$s['clave']), 235); if (!$ok) return $salir('Usuario o contraseña de correo incorrectos: ' . $r);
    }
    list($ok, $r) = $paso('MAIL FROM:<' . $desde . '>', 250); if (!$ok) return $salir('Remitente rechazado: ' . $r);
    list($ok, $r) = $paso('RCPT TO:<' . $para . '>', [250, 251]); if (!$ok) return $salir('Destinatario rechazado: ' . $r);
    list($ok, $r) = $paso('DATA', 354); if (!$ok) return $salir('DATA rechazado: ' . $r);
    $datos = preg_replace('/^\./m', '..', implode("\r\n", $h) . "\r\n\r\n" . $cuerpo);
    fwrite($fp, rtrim($datos, "\r\n") . "\r\n.\r\n");
    list($ok, $r) = $paso(null, 250); if (!$ok) return $salir('El servidor no aceptó el mensaje: ' . $r);
    @fwrite($fp, "QUIT\r\n"); @fclose($fp);
    return [true, ''];
}
/** Oculta parte de un correo: ana.lopez@gmail.com → a***@gmail.com */
function enmascarar_correo($e) {
    $p = explode('@', (string)$e, 2); if (count($p) !== 2) return '';
    return mb_substr($p[0], 0, 1) . '***@' . $p[1];
}
