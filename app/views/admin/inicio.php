<?php
$titulo = 'Inicio';
$colEstados = [['Activos', $estados['activo'], 'var(--verde)'], ['Inactivos', $estados['inactivo'], '#d9534f'], ['Pendientes', $estados['pendiente'], '#f0ad4e']];
$pend = max(0, $total - $activaron);
$colAcceso = [['Ya activaron su PIN', $activaron, 'var(--verde)'], ['Aún no', $pend, '#9aa5a0']];
$colTipos = [['Aprendices', $tipos['aprendiz'], 'var(--verde)'], ['Instructores', $tipos['instructor'], '#2e86c1'], ['Funcionarios', $tipos['funcionario'], '#8e6bbf'], ['Contratistas', $tipos['contratista'], '#d68910']];
?>
<h1 class="h4 mb-3">Inicio</h1>
<?php if (!empty($sinCarne)): ?>
<div class="alert alert-info d-flex justify-content-between align-items-center gap-2 flex-wrap" role="alert">
  <span><strong>Tu cuenta aún no tiene carnet.</strong> Registra tu cédula, nombre y cargo para tener tu propio carnet.</span>
  <a class="btn btn-sm btn-outline-dark" href="<?= e(admin_url('/cuenta')) ?>#carne">Completar mis datos</a>
</div>
<?php endif; ?>
<?php if (!empty($alertas) && $puedeConfig): ?>
<div class="alert alert-warning d-flex justify-content-between align-items-center gap-2" role="alert">
  <span><strong><?= (int)$alertas ?></strong> alerta(s) de actividad sospechosa reciente.</span>
  <form method="post" action="<?= e(admin_url('/configuracion/alertas/revisar')) ?>" class="m-0"><?= csrf_field() ?><button class="btn btn-sm btn-outline-dark" type="submit">Revisar</button></form>
</div>
<?php endif; ?>
<?php foreach (array_slice($alertasFichas ?? [], 0, 5) as $af): ?>
<div class="alert alert-<?= $af['clase'] === 'vencida' ? 'danger' : 'warning' ?> d-flex justify-content-between align-items-center gap-2 flex-wrap py-2" role="alert">
  <span><strong>Ficha <?= e($af['numero']) ?></strong> · <?= e($af['programa']) ?>: <?= e($af['texto']) ?><?= $af['clase'] === 'pronto' ? ' (' . e(fecha_legible($af['fecha_fin'])) . '): al terminar, sus carnets pasarán a inactivos.' : '. Sus carnets activos pasaron a inactivos.' ?></span>
  <a class="btn btn-sm btn-outline-dark" href="<?= e(admin_url('/fichas/' . $af['id'])) ?>">Ver ficha</a></div>
<?php endforeach; if (count($alertasFichas ?? []) > 5): ?><p class="small text-muted">Y <?= count($alertasFichas) - 5 ?> ficha(s) más: revisa la lista de fichas.</p><?php endif; ?>
<div class="row g-3 mb-3">
<?php foreach ($indicadores as $k => $v): ?>
 <div class="col-6 col-lg-4 col-xl-2"><div class="card kpi h-100"><div class="card-body py-2"><div class="text-muted small"><?= e($k) ?></div><div class="n"><?= (int)$v ?></div></div></div></div>
<?php endforeach; ?></div>

<div class="row g-3">
 <div class="col-lg-7"><div class="card h-100"><div class="card-body">
  <div class="d-flex justify-content-between align-items-baseline"><h2 class="h6 mb-0">Ingresos de titulares · últimos 7 días</h2>
   <span class="small text-muted"><?= (int)$ingresosSemana ?> ingresos · <?= (int)$semana ?> personas</span></div>
  <?= svg_barras($ing['labels'], $ing['vals']) ?>
  <p class="small text-muted mb-0">Cuántas veces los aprendices, instructores, funcionarios y contratistas consultaron su carnet con su PIN.</p>
 </div></div></div>
 <div class="col-lg-5"><div class="card h-100"><div class="card-body">
  <h2 class="h6">Acceso de las personas</h2>
  <div class="d-flex align-items-center gap-3 flex-wrap"><?= svg_dona($colAcceso, $total ? round($activaron / $total * 100) . '%' : '0%', 'activaron') ?><?= leyenda($colAcceso) ?></div>
  <p class="small text-muted mb-0 mt-2">Personas que ya crearon su PIN y pueden consultar su carnet.</p>
 </div></div></div>
 <div class="col-lg-6"><div class="card h-100"><div class="card-body">
  <h2 class="h6">Carnets por estado</h2>
  <div class="d-flex align-items-center gap-3 flex-wrap"><?= svg_dona($colEstados, (string)$total, 'carnets') ?><?= leyenda($colEstados) ?></div>
 </div></div></div>
 <div class="col-lg-6"><div class="card h-100"><div class="card-body">
  <h2 class="h6">Personas por tipo</h2>
  <?= svg_hbarras($colTipos) ?>
 </div></div></div>
</div>
