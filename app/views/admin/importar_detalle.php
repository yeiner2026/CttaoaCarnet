<?php $titulo = 'Importación'; $res = $res ?? null; $filas = $filas ?? null; ?>
<p class="mb-2"><a class="btn-volver" href="<?= e(admin_url('/importar')) ?>">Volver a importar</a></p>
<h1 class="h4">Importación: <?= e($imp['nombre_original']) ?></h1>
<?php if ($resultado !== null): ?><div class="card card-body"><h2 class="h6">Informe final (<?= e($imp['estado']) ?>)</h2><pre class="mb-0"><?= e($resultado) ?></pre></div>
<?php else: ?>
<form method="post" class="card card-body mb-3"><?= csrf_field() ?>
 <div class="row g-2"><div class="col-md-4"><label class="form-label">Hoja</label>
  <select name="hoja" class="form-select" onchange="this.form.accion.value='recargar';this.form.submit()"><?php foreach ($hojas as $h): ?><option<?= sel($hoja, $h) ?>><?= e($h) ?></option><?php endforeach; ?></select></div></div>
 <p class="small text-muted mt-3">Si usaste la plantilla del sistema, las columnas ya quedan relacionadas automáticamente.</p>
 <h2 class="h6">Relacionar columnas (<?= count($cols) ?> detectadas)</h2>
 <div class="row g-2"><?php foreach ($campos as $c): ?><div class="col-md-4"><label class="form-label small"><?= e($c[0]) ?><?= in_array($c[0], OBLIGATORIOS_IMPORT, true) ? ' *' : '' ?></label>
  <select name="map_<?= e($c[0]) ?>" class="form-select form-select-sm"><option value="">— no usar —</option><?php foreach ($cols as $i => $n): ?><option value="<?= $i ?>"<?= sel($c[1], (string)$i) ?>><?= e($n) ?></option><?php endforeach; ?></select></div><?php endforeach; ?></div>
 <input type="hidden" name="accion" value="previsualizar">
 <div class="mt-3 d-flex gap-2"><button class="btn btn-success" type="submit" onclick="this.form.accion.value='previsualizar'">Vista previa</button>
 <?php if ($res && !$res['error']): ?><button class="btn btn-primary" type="submit" onclick="this.form.accion.value='confirmar'">Confirmar importación</button>
  <label class="align-self-center small"><input type="checkbox" name="actualizar" value="1"> Actualizar TODOS los existentes con cambios</label><?php endif; ?>
 <button class="btn btn-outline-danger ms-auto" type="submit" formnovalidate onclick="this.form.accion.value='cancelar'">Cancelar</button></div>
<?php if ($res): ?>
 <div class="alert alert-info mt-3">Nuevos: <?= $res['nuevo'] ?> · Con cambios: <?= $res['con_cambios'] ?> · Sin cambios: <?= $res['sin_cambios'] ?> · Errores: <?= $res['error'] ?> · Con avisos: <?= $res['avisos'] ?> · Fichas: <?= e(implode(', ', $res['fichas'])) ?></div>
 <div class="table-responsive"><table class="table table-sm bg-white"><thead><tr><th>Fila</th><th>Documento</th><th>Nombre</th><th>Ficha</th><th>Estado</th><th>Detalle</th></tr></thead><tbody>
 <?php foreach ($filas as $f): $d = $f['datos']; ?><tr class="<?= $f['estado'] === 'error' ? 'table-danger' : ($f['estado'] === 'con_cambios' ? 'table-warning' : '') ?>">
  <td><?= (int)$f['fila'] ?></td><td><?= e($d['numero_documento']) ?></td><td><?= e($d['nombres'] . ' ' . $d['apellidos']) ?></td><td><?= e($d['ficha']) ?></td><td><?= e($f['estado']) ?></td>
  <td><?php if ($f['errores']): ?><span class="text-danger"><?= e(implode('; ', $f['errores'])) ?></span><?php endif; ?>
  <?php if ($f['existente']): $x = $f['existente']; ?><div class="border rounded p-2 bg-light small"><strong>Este documento ya está registrado:</strong> <?= e($x['nombre']) ?> (<?= e($x['tipo']) ?>)<?php if ($x['ficha'] !== ''): ?>, ficha <strong><?= e($x['ficha']) ?></strong> · <?= e($x['programa']) ?><?php elseif ($x['programa']): ?>, <?= e($x['programa']) ?><?php endif; ?>.
   <?php if ($f['estado'] === 'sin_cambios'): ?><div class="text-success">Los datos del Excel son iguales: se omite.</div>
   <?php else: ?><div class="text-muted">Diferencias: <?= e(implode('; ', $f['cambios'])) ?></div>
   <label class="d-block mt-1 mb-0">¿Qué hacer con esta fila?</label>
   <select name="decision_<?= (int)$f['fila'] ?>" class="form-select form-select-sm"><option value="omitir"<?= sel($f['decision'], 'omitir') ?>>Dejar como está (no importar esta fila)</option>
    <option value="actualizar"<?= sel($f['decision'], 'actualizar') ?>><?= $x['mueve_ficha'] ? 'Mover a la ficha ' . e($d['ficha']) . ' y actualizar datos' : 'Actualizar con los datos del Excel' ?></option></select>
   <div class="text-muted mt-1">Para corregirla en el Excel: pulsa Cancelar, edita el archivo y vuelve a subirlo.</div><?php endif; ?></div><?php endif; ?>
  <?php if ($f['avisos']): ?><div class="small text-muted">⚠ <?= e(implode('; ', $f['avisos'])) ?></div><?php endif; ?></td></tr>
 <?php endforeach; ?></tbody></table></div>
<?php endif; ?></form><?php endif; ?>
