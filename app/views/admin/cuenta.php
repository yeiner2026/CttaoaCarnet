<?php
$titulo = 'Mi cuenta'; $perm = array_filter(explode(',', (string)$u['permisos']));
$errCarne = $errCarne ?? [];
$v = function ($k, $d = '') { return isset($_POST[$k]) ? (string)$_POST[$k] : $d; };
?>
<h1 class="h4">Mi cuenta</h1>
<div class="row g-3">
<div class="col-lg-6">

 <?php if (!$p): ?>
 <form method="post" enctype="multipart/form-data" class="card card-body mb-3 border-success" id="carne"><?= csrf_field() ?><input type="hidden" name="accion" value="carne">
  <h2 class="h6">Mi carnet · completa tus datos</h2>
  <p class="small text-muted">Tu cuenta de administrador todavía no tiene cédula, nombre ni cargo registrados. Complétalos para tener <strong>tu propio carnet</strong> y poder mostrarlo.</p>
  <div class="row g-2">
   <div class="col-sm-6"><label class="form-label small">Tipo de documento</label>
    <select name="tipo_documento" class="form-select"><?php foreach (TIPOS_DOC as $k => $t): ?><option value="<?= $k ?>"<?= sel($v('tipo_documento', 'CC'), $k) ?>><?= e($t) ?></option><?php endforeach; ?></select><?= campo_error($errCarne, 'tipo_documento') ?></div>
   <div class="col-sm-6"><label class="form-label small">Número de cédula / documento</label>
    <input name="numero_documento" class="form-control" inputmode="numeric" maxlength="20" required value="<?= e($v('numero_documento')) ?>"><?= campo_error($errCarne, 'numero_documento') ?></div>
   <div class="col-sm-6"><label class="form-label small">Nombres</label><input name="nombres" class="form-control" maxlength="100" required value="<?= e($v('nombres')) ?>"><?= campo_error($errCarne, 'nombres') ?></div>
   <div class="col-sm-6"><label class="form-label small">Apellidos</label><input name="apellidos" class="form-control" maxlength="100" required value="<?= e($v('apellidos')) ?>"><?= campo_error($errCarne, 'apellidos') ?></div>
   <div class="col-sm-6"><label class="form-label small">Fecha de expedición del documento</label><input name="fecha_expedicion" type="date" class="form-control" required value="<?= e($v('fecha_expedicion')) ?>"><?= campo_error($errCarne, 'fecha_expedicion') ?></div>
   <div class="col-sm-6"><label class="form-label small">Tipo de sangre (RH)</label>
    <select name="rh" class="form-select" required><option value="">Selecciona…</option><?php foreach (GRUPOS_RH as $g): ?><option<?= sel($v('rh'), $g) ?>><?= e($g) ?></option><?php endforeach; ?></select><?= campo_error($errCarne, 'rh') ?></div>
   <div class="col-sm-6"><label class="form-label small">Soy</label>
    <select name="tipo_persona" class="form-select"><option value="funcionario"<?= sel($v('tipo_persona', 'funcionario'), 'funcionario') ?>>Funcionario</option><option value="instructor"<?= sel($v('tipo_persona'), 'instructor') ?>>Instructor</option></select></div>
   <div class="col-12"><label class="form-label small">Cargo, área o dependencia</label><input name="dependencia" class="form-control" maxlength="200" required placeholder="Ej.: Coordinación académica" value="<?= e($v('dependencia')) ?>"><?= campo_error($errCarne, 'dependencia') ?></div>
   <div class="col-12"><label class="form-label small">Correo electrónico</label><input name="email" type="email" class="form-control" maxlength="190" required value="<?= e($v('email', $u['email'])) ?>"><?= campo_error($errCarne, 'email') ?></div>
   <div class="col-12"><label class="form-label small">Fotografía (opcional, JPG o PNG, máx. 3 MB; también puedes subirla después)</label><input data-quitar-fondo name="foto" type="file" accept="image/jpeg,image/png" class="form-control"></div>
  </div>
  <button class="btn btn-success mt-3 align-self-start" type="submit">Crear mi carnet</button>
 </form>
 <?php else: ?>
 <div class="card card-body mb-3 border-success" id="carne">
  <h2 class="h6">Mi carnet</h2>
  <p class="small mb-2"><?= e(TIPOS_DOC[$p['tipo_documento']] ?? '') ?> <strong><?= e($p['numero_documento']) ?></strong> · <?= e(TIPOS_PERSONA[$p['tipo_persona']]) ?></p>
  <a class="btn btn-outline-success align-self-start" href="<?= e(admin_url('/mi-carne')) ?>">Ver mi carnet</a>
 </div>
 <?php endif; ?>

 <form method="post" class="card card-body mb-3"><?= csrf_field() ?><input type="hidden" name="accion" value="datos">
  <h2 class="h6">Datos personales</h2>
  <?php if ($p): ?>
   <div class="row g-2"><div class="col-sm-6"><label class="form-label small">Nombres</label><input name="nombres" class="form-control" maxlength="100" required value="<?= e($p['nombres']) ?>"></div>
   <div class="col-sm-6"><label class="form-label small">Apellidos</label><input name="apellidos" class="form-control" maxlength="100" required value="<?= e($p['apellidos']) ?>"></div></div>
   <label class="form-label small mt-2">Cédula / documento (solo la modifica un administrador principal)</label><input class="form-control" value="<?= e($p['numero_documento']) ?>" disabled>
   <?php if ($p['tipo_persona'] !== 'aprendiz'): ?><label class="form-label small mt-2">Cargo, área o dependencia</label><input name="dependencia" class="form-control" maxlength="200" required value="<?= e($p['dependencia']) ?>"><?php endif; ?>
   <div class="row g-2 mt-0"><div class="col-sm-6"><label class="form-label small mt-2">Fecha de expedición del documento</label><input name="fecha_expedicion" type="date" class="form-control" required value="<?= e($p['fecha_expedicion']) ?>"></div>
   <div class="col-sm-6"><label class="form-label small mt-2">Tipo de sangre (RH)</label><select name="rh" class="form-select" required><option value="">Selecciona…</option><?php foreach (GRUPOS_RH as $g): ?><option<?= sel($p['rh'], $g) ?>><?= e($g) ?></option><?php endforeach; ?></select></div></div>
  <?php else: ?>
   <label class="form-label small">Nombre</label><input name="nombre" class="form-control" maxlength="100" required value="<?= e($u['nombre']) ?>">
  <?php endif; ?>
  <label class="form-label small mt-2">Nombre de usuario</label><input name="usuario" class="form-control" maxlength="60" required value="<?= e($u['usuario']) ?>" autocomplete="username">
  <label class="form-label small mt-2">Correo electrónico</label><input name="email" type="email" class="form-control" maxlength="190" value="<?= e($p ? $p['email'] : $u['email']) ?>" autocomplete="email">
  <button class="btn btn-success mt-3 align-self-start" type="submit">Guardar mis datos</button>
 </form>
 <form method="post" class="card card-body mb-3"><?= csrf_field() ?><input type="hidden" name="accion" value="password">
  <h2 class="h6">Cambiar contraseña</h2>
  <label class="form-label small">Contraseña actual</label><input type="password" name="actual" class="form-control mb-2" required autocomplete="current-password">
  <label class="form-label small">Contraseña nueva (mínimo 10 caracteres)</label><input type="password" name="nueva" class="form-control mb-2" required autocomplete="new-password">
  <label class="form-label small">Confirmar contraseña nueva</label><input type="password" name="nueva2" class="form-control mb-3" required autocomplete="new-password">
  <button class="btn btn-success align-self-start" type="submit">Cambiar contraseña</button>
 </form>
 <div class="card card-body">
  <h2 class="h6">Información del acceso</h2>
  <dl class="row mb-0 small">
   <dt class="col-5">Rol</dt><dd class="col-7"><?= $u['rol'] === 'principal' ? 'Administrador principal' : 'Administrador autorizado' ?></dd>
   <dt class="col-5">Estado de la cuenta</dt><dd class="col-7"><?= $u['activo'] ? '<span class="badge bg-success">Activa</span>' : '<span class="badge bg-danger">Inactiva</span>' ?></dd>
   <dt class="col-5">Permisos</dt><dd class="col-7"><?php if ($u['rol'] === 'principal'): ?>Todos (poder total)<?php elseif ($perm): ?><ul class="mb-0 ps-3"><?php foreach ($perm as $k): ?><li><?= e(PERMISOS[$k] ?? $k) ?></li><?php endforeach; ?></ul><?php else: ?>Ninguno asignado<?php endif; ?></dd>
   <dt class="col-5">Cuenta creada</dt><dd class="col-7"><?= e(hora_legible($u['creado'])) ?></dd>
   <dt class="col-5">Último acceso</dt><dd class="col-7"><?= $u['ultimo_acceso'] ? e(hora_legible($u['ultimo_acceso'])) . ' · IP ' . e($u['ultima_ip']) : 'Sin registro' ?></dd>
  </dl>
  <p class="small text-muted mb-0 mt-2">Esta información es solo de consulta. La modifica un administrador principal.</p>
 </div>
