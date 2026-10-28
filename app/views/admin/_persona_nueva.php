<?php // Datos de una persona NUEVA (cuando la cédula no está registrada). Usa post() para conservar lo escrito. ?>
<div class="alert alert-info py-2 small">Esta cédula no está registrada: completa los datos para crear a la persona y su carnet.</div>
<div class="row g-2 mb-2">
 <div class="col-sm-6"><label class="form-label small">Tipo de documento</label>
  <select class="form-select" name="tipo_documento"><?php foreach (TIPOS_DOC as $k => $t): ?><option value="<?= $k ?>"<?= sel(post('tipo_documento', 'CC'), $k) ?>><?= e($t) ?></option><?php endforeach; ?></select></div>
 <div class="col-sm-6"><label class="form-label small">Nombres</label><input class="form-control" name="nombres" maxlength="100" required value="<?= e(post('nombres')) ?>"></div>
 <div class="col-sm-6"><label class="form-label small">Apellidos</label><input class="form-control" name="apellidos" maxlength="100" required value="<?= e(post('apellidos')) ?>"></div>
 <div class="col-sm-6"><label class="form-label small">Fecha de expedición</label><input class="form-control" type="date" name="fecha_expedicion" required value="<?= e(post('fecha_expedicion')) ?>"></div>
 <div class="col-sm-6"><label class="form-label small">Tipo de sangre (RH)</label><select class="form-select" name="rh" required><option value="">Selecciona…</option><?php foreach (GRUPOS_RH as $g): ?><option<?= sel(post('rh'), $g) ?>><?= e($g) ?></option><?php endforeach; ?></select></div>
 <div class="col-sm-6"><label class="form-label small">Área / dependencia</label><input class="form-control" name="dependencia" maxlength="200" required value="<?= e(post('dependencia')) ?>"></div>
 <div class="col-sm-6"><label class="form-label small">Correo electrónico</label><input class="form-control" type="email" name="email" maxlength="190" required value="<?= e(post('email')) ?>"></div>
 <div class="col-12"><label class="form-label small">Fotografía (opcional, JPG o PNG, máx. 3 MB)</label><input data-quitar-fondo class="form-control" type="file" name="foto_nueva" accept="image/jpeg,image/png"></div>
</div>
