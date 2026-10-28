<?php
// Descarga del carnet en PDF: una sola hoja (frente y reverso), con fecha y estado de la descarga,
// protegida con contraseña (el número de documento). Sin librerías externas: escribe el PDF a mano y lo cifra
// (AES-128 si el servidor tiene OpenSSL; si no, RC4-128).

const PDF_PAD = "\x28\xBF\x4E\x5E\x4E\x75\x8A\x41\x64\x00\x4E\x56\xFF\xFA\x01\x08\x2E\x2E\x00\xB6\xD0\x68\x3E\x80\x2F\x0C\xA9\xFE\x64\x53\x69\x7A";
const PDF_ANCHO_HELV = '278,278,355,556,556,889,667,191,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,278,278,584,584,584,556,1015,667,667,722,722,667,611,778,722,278,500,667,556,833,722,778,667,778,722,667,611,722,667,944,667,667,611,278,278,278,469,556,333,556,556,500,556,556,278,556,556,222,222,500,222,833,556,556,556,556,333,500,278,556,500,722,500,500,500,334,260,334,584';
const PDF_ANCHO_NEGR = '278,333,474,556,556,889,722,238,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,333,333,584,584,584,611,975,722,722,722,722,667,611,778,722,278,556,722,611,833,722,778,667,778,722,667,611,722,667,944,667,667,611,333,278,333,584,556,333,556,611,556,611,556,333,611,611,278,278,556,278,889,611,611,611,611,389,556,333,611,556,778,556,556,500,389,280,389,584';

function pdf_rc4($clave, $datos) {
    $S = range(0, 255); $j = 0; $kl = strlen($clave);
    for ($i = 0; $i < 256; $i++) { $j = ($j + $S[$i] + ord($clave[$i % $kl])) & 255; $t = $S[$i]; $S[$i] = $S[$j]; $S[$j] = $t; }
    $i = $j = 0; $out = '';
    for ($n = 0, $L = strlen($datos); $n < $L; $n++) {
        $i = ($i + 1) & 255; $j = ($j + $S[$i]) & 255; $t = $S[$i]; $S[$i] = $S[$j]; $S[$j] = $t;
        $out .= chr(ord($datos[$n]) ^ $S[($S[$i] + $S[$j]) & 255]);
    }
    return $out;
}
function pdf_xor_clave($clave, $n) { $o = ''; for ($i = 0; $i < strlen($clave); $i++) $o .= chr(ord($clave[$i]) ^ $n); return $o; }
function pdf_azar($n) { return function_exists('random_bytes') ? random_bytes($n) : openssl_random_pseudo_bytes($n); }

class PdfDoc {
    public $w = 595.28, $h = 841.89;            // A4 en puntos
    private $c = '';                             // contenido de la página
    private $imgs = [];                          // imágenes JPEG: [datos, ancho, alto]
    private $aes = false, $ekey = '', $O = '', $U = '', $P = -1852, $id = '';
    const MM = 2.834645669;

