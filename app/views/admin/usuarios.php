<?php $titulo = 'Usuarios y permisos'; $yo = admin_actual(); ?>
<h1 class="h4">Usuarios y permisos</h1>
<p class="text-muted small">Solo los administradores principales gestionan esta pantalla. Cada administrador tiene también su carnet.</p>
<?php foreach ($err as $x): ?><div class="alert alert-danger"><?= e($x) ?></div><?php endforeach; ?>
<div class="row g-3">
<div class="col-lg-5">
 <div class="card card-body mb-3"><h2 class="h6">Nuevo administrador</h2>
  <form method="get" class="d-flex gap-2 mb-0"><input name="cedula" class="form-control" placeholder="1. Escribe su cédula" value="<?= e($cedula) ?>" inputmode="numeric"><button class="btn btn-outline-success">Buscar</button></form></div>
 <?php if ($cedula !== ''): ?>
 <form method="post" enctype="multipart/form-data" class="card card-body"><?= csrf_field() ?><input type="hidden" name="cedula" value="<?= e($cedula) ?>">
  <h2 class="h6">2. Datos de acceso</h2>
  <?php if ($encontrada && $yaAdmin): ?><div class="alert alert-warning">La persona <strong><?= e(persona_nombre($encontrada)) ?></strong> ya tiene acceso de administrador.</div>
  <?php elseif ($encontrada): ?><div class="alert alert-success py-2 small">Persona encontrada: <strong><?= e(persona_nombre($encontrada)) ?></strong> (<?= e(TIPOS_PERSONA[$encontrada['tipo_persona']]) ?>). Se usará su registro y su carnet.</div>
  <?php else: include __DIR__ . '/_persona_nueva.php'; endif; ?>
  <?php if (!($encontrada && $yaAdmin)): ?>
  <label class="form-label">Usuario para iniciar sesión</label><input name="usuario" class="form-control mb-2" required maxlength="60" value="<?= e(post('usuario')) ?>" autocomplete="off">
  <label class="form-label">Contraseña (mín. 10 caracteres)</label><input name="password" type="password" class="form-control mb-2" required autocomplete="new-password">
  <div class="mb-2"><div class="small fw-semibold">Permisos</div><?php foreach (PERMISOS as $k => $t): ?><div class="form-check"><input class="form-check-input" type="checkbox" name="permisos[]" value="<?= $k ?>" id="np_<?= $k ?>"<?= chk(in_array($k, (array)($_POST['permisos'] ?? []), true)) ?>><label class="form-check-label small" for="np_<?= $k ?>"><?= e($t) ?></label></div><?php endforeach; ?></div>
  <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="rol" value="principal" id="np_rol"><label class="form-check-label small" for="np_rol"><strong>Administrador principal</strong> (poder total: ve todo y gestiona usuarios)</label></div>
  <button class="btn btn-success" type="submit">Crear administrador</button><?php endif; ?>
 </form><?php endif; ?>
</div>
<div class="col-lg-7">
<?php foreach ($admins as $a): $perm = array_filter(explode(',', (string)$a['permisos'])); $esYo = (int)$a['id'] === (int)$yo['id']; ?>
 <div class="card card-body mb-2">
  <div class="d-flex flex-wrap justify-content-between gap-2"><div><strong><?= e($a['usuario']) ?></strong> <span class="badge bg-<?= $a['rol'] === 'principal' ? 'dark' : 'secondary' ?>"><?= $a['rol'] === 'principal' ? 'Principal' : 'Autorizado' ?></span><?php if ($esYo): ?> <span class="badge bg-success">Tu cuenta</span><?php endif; ?><?php if (!$a['activo']): ?> <span class="badge bg-danger">Inactivo</span><?php endif; ?>
   <div class="small text-muted"><?php if ($a['persona_id']): ?><?= e($a['nombres'] . ' ' . $a['apellidos']) ?> · <?= e($a['numero_documento']) ?> · <a href="<?= e(admin_url('/personas/' . $a['persona_id'])) ?>">Ver carnet</a><?php else: ?>Sin carnet vinculado · <a href="<?= e(admin_url('/usuarios/' . $a['id'] . '/vincular')) ?>">Vincular persona</a><?php endif; ?></div></div></div>
  <?php if ($esYo): ?>
   <p class="small text-muted mb-0 mt-2">Para cambiar tus datos o tu contraseña usa <a href="<?= e(admin_url('/cuenta')) ?>">Mi cuenta</a>.</p>
  <?php else: ?>
  <form method="post" action="<?= e(admin_url('/usuarios/' . $a['id'] . '/permisos')) ?>" class="mt-2"><?= csrf_field() ?>
   <div class="small fw-semibold"><?= $a['rol'] === 'admin' ? 'Permisos' : 'Permisos que tendría al pasar a autorizado' ?></div>
   <div class="d-flex flex-wrap gap-2 my-2"><?php foreach (PERMISOS as $k => $t): ?><label class="small"><input type="checkbox" name="permisos[]" value="<?= $k ?>"<?= chk(in_array($k, $perm, true)) ?>> <?= e($t) ?></label><?php endforeach; ?></div>
   <label class="small"><input type="checkbox" name="activo" value="1"<?= chk($a['activo']) ?>> Cuenta activa</label>
   <div class="mt-2 d-flex flex-wrap gap-2">
    <button class="btn btn-sm btn-outline-success" type="submit" name="accion" value="guardar"><?= $a['rol'] === 'admin' ? 'Guardar permisos' : 'Guardar estado de la cuenta' ?></button>
    <?php if ($a['rol'] === 'admin'): ?>
     <button class="btn btn-sm btn-outline-dark" type="submit" name="accion" value="principal" data-confirmar="¿Convertir a este usuario en administrador principal (poder total)?">Hacer principal</button>
    <?php else: ?>
     <button class="btn btn-sm btn-outline-warning" type="submit" name="accion" value="autorizado" data-confirmar="¿Quitarle el poder total? Pasará a administrador autorizado con los permisos marcados.">Quitar poder total (pasar a autorizado)</button>
    <?php endif; ?>
   </div></form>
  <form method="post" action="<?= e(admin_url('/usuarios/' . $a['id'] . '/quitar')) ?>" class="mt-2" data-confirmar="¿Quitar el acceso de administrador a «<?= e($a['usuario']) ?>»? La persona y su carnet se conservan."><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Quitar acceso de administrador</button></form>
  <?php endif; ?></div>
<?php endforeach; ?></div></div>
