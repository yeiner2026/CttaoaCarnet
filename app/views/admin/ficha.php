<?php $titulo = 'Ficha ' . $f['numero']; $fil = ['' => 'Todos', 'activos' => 'Activos', 'inactivos' => 'Inactivos', 'sin_foto' => 'Sin fotografía', 'incompletos' => 'Datos incompletos', 'pendientes' => 'Pendientes']; ?>
<a class="btn-volver" href="<?= e(admin_url('/fichas')) ?>">Volver a las fichas</a>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2">
 <h1 class="h4 mb-0">Ficha <?= e($f['numero']) ?> · <?= e($f['programa']) ?></h1>
 <?php if (tiene('editar')): ?><a class="btn btn-sm btn-outline-danger" href="<?= e(admin_url('/fichas/' . $f['id'] . '/eliminar')) ?>">Eliminar ficha completa</a><?php endif; ?>
</div>
<?php $vg = ficha_vigencia($f); ?>
<?php if ($vg['clase'] === 'vencida'): ?><div class="alert alert-danger py-2 small mt-2 mb-2" role="alert"><strong>Esta ficha terminó el <?= e(fecha_legible($f['fecha_fin'])) ?>.</strong> Sus carnets activos pasaron a inactivos. Puedes reactivarlos con «Activar carnets completos» (marcando «incluir los desactivados») o eliminar la ficha completa.</div>
<?php elseif ($vg['clase'] === 'pronto'): ?><div class="alert alert-warning py-2 small mt-2 mb-2" role="alert"><strong><?= e($vg['texto']) ?></strong> (<?= e(fecha_legible($f['fecha_fin'])) ?>). Cuando termine, todos sus carnets pasarán a inactivos.</div><?php endif; ?>
<form method="post" action="<?= e(admin_url('/fichas/' . $f['id'] . '/fechas')) ?>" class="d-flex flex-wrap align-items-end gap-2 mt-2 mb-2"><?= csrf_field() ?>
 <div><label class="form-label small mb-0" for="f-ini">Inicio de la ficha</label><input class="form-control form-control-sm" id="f-ini" type="date" name="fecha_inicio" value="<?= e((string)$f['fecha_inicio']) ?>"<?= tiene('editar') ? '' : ' disabled' ?>></div>
 <div><label class="form-label small mb-0" for="f-fin">Fin de la ficha</label><input class="form-control form-control-sm" id="f-fin" type="date" name="fecha_fin" value="<?= e((string)$f['fecha_fin']) ?>"<?= tiene('editar') ? '' : ' disabled' ?>></div>
 <?php if (tiene('editar')): ?><button class="btn btn-sm btn-outline-success" type="submit">Guardar fechas</button><?php endif; ?>
 <span class="small text-muted align-self-center"><?= e($vg['texto']) ?>. <?= (int)ficha_aviso_dias() ?> días antes se avisa; al terminar, los carnets pasan a inactivos.</span>
</form>
<p class="small text-muted mt-2 mb-1"><?= (int)$st['total'] ?> integrantes · <strong><?= (int)$st['activos'] ?></strong> activos · <strong><?= (int)$st['listos'] ?></strong> completos listos para activar · <strong><?= (int)$st['incompletos'] ?></strong> con datos o foto incompletos<?= $st['inactivos'] ? ' · ' . (int)$st['inactivos'] . ' desactivados' : '' ?></p>
<?php if (tiene('estado') && $st['total']): ?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-2">
 <form method="post" action="<?= e(admin_url('/fichas/' . $f['id'] . '/activar-todos')) ?>" class="d-flex flex-wrap align-items-center gap-2" data-confirmar="¿Activar los carnets completos de esta ficha? Cantidad a activar: <?= (int)$st['listos'] ?>. Los que tengan datos o foto incompletos no se tocan.">
  <?= csrf_field() ?><button class="btn btn-sm btn-success" type="submit"<?= ($st['listos'] + $st['listos_inactivos']) ? '' : ' disabled' ?>>Activar carnets completos (<?= (int)$st['listos'] ?>)</button>
  <?php if ($st['listos_inactivos']): ?><label class="small"><input type="checkbox" name="incluir_inactivos" value="1"> incluir los <?= (int)$st['listos_inactivos'] ?> desactivados</label><?php endif; ?>
 </form>
 <form method="post" action="<?= e(admin_url('/fichas/' . $f['id'] . '/desactivar-todos')) ?>" data-confirmar="¿Desactivar los carnets activos de esta ficha (<?= (int)$st['activos'] ?>)? Dejarán de ser válidos.">
  <?= csrf_field() ?><button class="btn btn-sm btn-outline-warning" type="submit"<?= $st['activos'] ? '' : ' disabled' ?>>Desactivar todos los activos (<?= (int)$st['activos'] ?>)</button></form>
