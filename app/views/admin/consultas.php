<?php $titulo = 'Consultas públicas'; $rotulo = ['activo' => 'Activo', 'no_vigente' => 'No vigente', 'no_existe' => 'No registrado']; ?>
<h1 class="h4">Consultas públicas</h1>
<p class="small text-muted">Consultas hechas en la página pública de verificación (<code>/verificar</code>) por vigilantes o empresas. Se conservan <?= max(1, (int)cfg('consultas_dias', 90)) ?> días y luego se borran solas.</p>
<div class="row g-3 mb-3">
 <div class="col-6 col-md-3"><div class="card kpi h-100"><div class="card-body py-2"><div class="text-muted small">Hoy</div><div class="n"><?= (int)$res['hoy'] ?></div></div></div></div>
 <div class="col-6 col-md-3"><div class="card kpi h-100"><div class="card-body py-2"><div class="text-muted small">Últimos 7 días</div><div class="n"><?= (int)$res['semana'] ?></div></div></div></div>
 <div class="col-6 col-md-3"><div class="card kpi h-100"><div class="card-body py-2"><div class="text-muted small">No registrados (7 días)</div><div class="n"><?= (int)$res['no_existe'] ?></div></div></div></div>
</div>
<table class="table table-sm bg-white tabla-resp align-middle"><thead><tr><th>Fecha</th><th>Documento consultado</th><th>Persona</th><th>Resultado</th><th>IP</th></tr></thead><tbody>
<?php foreach ($pg['items'] as $c): ?><tr>
 <td data-l="Fecha" class="text-nowrap"><?= e(hora_legible($c['fecha'])) ?></td><td data-l="Documento"><?= e($c['documento']) ?></td>
 <td data-l="Persona"><?= $c['nombres'] !== null ? e($c['nombres'] . ' ' . $c['apellidos']) : '<span class="text-muted">—</span>' ?></td>
 <td data-l="Resultado"><?= e($rotulo[$c['resultado']] ?? $c['resultado']) ?></td><td data-l="IP"><?= e($c['ip']) ?></td></tr>
<?php endforeach; if (!$pg['items']): ?><tr><td colspan="5">Aún no hay consultas.</td></tr><?php endif; ?></tbody></table>
<?= paginador($pg) ?>
