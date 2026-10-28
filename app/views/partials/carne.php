<?php
// Variables: $p (persona con joins), $pl (plantilla), $barcode, $borrador, $chica, $demo
$estado = $p['carne_estado'] ?? 'pendiente';
$nombre = persona_nombre($p);
$n1 = persona_nombres_tc($p);
$n2 = persona_apellidos_tc($p);
$mx = max(mb_strlen($n1), mb_strlen($n2));
$cls = $mx > 29 ? 'muy-largo' : ($mx > 22 ? 'largo' : '');
$v = strtotime($pl['actualizada']);
?>
<div class="escena <?= $chica ? 'chica' : '' ?>" role="button" tabindex="0" aria-pressed="false" aria-label="Carnet: pulsa o desliza para girarlo">
    <div class="carne">
        <div class="cara <?= $estado === 'inactivo' ? 'c-inactivo' : '' ?>">
            <div class="c-top">
                <div class="c-logo">
                    <?php if ($pl['logo'] !== ''): ?><img src="<?= e(url('/privado/institucional/logo')) ?>?v=<?= $v ?>" alt="Logotipo institucional">
                    <?php else: ?><span class="ph">LOGO</span><?php endif; ?>
                </div>
                <div class="c-foto">
                    <?php if (!empty($p['id']) && $p['foto'] !== ''): ?><img src="<?= e(url('/privado/foto/' . $p['id'])) ?>" alt="Fotografía"><?php else: ?>Sin foto<?php endif; ?>
                </div>
            </div>
            <div class="c-tipo"><?= e(TIPOS_PERSONA[$p['tipo_persona']]) ?></div>
            <div class="c-nombre <?= $cls ?>"><span class="c-nom1"><?= e($n1) ?></span><span class="c-nom2"><?= e($n2) ?></span></div>
            <div class="c-doc"><span><?= e(TIPOS_DOC[$p['tipo_documento']] ?? $p['tipo_documento']) ?> <?= e($p['numero_documento']) ?></span>
                <?php if (persona_rh_visible($p)): ?><span>Rh <?= e(persona_rh_visible($p)) ?></span><?php endif; ?></div>
            <div class="c-barras" aria-label="Código de barras"><?= $barcode ?></div>
            <div class="c-reg"><?= e($pl['regional']) ?></div>
            <div class="c-complejo"><?= e($pl['complejo']) ?></div>
            <div class="c-prog"><?= e(persona_programa_o_area($p)) ?></div>
            <?php if ($p['tipo_persona'] === 'aprendiz' && !empty($p['ficha_numero'])): ?><div class="c-ficha">Grupo No. <?= e($p['ficha_numero']) ?></div><?php endif; ?>
            <?php if (!$demo && $estado === 'inactivo'): ?><div class="c-borrador">INACTIVO</div>
            <?php elseif ($borrador && !$demo): ?><div class="c-borrador">BORRADOR</div><?php endif; ?>
        </div>
        <div class="cara dorso">
            <div class="d-texto"><?= e($pl['texto_reverso']) ?></div>
            <div class="d-firmabloque">
                <div class="d-firma"><?php if ($pl['firma'] !== ''): ?><img src="<?= e(url('/privado/institucional/firma')) ?>?v=<?= $v ?>" alt="Firma autorizada"><?php endif; ?></div>
                <div class="d-etq"><?= e($pl['etiqueta_firma']) ?></div>
            </div>
            <div class="d-perdida"><?= e($pl['texto_perdida']) ?></div>
        </div>
    </div>
</div>
<p class="pista" aria-hidden="true">↺ Toca o desliza el carnet para girarlo</p>