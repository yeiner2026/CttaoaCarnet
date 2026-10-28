<?php $titulo = 'Mi carnet'; $estado = $p['carne_estado']; ?>
<div id="estado-sync" class="alert alert-secondary py-2 small" role="status">Estado cargado desde el servidor.</div>
<?php if ($estado === 'inactivo'): ?><div class="alert alert-warning" id="aviso-inactivo"><?= e(MSG_INACTIVO) ?></div>
<?php elseif ($estado === 'pendiente'): ?><div class="alert alert-info">Tu carnet aún no ha sido activado. Se muestra como borrador.</div><?php endif; ?>
<div id="zona-carne" class="protegido">
 <div class="velo"><div><p>Por seguridad, el carnet se oculta cuando sales de esta pantalla. No se permiten capturas de pantalla.</p><button type="button" class="btn btn-verde btn-lg" id="btn-mostrar">Toca para mostrar el carnet</button></div></div>
 <?= carne_html($p) ?>
 <div class="sello-vivo" aria-hidden="true"><span class="punto"></span> Verificado en vivo · <span id="reloj"></span></div>
</div>
<div class="d-grid gap-2 my-3 mx-auto" style="max-width:460px"><button id="btn-actualizar" class="btn btn-verde">Actualizar carnet</button>
 <a class="btn btn-outline-verde" href="<?= e(url('/mi-carne/pdf')) ?>">Descargar carnet en PDF</a>
 <div class="small text-muted text-center">El PDF se abre con tu número de documento.</div></div>
<?php if (!empty($adm)): ?>
<div class="card card-body shadow-sm mx-auto mb-3 border-success" style="max-width:460px" id="acceso-panel">
 <div class="fw-semibold">Acceso de administración</div>
 <p class="small text-muted mb-2"><?= e(acceso_admin_texto($adm)) ?></p>
 <a class="btn btn-outline-success" href="<?= e(admin_url('/')) ?>">Ir al panel de administración</a>
</div>
<?php endif; ?>
<details class="card card-body shadow-sm mx-auto" style="max-width:460px"><summary class="fw-semibold">Mis datos</summary>
<dl class="row mt-2 mb-0 small">
<dt class="col-5">Tipo</dt><dd class="col-7"><?= e(TIPOS_PERSONA[$p['tipo_persona']]) ?></dd>
<dt class="col-5">Documento</dt><dd class="col-7"><?= e(TIPOS_DOC[$p['tipo_documento']]) ?> <?= e($p['numero_documento']) ?></dd>
<dt class="col-5">Nombre</dt><dd class="col-7"><?= e(persona_nombre($p)) ?></dd>
<?php if (persona_rh_visible($p)): ?><dt class="col-5">RH</dt><dd class="col-7"><?= e(persona_rh_visible($p)) ?></dd><?php endif; ?>
<?php if ($p['ficha_numero']): ?><dt class="col-5">Ficha</dt><dd class="col-7"><?= e($p['ficha_numero']) ?></dd><?php endif; ?>
<?php if (formacion_codigo($p) !== ''): ?><dt class="col-5">Estado en la formación</dt><dd class="col-7"><?= e(formacion_etiqueta(formacion_codigo($p))) ?></dd><?php endif; ?>
<dt class="col-5">Programa / área</dt><dd class="col-7"><?= e(persona_programa_o_area($p)) ?></dd></dl></details>
<script>
(function(){
  var r=document.getElementById('reloj');
  function t(){r.textContent=new Date().toLocaleString('es-CO');}
  t(); setInterval(t,1000);
})();
document.getElementById('btn-actualizar').addEventListener('click',function(){
 var s=document.getElementById('estado-sync'); s.textContent='Consultando al servidor…';
 fetch('<?= e(url('/api/mi-carne')) ?>',{credentials:'same-origin',cache:'no-store'}).then(function(r){if(!r.ok)throw 0;return r.json();}).then(function(d){
   s.className='alert alert-success py-2 small'; s.textContent='Estado verificado ahora: '+d.estado+'. Recargando…'; setTimeout(function(){location.reload();},700);
 }).catch(function(){s.className='alert alert-danger py-2 small';s.textContent='No se pudo verificar el estado actual. Sin conexión: la información mostrada podría no estar vigente.';});
});
</script>
