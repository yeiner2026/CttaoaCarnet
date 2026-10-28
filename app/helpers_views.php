<?php
function flashes_html() {
    $h = '';
    foreach (flashes() as $f) {
        $t = $f[0] === 'error' ? 'danger' : $f[0];
        if (!empty($f[2])) $h .= '<div class="alert alert-' . e($t) . ' alert-dismissible" role="alert">' . e($f[1]) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button></div>';
        else $h .= '<div class="alert alert-' . e($t) . '" role="alert" data-flash>' . e($f[1]) . '</div>';
    }
    return $h;
}
function sel($a, $b) { return (string)$a === (string)$b ? ' selected' : ''; }
function chk($v) { return !empty($v) ? ' checked' : ''; }
function badge_carne($estado) {
    $c = $estado === 'activo' ? 'success' : ($estado === 'inactivo' ? 'danger' : 'warning text-dark');
    return '<span class="badge bg-' . $c . '">' . e(ESTADOS_CARNE[$estado] ?? $estado) . '</span>';
}
function foto_mini($p) {
    return $p['foto'] !== '' ? '<img class="mini-foto" src="' . e(url('/privado/foto/' . $p['id'])) . '" alt="">' : '<span class="badge bg-secondary">Sin foto</span>';
}
function paginador($pg, $extra = []) {
    if ($pg['paginas'] <= 1) return '';
    $h = '<div class="d-flex gap-2 align-items-center my-2">';
    if ($pg['page'] > 1) $h .= '<a class="btn btn-sm btn-outline-secondary" href="?' . e(qs($extra)) . '&page=' . ($pg['page'] - 1) . '">Anterior</a>';
    $h .= '<span class="small">Página ' . $pg['page'] . ' de ' . $pg['paginas'] . '</span>';
    if ($pg['page'] < $pg['paginas']) $h .= '<a class="btn btn-sm btn-outline-secondary" href="?' . e(qs($extra)) . '&page=' . ($pg['page'] + 1) . '">Siguiente</a>';
    return $h . '</div>';
}
function campo_error($err, $k) { return isset($err[$k]) ? '<div class="text-danger small">' . e($err[$k]) . '</div>' : ''; }

// ---------- pie de página (firma del proyecto) ----------
function pie_pagina() {
    return '<footer class="pie">' . e(cfg('footer', '© 2026 CTTAOA · Desarrollado por Yeiner Castillo · Ficha 3134228'))
        . '<br><a href="' . e(url('/privacidad')) . '">Política de privacidad y términos</a></footer>';
}

// ---------- gráficas SVG (sin librerías; usan los colores del tema, claro u oscuro) ----------
function svg_barras($labels, $vals) {
    $n = max(1, count($vals)); $max = max(1, max($vals)); $w = 340; $h = 190; $base = 150; $top = 24; $paso = ($w - 20) / $n; $bw = $paso * 0.58; $o = '';
    for ($i = 0; $i < $n; $i++) {
        $alto = ($vals[$i] / $max) * ($base - $top); $x = 10 + $i * $paso + ($paso - $bw) / 2; $y = $base - $alto;
        $o .= '<rect x="' . round($x, 1) . '" y="' . round($y, 1) . '" width="' . round($bw, 1) . '" height="' . round(max($alto, $vals[$i] > 0 ? 2 : 0), 1) . '" rx="4" fill="var(--verde)" opacity="' . ($vals[$i] > 0 ? '1' : '.25') . '"/>';
        $o .= '<text x="' . round($x + $bw / 2, 1) . '" y="' . round($y - 5, 1) . '" text-anchor="middle" class="g-val">' . (int)$vals[$i] . '</text>';
        $o .= '<text x="' . round($x + $bw / 2, 1) . '" y="' . ($base + 16) . '" text-anchor="middle" class="g-lab">' . e(explode(' ', $labels[$i])[0]) . '</text>';
        $o .= '<text x="' . round($x + $bw / 2, 1) . '" y="' . ($base + 29) . '" text-anchor="middle" class="g-lab2">' . e(explode(' ', $labels[$i])[1] ?? '') . '</text>';
    }
    return '<svg viewBox="0 0 ' . $w . ' ' . $h . '" role="img" aria-label="Gráfica de barras" class="grafica"><line x1="8" x2="' . ($w - 8) . '" y1="' . $base . '" y2="' . $base . '" class="g-eje"/>' . $o . '</svg>';
}
function svg_dona($items, $centro, $subtitulo = '') {
    $total = 0; foreach ($items as $it) $total += $it[1];
    $r = 42; $c = 2 * M_PI * $r; $acum = 0; $o = '<circle cx="60" cy="60" r="' . $r . '" fill="none" class="g-fondo" stroke-width="16"/>';
    if ($total > 0) foreach ($items as $it) {
        if ($it[1] <= 0) continue;
        $len = $it[1] / $total * $c;
        $o .= '<circle cx="60" cy="60" r="' . $r . '" fill="none" stroke="' . $it[2] . '" stroke-width="16" stroke-dasharray="' . round($len, 2) . ' ' . round($c - $len, 2) . '" stroke-dashoffset="' . round(-$acum, 2) . '" transform="rotate(-90 60 60)"/>';
        $acum += $len;
    }
    $o .= '<text x="60" y="' . ($subtitulo ? 58 : 66) . '" text-anchor="middle" class="g-centro">' . e($centro) . '</text>';
    if ($subtitulo) $o .= '<text x="60" y="74" text-anchor="middle" class="g-lab">' . e($subtitulo) . '</text>';
    return '<svg viewBox="0 0 120 120" role="img" aria-label="Gráfica circular" class="grafica-dona">' . $o . '</svg>';
}
function leyenda($items) {
    $h = '<ul class="leyenda">';
    foreach ($items as $it) $h .= '<li><span class="punto-l" style="background:' . $it[2] . '"></span>' . e($it[0]) . ' <strong>' . (int)$it[1] . '</strong></li>';
    return $h . '</ul>';
}
function svg_hbarras($items) {
    $max = 1; foreach ($items as $it) $max = max($max, $it[1]); $h = '';
    foreach ($items as $it) $h .= '<div class="hb"><div class="hb-top"><span>' . e($it[0]) . '</span><strong>' . (int)$it[1] . '</strong></div><div class="hb-pista"><div class="hb-barra" style="width:' . round($it[1] / $max * 100) . '%;background:' . $it[2] . '"></div></div></div>';
    return $h;
}
