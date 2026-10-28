<?php
$titulo = $inst ? 'Editar persona' : 'Nueva persona';
$val = function ($k) use ($v) { return isset($v[$k]) ? (string)$v[$k] : ''; };
$txt = function ($k, $label, $type = 'text', $extra = '') use ($val, $err) {
    return '<div class="col-md-6" data-campo="' . $k . '"><label class="form-label" for="id_' . $k . '">' . e($label) . '</label><input class="form-control" id="id_' . $k . '" name="' . $k . '" type="' . $type . '" value="' . e($val($k)) . '" ' . $extra . '>' . campo_error($err, $k) . '</div>';
};
?>
<p class="mb-2"><a class="btn-volver" href="<?= e($inst ? admin_url('/personas/' . $inst['id']) : admin_url('/personas')) ?>">Volver</a></p>
<h1 class="h4"><?= e($titulo) ?></h1>
<form method="post" enctype="multipart/form-data" class="card card-body" data-carga><?= csrf_field() ?>
<div class="row g-3">
 <div class="col-md-6" data-campo="tipo_persona"><label class="form-label" for="id_tipo_persona">Tipo de persona</label>
  <select class="form-select" id="id_tipo_persona" name="tipo_persona"><?php foreach (TIPOS_PERSONA as $k => $t): ?><option value="<?= $k ?>"<?= sel($val('tipo_persona'), $k) ?>><?= e($t) ?></option><?php endforeach; ?></select><?= campo_error($err, 'tipo_persona') ?></div>
 <div class="col-md-6" data-campo="tipo_documento"><label class="form-label" for="id_tipo_documento">Tipo de documento</label>
  <select class="form-select" id="id_tipo_documento" name="tipo_documento"><?php foreach (TIPOS_DOC as $k => $t): ?><option value="<?= $k ?>"<?= sel($val('tipo_documento'), $k) ?>><?= e($t) ?></option><?php endforeach; ?></select><?= campo_error($err, 'tipo_documento') ?></div>
 <?= $txt('numero_documento', 'Número de documento', 'text', 'inputmode="numeric" maxlength="20" required') ?>
 <?= $txt('nombres', 'Nombres', 'text', 'maxlength="100" required') ?>
 <?= $txt('apellidos', 'Apellidos', 'text', 'maxlength="100" required') ?>
 <?= $txt('fecha_expedicion', 'Fecha de expedición del documento', 'date', 'required') ?>
 <?= $txt('email', 'Correo electrónico', 'email', 'maxlength="190" required') ?>
 <div class="col-md-6" data-campo="rh"><label class="form-label" for="id_rh">Tipo de sangre (RH)</label>
  <select class="form-select" id="id_rh" name="rh" required><option value="">Selecciona…</option><?php foreach (GRUPOS_RH as $g): ?><option<?= sel($val('rh'), $g) ?>><?= e($g) ?></option><?php endforeach; ?></select><?= campo_error($err, 'rh') ?></div>
 <?= $txt('dependencia', 'Área, dependencia o cargo', 'text', 'maxlength="200"') ?>
 <?= $txt('ficha_numero', 'Número de ficha', 'text', 'maxlength="20"') ?>
 <div class="col-md-6" data-campo="programa_nombre"><label class="form-label" for="id_programa_nombre">Programa de formación</label>
  <input class="form-control" id="id_programa_nombre" name="programa_nombre" list="lista-programas" autocomplete="off" maxlength="200" value="<?= e($val('programa_nombre')) ?>"><?= campo_error($err, 'programa_nombre') ?></div>
 <div class="col-md-6 d-flex align-items-end" data-campo="actualizar_programa_ficha"><div class="form-check"><input class="form-check-input" type="checkbox" id="id_actualizar_programa_ficha" name="actualizar_programa_ficha" value="1"><label class="form-check-label" for="id_actualizar_programa_ficha">Corregir el programa de TODA la ficha (afecta a todos sus integrantes)</label></div></div>
 <div class="col-md-6" data-campo="estado_formacion"><label class="form-label" for="id_estado_formacion">Estado en la formación</label>
  <select class="form-select" id="id_estado_formacion" name="estado_formacion"><?php $efv = $val('estado_formacion') ?: 'en_formacion'; foreach (ESTADOS_FORMACION as $k => $t): ?><option value="<?= $k ?>"<?= sel($efv, $k) ?>><?= e($t[0]) ?></option><?php endforeach; ?></select>
  <div class="form-text">Con retiro voluntario, cancelado o certificado, el carnet se desactiva solo.</div><?= campo_error($err, 'estado_formacion') ?></div>
 <div class="col-md-12" data-campo="observaciones"><label class="form-label" for="id_observaciones">Observaciones</label><textarea class="form-control" id="id_observaciones" name="observaciones" rows="2"><?= e($val('observaciones')) ?></textarea></div>
 <div class="col-md-6" data-campo="foto_nueva"><label class="form-label" for="id_foto_nueva">Fotografía (opcional, JPG o PNG, máx. 3 MB)</label><input data-quitar-fondo class="form-control" id="id_foto_nueva" name="foto_nueva" type="file" accept="image/jpeg,image/png"></div>
</div>
<datalist id="lista-programas"><?php foreach ($programas as $pr): ?><option value="<?= e($pr['nombre']) ?>"><?php endforeach; ?></datalist>
<script>
(function(){
  var tipo = document.getElementById('id_tipo_persona');
  if (!tipo) return;
  var soloAprendiz = ['ficha_numero', 'programa_nombre', 'actualizar_programa_ficha', 'estado_formacion'];
  var soloOtros = ['dependencia'];
  function mostrar(nombres, ver){
    nombres.forEach(function(n){
      var c = document.querySelector('[data-campo="' + n + '"]');
      if (!c) return;
      c.style.display = ver ? '' : 'none';
      var i = c.querySelector('input,select,textarea');
      if (i) i.disabled = !ver;
    });
  }
  function aplicar(){
    var apr = tipo.value === 'aprendiz';
    var otro = (tipo.value === 'instructor' || tipo.value === 'funcionario' || tipo.value === 'contratista');
    mostrar(soloAprendiz, apr || !otro);
    mostrar(soloOtros, otro);
    ['ficha_numero', 'programa_nombre'].forEach(function(n){
      var i = document.getElementById('id_' + n);
      if (i) i.required = apr;
    });
    var d = document.getElementById('id_dependencia');
    if (d) d.required = otro;
  }
  tipo.addEventListener('change', aplicar);
  aplicar();
})();
</script>
<div class="mt-3 d-flex flex-wrap gap-2">
 <button class="btn btn-success" type="submit">Guardar</button>
 <?php if (!$inst): ?><button class="btn btn-outline-success" name="guardar_otro" value="1" type="submit">Guardar y crear otro</button><?php endif; ?>
 <a class="btn btn-outline-secondary" href="<?= $inst ? e(admin_url('/personas/' . $inst['id'])) : e(admin_url('/')) ?>">Cancelar</a>
</div></form>
