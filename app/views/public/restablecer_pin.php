<?php $titulo = 'Crear PIN nuevo'; ?>
<?php if (!$valido): ?>
<h1 class="h4 mt-3">Este enlace ya no sirve</h1>
<p class="text-muted">El enlace venció, ya se usó o no es válido. Pide uno nuevo desde «Olvidé PIN».</p>
<p><a class="btn btn-verde" href="<?= e(url('/')) ?>">Ir al inicio</a></p>
<?php else: ?>
<h1 class="h4 mt-3">Crea tu PIN nuevo</h1>
<p class="text-muted">Hola, <strong><?= e(explode(' ', persona_nombres_tc($persona))[0]) ?></strong>. Elige un PIN de 4 dígitos. El enlace quedará inutilizado al guardarlo.</p>
<form method="post" class="card card-body shadow-sm" autocomplete="off"><?= csrf_field() ?><input type="hidden" name="t" value="<?= e($token) ?>">
 <label class="form-label" for="pin">PIN nuevo (4 dígitos)</label>
 <input id="pin" name="pin" type="password" class="form-control form-control-lg mb-3" inputmode="numeric" maxlength="4" pattern="\d{4}" required autofocus>
 <label class="form-label" for="pin2">Confirmar PIN</label>
 <input id="pin2" name="pin2" type="password" class="form-control form-control-lg mb-3" inputmode="numeric" maxlength="4" pattern="\d{4}" required>
 <button class="btn btn-verde btn-lg">Guardar PIN</button></form>
<?php endif; ?>
