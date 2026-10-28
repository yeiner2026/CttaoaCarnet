<?php $titulo = 'Aceptar términos'; $ver = cfg('politica_version', '1.0'); ?>
<h1 class="h4 mt-3">Antes de continuar</h1>
<p class="text-muted">Para usar tu carnet digital necesitamos que leas y aceptes los términos y condiciones y la política de privacidad y tratamiento de datos personales (versión <?= e($ver) ?>).</p>
<form method="post" class="card card-body shadow-sm" data-acepto><?= csrf_field() ?>
 <div class="form-check mb-3">
  <input class="form-check-input" type="checkbox" id="acepto" name="acepto" value="1" required>
  <label class="form-check-label" for="acepto">Acepto los <a href="<?= e(url('/privacidad#terminos')) ?>" target="_blank" rel="noopener">términos y condiciones</a> y la <a href="<?= e(url('/privacidad')) ?>" target="_blank" rel="noopener">política de privacidad y tratamiento de datos personales</a>.</label>
 </div>
 <button class="btn btn-verde btn-lg" type="submit">Aceptar y continuar</button>
</form>
<form method="post" action="<?= e(url('/salir')) ?>" class="mt-3"><?= csrf_field() ?><button class="btn btn-link btn-sm">No acepto · cerrar sesión</button></form>