</div>
<?php endif; ?>
<?php if ($st['no_vigentes']): ?><p class="small text-danger mb-1"><?= (int)$st['no_vigentes'] ?> con estado de formación no vigente (retiro voluntario, cancelado o certificado): no se pueden activar.</p><?php endif; ?>
<?php if (tiene('editar') && $st['total']): ?>
<form method="post" action="<?= e(admin_url('/fichas/' . $f['id'] . '/estado-formacion')) ?>" class="d-flex flex-wrap align-items-center gap-2 mb-2" data-confirmar="¿Cambiar el estado en la formación de TODOS los aprendices de esta ficha? Si eliges retiro voluntario, cancelado o certificado, sus carnets activos se desactivarán.">
 <?= csrf_field() ?><label class="small mb-0" for="ef-ficha">Estado en la formación de toda la ficha:</label>
 <select class="form-select form-select-sm w-auto" id="ef-ficha" name="estado_formacion"><?php foreach (ESTADOS_FORMACION as $k => $t): ?><option value="<?= $k ?>"><?= e($t[0]) ?></option><?php endforeach; ?></select>
 <button class="btn btn-sm btn-outline-secondary" type="submit">Aplicar a toda la ficha</button>
</form>
<?php endif; ?>
<div class="my-3 d-flex flex-wrap gap-1"><?php foreach ($fil as $k => $t): ?><a class="btn btn-sm btn-outline-success<?= $filtro === $k ? ' active' : '' ?>" href="?<?= $k !== '' ? 'filtro=' . $k : '' ?>"><?= e($t) ?></a><?php endforeach; ?></div>
<table class="table table-sm bg-white tabla-resp align-middle"><thead><tr><th>Foto</th><th>Nombre</th><th>Documento</th><th>Tipo</th><th>Formación</th><th>Registro</th><th>Carnet</th><th>Acciones</th></tr></thead><tbody>
<?php foreach ($pg['items'] as $p): ?><tr>
 <td data-l="Foto"><?= foto_mini($p) ?></td><td data-l="Nombre"><?= e(persona_nombre($p)) ?></td><td data-l="Documento"><?= e($p['tipo_documento'] . ' ' . $p['numero_documento']) ?></td>
 <td data-l="Tipo"><?= e(TIPOS_PERSONA[$p['tipo_persona']]) ?></td><td data-l="Formación"><?= e(formacion_etiqueta(formacion_codigo($p))) ?></td><td data-l="Registro"><?= e(ESTADOS_REGISTRO[$p['estado_registro']]) ?></td><td data-l="Carnet"><?= badge_carne($p['carne_estado']) ?></td>
 <td data-l="Acciones"><a class="btn btn-sm btn-outline-secondary" href="<?= e(admin_url('/personas/' . $p['id'])) ?>">Ver</a>
  <?php if (tiene('editar')): ?><a class="btn btn-sm btn-outline-secondary" href="<?= e(admin_url('/personas/' . $p['id'] . '/editar')) ?>">Editar</a><?php endif; ?></td></tr>
<?php endforeach; if (!$pg['items']): ?><tr><td colspan="8">Sin integrantes.</td></tr><?php endif; ?></tbody></table>
<?= paginador($pg) ?>
