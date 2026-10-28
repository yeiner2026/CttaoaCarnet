<?php $titulo = 'Fichas'; ?>
<h1 class="h4">Fichas de formación</h1>
<form method="get" class="d-flex gap-2 mb-3"><input name="ficha" value="<?= e($q) ?>" class="form-control" placeholder="Buscar por número de ficha o programa" autofocus>
 <button class="btn btn-success">Buscar</button><?php if ($q !== ''): ?><a class="btn btn-outline-secondary" href="<?= e(admin_url('/fichas')) ?>">Ver todas</a><?php endif; ?></form>
<p class="text-muted small"><?= $q !== '' ? (int)$pg['total'] . ' resultado(s) para «' . e($q) . '».' : (int)$total . ' ficha(s) registradas. Se agregan automáticamente al importar o registrar aprendices.' ?></p>
<div class="list-group lista-fichas mb-3">
<?php foreach ($pg['items'] as $f): ?>
 <?php $vg = ficha_vigencia($f); ?>
 <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-2<?= $vg['clase'] === 'vencida' ? ' list-group-item-danger' : '' ?>" href="<?= e(admin_url('/fichas/' . $f['id'])) ?>">
  <span><strong><?= e($f['numero']) ?></strong> · <?= e($f['programa']) ?><?php if ($vg['clase'] === 'ok'): ?><br><small class="text-muted"><?= e($vg['texto']) ?></small><?php endif; ?></span>
  <span class="d-flex align-items-center gap-2 flex-wrap justify-content-end"><?php if ($vg['clase'] === 'vencida'): ?><span class="badge bg-danger"><?= e($vg['texto']) ?></span><?php elseif ($vg['clase'] === 'pronto'): ?><span class="badge bg-warning text-dark"><?= e($vg['texto']) ?></span><?php elseif ($vg['clase'] === 'sin_fecha'): ?><span class="badge bg-secondary">Sin fecha de fin</span><?php endif; ?>
  <span class="badge bg-success rounded-pill"><?= (int)$f['n'] ?> integrantes</span></span></a>
<?php endforeach; if (!$pg['items']): ?><div class="list-group-item text-muted">No hay fichas <?= $q !== '' ? 'que coincidan.' : 'registradas todavía. Importa un Excel o crea un aprendiz.' ?></div><?php endif; ?>
</div>
<?= paginador($pg) ?>
