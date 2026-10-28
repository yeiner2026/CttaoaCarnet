<?php $titulo = 'Acceso administrativo'; ?>
<h1 class="h4 mt-3">Acceso administrativo</h1>
<form method="post" class="card card-body shadow-sm"><?= csrf_field() ?>
 <label class="form-label">Usuario</label><input name="usuario" class="form-control mb-3" required autofocus autocomplete="username">
 <label class="form-label">Contraseña</label><input name="password" type="password" class="form-control mb-3" required autocomplete="current-password">
 <button class="btn btn-verde">Entrar</button></form>
<p class="mt-3 small"><a class="btn-volver" href="<?= e(url('/')) ?>">Ir a la consulta de carnet</a></p>