</div>
<div class="col-lg-6">
 <div class="card card-body mb-3">
  <h2 class="h6">Actividad reciente</h2>
  <div class="small fw-semibold mb-1">Últimos accesos</div>
  <?php if ($accesos): ?><ul class="small mb-3"><?php foreach ($accesos as $a): ?><li><?= e(hora_legible($a['fecha'])) ?> · <?= e($a['detalle']) ?></li><?php endforeach; ?></ul>
  <?php else: ?><p class="small text-muted">No hay accesos registrados todavía.</p><?php endif; ?>
  <div class="small fw-semibold mb-1">Mis acciones recientes</div>
  <?php if ($acciones): ?><div class="table-responsive"><table class="table table-sm small mb-0"><thead><tr><th>Fecha</th><th>Acción</th><th>Detalle</th></tr></thead><tbody>
   <?php foreach ($acciones as $a): ?><tr><td class="text-nowrap"><?= e(hora_legible($a['fecha'])) ?></td><td><?= e($a['accion']) ?></td><td class="text-break"><?= e($a['objeto'] . ' ' . $a['objeto_id'] . ' ' . $a['detalle']) ?></td></tr><?php endforeach; ?></tbody></table></div>
  <?php else: ?><p class="small text-muted mb-0">Aún no has hecho acciones registradas.</p><?php endif; ?>
  <p class="small text-muted mb-0 mt-2">El historial se conserva solo unos días y luego se borra automáticamente.</p>
 </div>
 <div class="card card-body">
  <h2 class="h6">Cerrar sesión</h2>
  <p class="small text-muted">Finaliza tu sesión de forma segura. Hazlo siempre al terminar, sobre todo en equipos compartidos.</p>
  <form method="post" action="<?= e(admin_url('/logout')) ?>"><?= csrf_field() ?><button class="btn btn-outline-danger" type="submit">Cerrar sesión</button></form>
 </div>
</div></div>
