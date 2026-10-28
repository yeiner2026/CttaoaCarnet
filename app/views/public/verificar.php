<?php $titulo = 'Verificar carnet'; ?>
<h1 class="h3 mb-1">Verificar un carnet</h1>
<p class="text-muted">Consulta el estado del carnet. Esta verificación es de apoyo y cada consulta queda registrada; no reemplaza los registros oficiales del SENA.</p>

<form method="post" id="form-verificar" class="card card-body shadow-sm mb-3" autocomplete="off"><?= csrf_field() ?>
 <label class="form-label fw-semibold" for="v-doc">Número de documento</label>
 <input id="v-doc" name="documento" class="form-control form-control-lg mb-3" inputmode="numeric" pattern="[0-9 .]*" maxlength="18" placeholder="Solo números" value="<?= e($doc) ?>" required>
 <div class="d-grid gap-2">
  <button class="btn btn-verde btn-lg" type="submit">Consultar</button>
  <button class="btn btn-outline-verde btn-lg" type="button" id="btn-escanear">Escanear código de barras con la cámara</button>
 </div>
 <div id="caja-escaner" class="d-none mt-3">
  <div class="escaner-marco"><video id="video-escaner" playsinline muted></video><div class="escaner-guia"></div></div>
  <div id="aviso-escaner" class="small text-muted mt-2" aria-live="polite"></div>
  <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="btn-cerrar-escaner">Cerrar cámara</button>
 </div>
</form>

<?php if ($res): ?>
<section id="resultado" class="card shadow-sm mb-3 verif-<?= !$res['encontrado'] ? 'no' : ($res['vigente'] ? 'ok' : 'mal') ?>" aria-live="polite">
 <div class="card-body text-center">
  <?php if (!$res['encontrado']): ?>
   <div class="verif-estado">NO REGISTRADO</div>
   <p class="mb-1">No encontramos un carnet registrado con el documento <strong><?= e($res['documento']) ?></strong>.</p>
   <p class="small text-muted mb-0">Revisa que el número esté bien escrito.</p>
  <?php else: ?>
   <div class="verif-estado"><?= e($res['titulo']) ?></div>
   <div class="verif-detalle"><?= e($res['detalle']) ?></div>
   <hr>
   <div class="text-start mx-auto" style="max-width:380px">
    <div class="small text-muted">Nombre completo</div><div class="fs-5 fw-semibold mb-2"><?= e($res['nombre']) ?></div>
    <div class="small text-muted">Documento</div><div class="fs-5 fw-semibold"><?= e($res['documento']) ?></div>
   </div>
  <?php endif; ?>
  <div class="small text-muted mt-3">Consulta realizada el <?= e(hora_legible(now())) ?></div>
 </div>
</section>
<script>document.getElementById('resultado').scrollIntoView({block:'center'});</script>
<?php endif; ?>