    function __construct($claveUsuario = null) {
        if ($claveUsuario !== null && $claveUsuario !== '') $this->iniciarCifrado((string)$claveUsuario);
    }
    // ---------- cifrado (esquema estándar de PDF, revisión 3/4) ----------
    private function pad($pw) { return substr($pw . PDF_PAD, 0, 32); }
    private function iniciarCifrado($usuario) {
        $this->aes = function_exists('openssl_encrypt') && in_array('aes-128-cbc', openssl_get_cipher_methods(), true);
        $this->id = pdf_azar(16); $propietario = bin2hex(pdf_azar(16));
        $h = md5($this->pad($propietario), true); for ($i = 0; $i < 50; $i++) $h = md5($h, true);
        $kO = substr($h, 0, 16); $o = pdf_rc4($kO, $this->pad($usuario));
        for ($i = 1; $i <= 19; $i++) $o = pdf_rc4(pdf_xor_clave($kO, $i), $o);
        $this->O = $o;
        $k = md5($this->pad($usuario) . $o . pack('V', $this->P & 0xFFFFFFFF) . $this->id, true);
        for ($i = 0; $i < 50; $i++) $k = md5(substr($k, 0, 16), true);
        $this->ekey = substr($k, 0, 16);
        $u = pdf_rc4($this->ekey, md5(PDF_PAD . $this->id, true));
        for ($i = 1; $i <= 19; $i++) $u = pdf_rc4(pdf_xor_clave($this->ekey, $i), $u);
        $this->U = $u . str_repeat("\0", 16);
    }
    private function cifrar($datos, $num) {
        if ($this->ekey === '') return $datos;
        $base = $this->ekey . substr(pack('V', $num), 0, 3) . "\0\0" . ($this->aes ? 'sAlT' : '');
        $clave = substr(md5($base, true), 0, 16);
        if ($this->aes) { $iv = pdf_azar(16); return $iv . openssl_encrypt($datos, 'aes-128-cbc', $clave, OPENSSL_RAW_DATA, $iv); }
        return pdf_rc4($clave, $datos);
    }
    // ---------- texto ----------
    static function aCp1252($s) { return mb_convert_encoding((string)$s, 'Windows-1252', 'UTF-8'); }
    static function ancho($texto, $negrita, $tam) {
        static $tab = [];
        $k = $negrita ? 'n' : 'h';
        if (!isset($tab[$k])) $tab[$k] = array_map('intval', explode(',', $negrita ? PDF_ANCHO_NEGR : PDF_ANCHO_HELV));
        static $base = ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n', 'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u', 'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o',
                        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N', 'À' => 'A', 'È' => 'E', 'Ì' => 'I', 'Ò' => 'O', 'Ù' => 'U', 'Ä' => 'A', 'Ë' => 'E', 'Ï' => 'I', 'Ö' => 'O', 'ç' => 'c', 'Ç' => 'C'];
        $w = 0;
        foreach (preg_split('//u', (string)$texto, -1, PREG_SPLIT_NO_EMPTY) as $ch) {
            if (isset($base[$ch])) $ch = $base[$ch];
            $o = strlen($ch) === 1 ? ord($ch) : 0;
            $w += ($o >= 32 && $o <= 126) ? $tab[$k][$o - 32] : 556;
        }
        return $w * $tam / 1000.0;   // puntos
    }
    /** Divide el texto en líneas que caben en $anchoMm. */
    function lineas($texto, $anchoMm, $tam, $negrita = false) {
        $max = $anchoMm * self::MM; $out = []; $l = '';
        foreach (preg_split('/\s+/u', trim((string)$texto), -1, PREG_SPLIT_NO_EMPTY) as $pal) {
            $try = $l === '' ? $pal : $l . ' ' . $pal;
            if (self::ancho($try, $negrita, $tam) * 1.03 <= $max || $l === '') $l = $try; else { $out[] = $l; $l = $pal; }
        }
        if ($l !== '') $out[] = $l;
        return $out;
    }
    private function esc($s) { return str_replace(['\\', '(', ')', "\r"], ['\\\\', '\\(', '\\)', ''], self::aCp1252($s)); }
    private function col($c, $trazo = false) { return sprintf('%.3F %.3F %.3F %s ', $c[0], $c[1], $c[2], $trazo ? 'RG' : 'rg'); }
    // coordenadas: milímetros desde la esquina superior izquierda de la hoja
    function texto($s, $x, $y, $tam, $negrita = false, $color = [0, 0, 0], $alinear = 'l', $espaciado = 0) {
        $w = self::ancho($s, $negrita, $tam) + $espaciado * max(0, mb_strlen($s) - 1);
        $px = $x * self::MM; if ($alinear === 'c') $px -= $w / 2; elseif ($alinear === 'r') $px -= $w;
        $this->c .= sprintf("BT /%s %.2F Tf %.2F Tc %s%.2F %.2F Td (%s) Tj ET\n", $negrita ? 'F2' : 'F1', $tam, $espaciado, $this->col($color), $px, $this->h - $y * self::MM, $this->esc($s));
    }
    function rect($x, $y, $w, $h, $relleno = null, $trazo = null, $grosor = 0.3, $radio = 0) {
        $X = $x * self::MM; $Y = $this->h - ($y + $h) * self::MM; $W = $w * self::MM; $H = $h * self::MM; $r = $radio * self::MM; $o = '';
        if ($relleno) $o .= $this->col($relleno);
        if ($trazo) $o .= $this->col($trazo, true) . sprintf('%.2F w ', $grosor * self::MM);
        if ($r > 0) {
            $k = $r * 0.5523;
            $o .= sprintf('%.2F %.2F m %.2F %.2F l %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F l %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F l %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F l %.2F %.2F %.2F %.2F %.2F %.2F c h ',
                $X + $r, $Y, $X + $W - $r, $Y, $X + $W - $r + $k, $Y, $X + $W, $Y + $r - $k, $X + $W, $Y + $r, $X + $W, $Y + $H - $r, $X + $W, $Y + $H - $r + $k, $X + $W - $r + $k, $Y + $H, $X + $W - $r, $Y + $H,
                $X + $r, $Y + $H, $X + $r - $k, $Y + $H, $X, $Y + $H - $r + $k, $X, $Y + $H - $r, $X, $Y + $r, $X, $Y + $r - $k, $X + $r - $k, $Y, $X + $r, $Y);
        } else $o .= sprintf('%.2F %.2F %.2F %.2F re ', $X, $Y, $W, $H);
        $this->c .= $o . ($relleno && $trazo ? 'B' : ($relleno ? 'f' : 'S')) . "\n";
    }
    function linea($x1, $y1, $x2, $y2, $color = [0, 0, 0], $grosor = 0.3) {
        $this->c .= $this->col($color, true) . sprintf("%.2F w %.2F %.2F m %.2F %.2F l S\n", $grosor * self::MM, $x1 * self::MM, $this->h - $y1 * self::MM, $x2 * self::MM, $this->h - $y2 * self::MM);
    }
    /** Sello diagonal translúcido (BORRADOR / INACTIVO). */
    function sello($s, $cx, $cy, $tam, $color, $grados = 28) {
        $a = deg2rad($grados); $w = self::ancho($s, true, $tam);
        $this->c .= sprintf("q /GS1 gs %s%.4F %.4F %.4F %.4F %.2F %.2F cm BT /F2 %.2F Tf %.2F %.2F Td (%s) Tj ET Q\n", $this->col($color), cos($a), sin($a), -sin($a), cos($a), $cx * self::MM, $this->h - $cy * self::MM, $tam, -$w / 2, -$tam * 0.35, $this->esc($s));
    }
    /** Coloca una imagen (archivo JPG/PNG) dentro del cuadro indicado, sin deformarla. Devuelve false si no se pudo. */
    function imagen($ruta, $x, $y, $w, $h, $alinear = 'c') {
        $im = pdf_cargar_jpeg($ruta); if (!$im) return false;
        $r = min($w / $im[1], $h / $im[2]); $iw = $im[1] * $r; $ih = $im[2] * $r;
        $ox = $alinear === 'l' ? 0 : ($w - $iw) / 2; $oy = ($h - $ih) / 2;
        $this->imgs[] = $im; $n = count($this->imgs);
        $this->c .= sprintf("q %.2F 0 0 %.2F %.2F %.2F cm /Im%d Do Q\n", $iw * self::MM, $ih * self::MM, ($x + $ox) * self::MM, $this->h - ($y + $oy + $ih) * self::MM, $n);
        return true;
    }
    function barras($bits, $x, $y, $w, $h) {
        $n = strlen($bits); $mod = $w / $n; $i = 0; $o = '0 g ';
        while ($i < $n) {
            if ($bits[$i] === '1') { $j = $i; while ($j < $n && $bits[$j] === '1') $j++; $o .= sprintf('%.3F %.3F %.3F %.3F re ', ($x + $i * $mod) * self::MM, $this->h - ($y + $h) * self::MM, ($j - $i) * $mod * self::MM, $h * self::MM); $i = $j; }
            else $i++;
        }
        $this->c .= $o . "f\n";
    }
    // ---------- salida ----------
    function salida() {
        $objs = []; $res = '/Font << /F1 5 0 R /F2 6 0 R >> /ExtGState << /GS1 << /ca 0.28 /CA 0.28 >> >>';
        $n = 7; $xo = '';
        foreach ($this->imgs as $i => $im) { $num = $n++; $xo .= sprintf('/Im%d %d 0 R ', $i + 1, $num); $objs[$num] = ['img', $im]; }
        if ($xo !== '') $res .= ' /XObject << ' . $xo . '>>';
        $cont = function_exists('gzcompress') ? gzcompress($this->c, 6) : $this->c; $filtro = function_exists('gzcompress') ? ' /Filter /FlateDecode' : '';
        $fijos = [1 => "<< /Type /Catalog /Pages 2 0 R >>", 2 => "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
                  3 => sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Contents 4 0 R /Resources << %s >> >>', $this->w, $this->h, $res)];
        $pdf = "%PDF-1.6\n%\xE2\xE3\xCF\xD3\n"; $off = [];
        $escribir = function ($num, $cuerpo) use (&$pdf, &$off) { $off[$num] = strlen($pdf); $pdf .= $num . " 0 obj\n" . $cuerpo . "\nendobj\n"; };
        $flujo = function ($num, $dict, $datos) use ($escribir) { $d = $this->cifrar($datos, $num); $escribir($num, '<< ' . $dict . ' /Length ' . strlen($d) . " >>\nstream\n" . $d . "\nendstream"); };
        foreach ($fijos as $num => $c) $escribir($num, $c);
        $flujo(4, trim($filtro), $cont);
        $escribir(5, '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>');
        $escribir(6, '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>');
        foreach ($objs as $num => $o) $flujo($num, sprintf('/Type /XObject /Subtype /Image /Width %d /Height %d /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode', $o[1][1], $o[1][2]), $o[1][0]);
        $total = $n; $trailer = '/Size ' . $total . ' /Root 1 0 R';
        if ($this->ekey !== '') {
            $enc = $total; $total++;
            $dic = $this->aes ? '<< /Filter /Standard /V 4 /R 4 /Length 128 /CF << /StdCF << /AuthEvent /DocOpen /CFM /AESV2 /Length 16 >> >> /StmF /StdCF /StrF /StdCF'
                              : '<< /Filter /Standard /V 2 /R 3 /Length 128';
            $escribir($enc, $dic . ' /O <' . bin2hex($this->O) . '> /U <' . bin2hex($this->U) . '> /P ' . $this->P . ' >>');
            $trailer = '/Size ' . $total . ' /Root 1 0 R /Encrypt ' . $enc . ' 0 R /ID [<' . bin2hex($this->id) . '> <' . bin2hex($this->id) . '>]';
        }
        $xref = strlen($pdf); $pdf .= "xref\n0 $total\n0000000000 65535 f \n";
        for ($i = 1; $i < $total; $i++) $pdf .= sprintf("%010d 00000 n \n", $off[$i]);
        return $pdf . "trailer\n<< $trailer >>\nstartxref\n$xref\n%%EOF\n";
    }
}

/** Lee una imagen (JPG o PNG, con o sin transparencia), la aplana sobre blanco y la devuelve como JPEG base: [datos, ancho, alto]. */
function pdf_cargar_jpeg($ruta) {
    if (!is_file($ruta) || !function_exists('imagecreatetruecolor')) return null;
    $info = @getimagesize($ruta); if (!$info) return null;
    $src = $info[2] === IMAGETYPE_PNG ? @imagecreatefrompng($ruta) : ($info[2] === IMAGETYPE_JPEG ? @imagecreatefromjpeg($ruta) : null);
    if (!$src) return null;
    $w = imagesx($src); $h = imagesy($src); $lienzo = imagecreatetruecolor($w, $h);
    imagefill($lienzo, 0, 0, imagecolorallocate($lienzo, 255, 255, 255)); imagealphablending($lienzo, true); imagecopy($lienzo, $src, 0, 0, 0, 0, $w, $h);
    imageinterlace($lienzo, 0); ob_start(); imagejpeg($lienzo, null, 88); $d = ob_get_clean();
    imagedestroy($src); imagedestroy($lienzo);
    return $d ? [$d, $w, $h] : null;
}

/** Genera el PDF del carnet de la persona $p (con los joins de persona()). $baseUrl: dirección del sitio (para /verificar). */
function pdf_carne($p, $pl, $baseUrl) {
    $d = new PdfDoc((string)$p['numero_documento']);
    $verde = [0.22, 0.66, 0.21]; $verdeOsc = [0.12, 0.35, 0.11]; $gris = [0.42, 0.45, 0.42]; $tinta = [0.13, 0.16, 0.13]; $rojo = [0.75, 0.15, 0.15];
    $estado = $p['carne_estado'] ?? 'pendiente'; $inactivo = $estado === 'inactivo';
    list($vigente, $titulo, $detalle) = estado_verificacion($p);
    $ahora = date('d/m/Y') . ' a las ' . date('g:i') . (date('a') === 'am' ? ' a. m.' : ' p. m.');
    // ----- encabezado -----
    $d->texto('CARNET DIGITAL', 105, 21, 17, true, $verdeOsc, 'c');
    $d->texto('Complejo Tecnológico, Turístico y Agroindustrial del Occidente Antioqueño', 105, 27, 8.5, false, $gris, 'c');
    $d->linea(36, 31, 174, 31, $verde, 0.6);
    $d->texto('Descargado el ' . $ahora, 105, 38.5, 10.5, false, $tinta, 'c');
    $d->texto('Estado del carnet al descargar: ' . $titulo . ($vigente ? '' : '') . ' · ' . $detalle, 105, 44.5, 10.5, true, $vigente ? $verdeOsc : $rojo, 'c');
    // ----- medidas del carnet -----
    $W = 62; $pad = 4.2; $inner = $W - 2 * $pad; $x1 = 36; $x2 = 112; $y0 = 52;
    $tamN = 11.5; $ln = array_merge($d->lineas(persona_nombres_tc($p), $inner, $tamN, true), $d->lineas(persona_apellidos_tc($p), $inner, $tamN, true));   // nombres arriba, apellidos abajo
    if (count($ln) > 4) { $tamN = 9.2; $ln = array_merge($d->lineas(persona_nombres_tc($p), $inner, $tamN, true), $d->lineas(persona_apellidos_tc($p), $inner, $tamN, true)); }
    $lhN = $tamN * 0.3528 * 1.18;                                   // alto de línea en mm
    $comp = $d->lineas($pl['complejo'], $inner, 6.6); $prog = $d->lineas(persona_programa_o_area($p), $inner, 6.2, true);
    // posiciones relativas al borde superior del carnet
    $yTipo = $pad + 32 + 5.5; $yNom = $yTipo + 7.2; $yDoc = $yNom + (count($ln) - 1) * $lhN + 5.2; $yBar = $yDoc + 2.6; $hBar = 9.5; $yReg = $yBar + $hBar + 5.6;
    $yComp = $yReg + 4.2; $yProg = $yComp + count($comp) * 2.6 + 0.8; $yFicha = $yProg + count($prog) * 2.5 + 1.2;
    $esAp = $p['tipo_persona'] === 'aprendiz' && !empty($p['ficha_numero']);
    $fin = $esAp ? $yFicha + 1.8 : $yProg + (count($prog) - 1) * 2.5 + 1.2;
    $H = max(96, $fin + $pad);
    foreach ([$x1, $x2] as $x) $d->rect($x, $y0, $W, $H, $inactivo ? [1, 0.94, 0.94] : [1, 1, 1], $inactivo ? $rojo : [0.78, 0.8, 0.78], $inactivo ? 0.7 : 0.35, 3.2);
    // ----- frente -----
    $d->linea($x1 + $pad, $y0 + $yTipo + 2.6, $x1 + $W - $pad, $y0 + $yTipo + 2.6, $verde, 0.35);
    $okLogo = $pl['logo'] !== '' && $d->imagen(APP_ROOT . '/storage/' . $pl['logo'], $x1 + $pad, $y0 + $pad, 27, 20, 'l');
    if (!$okLogo) $d->texto('LOGO', $x1 + $pad, $y0 + $pad + 8, 13, true, $verde);
    $okFoto = $p['foto'] !== '' && $d->imagen(APP_ROOT . '/storage/' . $p['foto'], $x1 + $W - $pad - 24, $y0 + $pad, 24, 32);
    if (!$okFoto) { $d->rect($x1 + $W - $pad - 24, $y0 + $pad, 24, 32, [0.86, 0.9, 0.86]); $d->texto('Sin foto', $x1 + $W - $pad - 12, $y0 + $pad + 17, 7.5, false, $gris, 'c'); }
    $d->texto(TIPOS_PERSONA[$p['tipo_persona']], $x1 + $pad, $y0 + $yTipo, 7.4, false, $tinta, 'l', 1.6);
    foreach ($ln as $i => $l) $d->texto($l, $x1 + $pad, $y0 + $yNom + $i * $lhN, $tamN, true, $verde);
    $d->texto((TIPOS_DOC[$p['tipo_documento']] ?? $p['tipo_documento']) . ' ' . $p['numero_documento'], $x1 + $pad, $y0 + $yDoc, 8.6, false, $tinta);
    if (persona_rh_visible($p) !== '') $d->texto('Rh ' . persona_rh_visible($p), $x1 + $W - $pad, $y0 + $yDoc, 8.6, false, $tinta, 'r');
    $d->barras(code128_bits($p['numero_documento']), $x1 + $pad + 4, $y0 + $yBar, $inner - 8, $hBar);
    $d->texto($pl['regional'], $x1 + $pad, $y0 + $yReg, 9, true, $tinta);
    foreach ($comp as $i => $l) $d->texto($l, $x1 + $pad, $y0 + $yComp + $i * 2.6, 6.6, false, $verde);
    foreach ($prog as $i => $l) $d->texto($l, $x1 + $pad, $y0 + $yProg + $i * 2.5, 6.2, true, $verde);
    if ($esAp) $d->texto('Ficha No. ' . $p['ficha_numero'], $x1 + $pad, $y0 + $yFicha, 7, true, $verde);
    if ($inactivo) $d->sello('INACTIVO', $x1 + $W / 2, $y0 + $H * 0.5, 24, $rojo);
    elseif (carne_es_borrador($p)) $d->sello('BORRADOR', $x1 + $W / 2, $y0 + $H * 0.5, 24, $rojo);
    // ----- reverso -----
    $tr = $d->lineas($pl['texto_reverso'], $inner, 7.2); foreach ($tr as $i => $l) $d->texto($l, $x2 + $W / 2, $y0 + $pad + 6 + $i * 3.2, 7.2, false, $tinta, 'c');
    $yF = $y0 + $pad + 6 + count($tr) * 3.2 + 8;
    if ($pl['firma'] !== '') $d->imagen(APP_ROOT . '/storage/' . $pl['firma'], $x2 + 12, $yF, $W - 24, 16);
    $d->linea($x2 + 10, $yF + 17.5, $x2 + $W - 10, $yF + 17.5, [0.6, 0.62, 0.6], 0.25);
    $d->texto($pl['etiqueta_firma'], $x2 + $W / 2, $yF + 21.2, 6.6, false, $gris, 'c');
    $pe = $d->lineas($pl['texto_perdida'], $inner, 6.4); foreach ($pe as $i => $l) $d->texto($l, $x2 + $W / 2, $y0 + $H - $pad - (count($pe) - 1 - $i) * 2.8 - 1, 6.4, false, $gris, 'c');
    if ($inactivo) $d->sello('INACTIVO', $x2 + $W / 2, $y0 + $H * 0.5, 24, $rojo); elseif (carne_es_borrador($p)) $d->sello('BORRADOR', $x2 + $W / 2, $y0 + $H * 0.5, 24, $rojo);
    $d->texto('Frente', $x1 + $W / 2, $y0 + $H + 5, 8, false, $gris, 'c'); $d->texto('Reverso', $x2 + $W / 2, $y0 + $H + 5, 8, false, $gris, 'c');
    // ----- notas -----
    $yN = $y0 + $H + 14;
    $d->rect(36, $yN, 138, 40, [0.95, 0.98, 0.94], [0.75, 0.88, 0.73], 0.3, 2);
    $d->texto('Importante', 40, $yN + 6.5, 9.5, true, $verdeOsc);
    $notas = ['Este PDF es una copia del carnet a la fecha y hora de la descarga. Para saber si sigue vigente, consulta en línea: ' . $baseUrl . '/verificar',
              'Documento personal: no lo compartas. Se abre con el número de documento (esa es la contraseña).',
              'Una impresión o una captura de este archivo no prueba que el carnet esté vigente: lo que vale es el estado que muestra el sistema.'];
    $yy = $yN + 12.5;
    foreach ($notas as $nt) { foreach ($d->lineas($nt, 126, 7.8) as $i => $l) { $d->texto(($i === 0 ? '•  ' : '    ') . $l, 40, $yy, 7.8, false, $tinta); $yy += 3.7; } $yy += 1.2; }
    $d->texto(cfg('footer', '© 2026 CTTAOA'), 105, 287, 7, false, $gris, 'c');
    return $d->salida();
}

/** Envía el PDF del carnet al navegador. */
function pdf_enviar_carne($p) {
    $host = preg_replace('/[^A-Za-z0-9.\-:]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $base = (es_https() ? 'https' : 'http') . '://' . $host . rtrim(base_path(), '/');
    $pdf = pdf_carne($p, plantilla_actual(), $base);
    $nombre = 'carne_CTTAOA_' . preg_replace('/\D/', '', $p['numero_documento']) . '.pdf';
    header('Content-Type: application/pdf'); header('Content-Disposition: attachment; filename="' . $nombre . '"'); header('Content-Length: ' . strlen($pdf));
    header('Cache-Control: no-store, no-cache, must-revalidate'); header('Pragma: no-cache'); header('X-Content-Type-Options: nosniff');
    echo $pdf; exit;
}
