<?php $titulo = 'Mi carnet'; $est = $p['carne_estado']; $yo = admin_actual(); ?>
<h1 class="h4">Mi carnet</h1>
<div class="row g-4">
 <div class="col-lg-5"><div class="protegido"><?= carne_html($p, ['chica' => true]) ?></div></div>
 <div class="col-lg-7">
  <h2 class="h5 mb-1"><?= e(persona_nombre($p)) ?></h2>
  <p class="mb-2"><?= e((TIPOS_DOC[$p['tipo_documento']] ?? '') . ' ' . $p['numero_documento']) ?> · <?= e(TIPOS_PERSONA[$p['tipo_persona']]) ?><?= $p['dependencia'] !== '' ? ' · ' . e($p['dependencia']) : '' ?></p>
  <p>Estado del carnet: <?= badge_carne($est) ?></p>
  <?php if ($faltan): ?>
   <div class="alert alert-warning">Para que tu carnet quede completo falta: <strong><?= e(implode(', ', $faltan)) ?></strong>.</div>
  <?php endif; ?>

  <?php if ($est !== 'activo'): ?>
   <?php if (!$faltan && tiene('estado')): ?>
    <form method="post" action="<?= e(admin_url('/personas/' . $p['id'] . '/estado')) ?>" class="mb-3"><?= csrf_field() ?><input type="hidden" name="estado" value="activo"><input type="hidden" name="volver" value="mi-carne">
     <button class="btn btn-success"><?= $est === 'inactivo' ? 'Reactivar mi carnet' : 'Activar mi carnet' ?></button></form>
   <?php elseif (!$faltan): ?>
    <p class="small text-muted">Tu carnet está completo pero aún no está activo. Pide a un administrador con permiso de activación que lo active.</p>
   <?php endif; ?>
  <?php endif; ?>

  <form method="post" action="<?= e(admin_url('/mi-carne/foto')) ?>" enctype="multipart/form-data" class="card card-body mb-3" data-carga><?= csrf_field() ?>
   <label class="form-label">Subir o reemplazar mi fotografía (JPG o PNG)</label>
   <input data-quitar-fondo type="file" name="foto" accept="image/jpeg,image/png" class="form-control mb-2" required><button class="btn btn-success align-self-start" type="submit">Subir</button>
  </form>

  <div class="card card-body">
   <h2 class="h6">Cómo mostrar mi carnet en el celular</h2>
   <ol class="small mb-2">
    <li>Abre la página principal del carnet.</li>
    <li>En <strong>Primera vez</strong>, escribe tu cédula y tu fecha de expedición, marca los términos y crea tu PIN.</li>
    <li>Desde ahí entras solo con tu PIN.</li>
   </ol>
   <div class="d-flex flex-wrap gap-2"><a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/')) ?>">Ir a la página principal</a><a class="btn btn-sm btn-outline-secondary" href="<?= e(admin_url('/cuenta')) ?>">Editar mis datos</a></div>
   <?php if (empty($p['fecha_expedicion'])): ?><p class="small text-danger mb-0 mt-2">Falta tu fecha de expedición: agrégala en «Mi cuenta» para poder crear tu PIN.</p><?php endif; ?>
  </div>
 </div>
</div>
