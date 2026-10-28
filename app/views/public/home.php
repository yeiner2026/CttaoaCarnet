<?php $titulo = 'Consultar mi carnet'; ?>
<section class="hero">
 <h1>Tu carnet digital, siempre a la mano</h1>
 <p>Complejo Tecnológico, Turístico y Agroindustrial del Occidente Antioqueño. Consulta tu carnet de aprendiz, instructor, funcionario o contratista de forma segura.</p>
 <p class="mt-3"><a href="<?= e(url('/verificar')) ?>" class="btn btn-light btn-lg px-4">Verificar Carnet</a></p>
</section>
<section id="consulta" class="consulta">
 <?= flashes_html() ?>
 <div class="card"><div class="card-body p-3 p-sm-4">
  <ul class="nav nav-pills nav-fill mb-3" role="tablist">
   <li class="nav-item"><button class="nav-link <?= $tab === 'activar' ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#t-activar" type="button" role="tab">Primera vez</button></li>
   <li class="nav-item"><button class="nav-link <?= $tab === 'pin' ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#t-pin" type="button" role="tab">Tengo PIN</button></li>
   <li class="nav-item"><button class="nav-link <?= $tab === 'olvide' ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#t-olvide" type="button" role="tab">Olvidé PIN</button></li>
  </ul>
  <div class="tab-content">
   <div class="tab-pane fade <?= $tab === 'activar' ? 'show active' : '' ?>" id="t-activar" role="tabpanel">
    <p class="text-muted small">Verifica tu identidad con los datos de tu documento. Solo se piden esta vez: después crearás un PIN de 4 dígitos.</p>
    <form method="post" autocomplete="off" data-acepto><?= csrf_field() ?><input type="hidden" name="accion" value="activar">
     <label class="form-label" for="a-doc">Número de documento</label>
     <input id="a-doc" name="documento" class="form-control form-control-lg mb-3" inputmode="numeric" required>
     <label class="form-label" for="a-fecha">Fecha de expedición del documento</label>
     <input id="a-fecha" name="fecha_expedicion" type="date" class="form-control form-control-lg mb-3" required>
     <div class="form-check mb-3">
      <input class="form-check-input" type="checkbox" id="a-acepto" name="acepto" value="1" required>
      <label class="form-check-label small" for="a-acepto">Acepto los <a href="<?= e(url('/privacidad#terminos')) ?>" target="_blank" rel="noopener">términos y condiciones</a> y la <a href="<?= e(url('/privacidad')) ?>" target="_blank" rel="noopener">política de privacidad y tratamiento de datos personales</a>.</label>
     </div>
     <button class="btn btn-verde btn-lg w-100" type="submit">Verificar</button></form></div>
   <div class="tab-pane fade <?= $tab === 'pin' ? 'show active' : '' ?>" id="t-pin" role="tabpanel">
    <form method="post" autocomplete="off"><?= csrf_field() ?><input type="hidden" name="accion" value="pin">
     <?php if ($recordada): ?><p class="mb-3">Hola, <strong><?= e(explode(' ', trim($recordada['nombres']))[0]) ?></strong>. Ingresa tu PIN.</p>
     <?php else: ?><label class="form-label" for="p-doc">Número de documento</label>
     <input id="p-doc" name="documento" class="form-control form-control-lg mb-3" inputmode="numeric" required><?php endif; ?>
     <label class="form-label" for="p-pin">PIN (4 dígitos)</label>
     <input id="p-pin" name="pin" type="password" class="form-control form-control-lg mb-3" inputmode="numeric" maxlength="4" pattern="\d{4}" required>
     <button class="btn btn-verde btn-lg w-100">Ingresar</button></form>
    <?php if ($recordada): ?><form method="post" action="<?= e(url('/cambiar-documento')) ?>" class="mt-3 text-center"><?= csrf_field() ?><button class="btn btn-link btn-sm">No soy yo · consultar otro documento</button></form><?php endif; ?></div>
   <div class="tab-pane fade <?= $tab === 'olvide' ? 'show active' : '' ?>" id="t-olvide" role="tabpanel">
    <p class="text-muted small">Escribe tu número de documento y te enviamos un enlace seguro al correo que registraste. Con él eliges un PIN nuevo; tú no necesitas pedirle nada al administrador.</p>
    <form method="post" autocomplete="off"><?= csrf_field() ?><input type="hidden" name="accion" value="olvide">
     <label class="form-label" for="o-doc">Número de documento</label>
     <input id="o-doc" name="documento" class="form-control form-control-lg mb-3" inputmode="numeric" required>
     <button class="btn btn-verde btn-lg w-100">Enviarme el enlace</button></form>
    <p class="small text-muted mt-3 mb-0">¿Ya no tienes acceso a ese correo? Pide al administrador del complejo que actualice tu correo y vuelve a intentarlo.</p></div>
  </div>
 </div></div>

</section>
