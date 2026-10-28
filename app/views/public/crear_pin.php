<?php $titulo = 'Crear PIN'; ?>
<h1 class="h4 mt-3">Crea tu PIN</h1>
<p class="text-muted">Hola, <strong><?= e(explode(' ', persona_nombres_tc($persona))[0]) ?></strong>. Escribe tu correo electrónico y elige un PIN de 4 dígitos. El correo sirve para que, si algún día olvidas el PIN, puedas crear uno nuevo tú mismo con un enlace seguro.</p>
<form method="post" class="card card-body shadow-sm" autocomplete="off"><?= csrf_field() ?>
 <label class="form-label" for="email">Correo electrónico</label>
 <input id="email" name="email" type="email" class="form-control form-control-lg mb-3" maxlength="120" value="<?= e($email) ?>" autocomplete="email" required autofocus>
 <label class="form-label" for="pin">PIN nuevo (4 dígitos)</label>
 <input id="pin" name="pin" type="password" class="form-control form-control-lg mb-3" inputmode="numeric" maxlength="4" pattern="\d{4}" required>
 <label class="form-label" for="pin2">Confirmar PIN</label>
 <input id="pin2" name="pin2" type="password" class="form-control form-control-lg mb-3" inputmode="numeric" maxlength="4" pattern="\d{4}" required>
 <button class="btn btn-verde btn-lg">Guardar</button></form>
<p class="small mt-3"><a class="btn-volver" href="<?= e(url('/')) ?>">Cancelar</a></p>
