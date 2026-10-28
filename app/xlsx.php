<?php
// Lector mínimo de .xlsx en PHP puro (ZipArchive + SimpleXML). Sin librerías externas.

function xlsx_abrir($ruta) {
    if (!class_exists('ZipArchive')) throw new RuntimeException('El hosting no tiene la extensión ZipArchive activada.');
    $zip = new ZipArchive();
    if ($zip->open($ruta) !== true) throw new RuntimeException('El archivo no es un Excel válido.');
    $total = 0;
    for ($i = 0; $i < $zip->numFiles; $i++) { $st = $zip->statIndex($i); $total += $st['size']; }
    if ($total > 80 * 1024 * 1024) { $zip->close(); throw new RuntimeException('El archivo es demasiado grande al descomprimirse.'); }
    return $zip;
}
function xlsx_xml($zip, $nombre) {
    $c = $zip->getFromName($nombre);
    if ($c === false) return null;
    if (PHP_VERSION_ID < 80000) libxml_disable_entity_loader(true);
    $x = simplexml_load_string($c, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
    return $x ?: null;
}
function xlsx_hojas_info($zip) {
    $wb = xlsx_xml($zip, 'xl/workbook.xml');
    if (!$wb) throw new RuntimeException('El archivo no es un Excel válido.');
    $rels = xlsx_xml($zip, 'xl/_rels/workbook.xml.rels'); $mapa = [];
    if ($rels) foreach ($rels->Relationship as $r) {
        $t = (string)$r['Target']; $t = ltrim($t, '/');
        $mapa[(string)$r['Id']] = str_starts_with($t, 'xl/') ? $t : 'xl/' . $t;
    }
    $out = [];
    foreach ($wb->sheets->sheet as $s) {
        $rid = (string)$s->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
        $out[(string)$s['name']] = $mapa[$rid] ?? null;
    }
    return $out;
}
function xlsx_hojas($ruta) { $z = xlsx_abrir($ruta); $h = array_keys(xlsx_hojas_info($z)); $z->close(); return $h; }

function xlsx_col($ref) {
    preg_match('/^([A-Z]+)/', $ref, $m); $n = 0;
    foreach (str_split($m[1]) as $ch) $n = $n * 26 + (ord($ch) - 64);
    return $n - 1;
}
function xlsx_es_formato_fecha($id, $custom) {
    if (in_array($id, [14, 15, 16, 17, 18, 19, 20, 21, 22, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 45, 46, 47, 50, 51, 52, 53, 54, 55, 56, 57, 58], true)) return true;
    if (!isset($custom[$id])) return false;
    $c = preg_replace('/"[^"]*"|\[[^\]]*\]|\\\\./', '', $custom[$id]);
    return (bool)preg_match('/[dyDY]/', $c);
}
function xlsx_leer($ruta, $hoja, $max = 5001) {
    $zip = xlsx_abrir($ruta); $info = xlsx_hojas_info($zip);
    if (!isset($info[$hoja]) || !$info[$hoja]) { $zip->close(); throw new RuntimeException('La hoja no existe.'); }
    $compartidas = [];
    if (($ss = xlsx_xml($zip, 'xl/sharedStrings.xml'))) foreach ($ss->si as $si) {
        $t = ''; foreach ($si->xpath('.//*[local-name()="t"]') as $n) $t .= (string)$n; $compartidas[] = $t;
    }
    $custom = []; $xfFecha = [];
    if (($st = xlsx_xml($zip, 'xl/styles.xml'))) {
        if (isset($st->numFmts)) foreach ($st->numFmts->numFmt as $nf) $custom[(int)$nf['numFmtId']] = (string)$nf['formatCode'];
        $i = 0; if (isset($st->cellXfs)) foreach ($st->cellXfs->xf as $xf) { $xfFecha[$i++] = xlsx_es_formato_fecha((int)$xf['numFmtId'], $custom); }
    }
    $sh = xlsx_xml($zip, $info[$hoja]); $zip->close();
    if (!$sh) throw new RuntimeException('No se pudo leer la hoja.');
    $filas = []; $n = 0;
    foreach ($sh->sheetData->row as $r) {
        if (++$n > $max) break;
        $fila = []; $maxc = -1;
        foreach ($r->c as $c) {
            $col = xlsx_col((string)$c['r']); $t = (string)$c['t']; $v = isset($c->v) ? (string)$c->v : '';
            if ($t === 's') $v = $compartidas[(int)$v] ?? '';
            elseif ($t === 'inlineStr') { $v = ''; foreach ($c->xpath('.//*[local-name()="t"]') as $x) $v .= (string)$x; }
            elseif ($t === 'b') $v = $v === '1' ? 'VERDADERO' : 'FALSO';
            elseif ($t === 'str' || $t === 'e') { /* texto tal cual */ }
            elseif ($v !== '' && is_numeric($v)) {
                $f = (float)$v;
                if (!empty($xfFecha[(int)$c['s']]) && $f >= 1) $v = gmdate('Y-m-d', (int)round(($f - 25569) * 86400));
                elseif (floor($f) == $f && abs($f) < 1e15) $v = (string)(int)$f;
            }
            $fila[$col] = $v; if ($col > $maxc) $maxc = $col;
        }
        $out = []; for ($i = 0; $i <= $maxc; $i++) $out[] = $fila[$i] ?? '';
        $filas[(int)$r['r']] = $out;
    }
    return $filas;   // clave = número de fila de Excel (1 = encabezados)
}
