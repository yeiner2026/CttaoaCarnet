<?php $titulo = 'Diseño de plantilla'; $vv = strtotime($pl['actualizada'] ?: 'now'); ?>
<h1 class="h4">Diseño de plantilla</h1>
<?php foreach ($err as $x): ?><div class="alert alert-danger"><?= e($x) ?></div><?php endforeach; ?>
<div class="row g-4"><div class="col-lg-6"><form method="post" enctype="multipart/form-data" class="card card-body" data-carga><?= csrf_field() ?>
 <label class="form-label mt-2">Regional</label><input class="form-control" name="regional" value="<?= e($pl['regional']) ?>" maxlength="100" required>
 <label class="form-label mt-2">Complejo</label><input class="form-control" name="complejo" value="<?= e($pl['complejo']) ?>" maxlength="200" required>
 <label class="form-label mt-2">Texto del reverso</label><textarea class="form-control" name="texto_reverso" rows="5" required><?= e($pl['texto_reverso']) ?></textarea>
 <label class="form-label mt-2">Rótulo de la firma</label><input class="form-control" name="etiqueta_firma" value="<?= e($pl['etiqueta_firma']) ?>" maxlength="60" required>
 <label class="form-label mt-2">Texto en caso de pérdida</label><textarea class="form-control" name="texto_perdida" rows="3"><?= e($pl['texto_perdida']) ?></textarea>
 <label class="form-label mt-3">Logotipo (JPG/PNG; el PNG conserva la transparencia)</label>
 <div class="d-flex gap-3 align-items-center mb-2"><img id="prev-logo" class="preview-logo" alt="Vista previa del logotipo" <?= !empty($pl['logo']) ? 'src="' . e(url('/privado/institucional/logo')) . '?v=' . $vv . '"' : '' ?>><input type="file" name="logo" accept="image/png,image/jpeg" class="form-control" data-preview="prev-logo"></div>
 <label class="form-label mt-2">Firma autorizada (JPG/PNG)</label>
 <div class="d-flex gap-3 align-items-center"><img id="prev-firma" class="preview-logo" alt="Vista previa de la firma" <?= !empty($pl['firma']) ? 'src="' . e(url('/privado/institucional/firma')) . '?v=' . $vv . '"' : '' ?>><input type="file" name="firma" accept="image/png,image/jpeg" class="form-control" data-preview="prev-firma"></div>
 <button class="btn btn-success mt-3" type="submit">Guardar plantilla</button></form></div>
<div class="col-lg-6"><p class="small text-muted">Vista previa con datos ficticios (se actualiza al guardar).</p><?= carne_html($demo, ['chica' => true, 'demo' => true, 'borrador' => false]) ?></div></div>
