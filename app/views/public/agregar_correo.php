<?php $titulo = 'Agrega tu correo'; ?>
<h1 class="h4 mt-3">Agrega tu correo electrónico</h1>
<p class="text-muted">Para cuidar tu cuenta necesitamos un correo tuyo. Si algún día olvidas tu PIN, te enviaremos allí un enlace seguro para crear uno nuevo. Solo lo verá el sistema y el administrador del complejo.</p>
<form method="post" class="card card-body shadow-sm" autocomplete="off"><?= csrf_field() ?>
 <label class="form-label" for="email">Correo electrónico</label>
 <input id="email" name="email" type="email" class="form-control form-control-lg mb-3" maxlength="120" value="<?= e($email) ?>" autocomplete="email" required autofocus>
 <button class="btn btn-verde btn-lg">Guardar y continuar</button></form>
<form method="post" action="<?= e(url('/salir')) ?>" class="mt-3"><?= csrf_field() ?><button class="btn btn-link btn-sm">Salir</button></form>
