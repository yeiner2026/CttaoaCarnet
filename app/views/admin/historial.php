<?php $titulo = 'Historial'; ?>
<?php if (get('objeto') === 'Persona' && (int)get('id')): ?><p class="mb-2"><a class="btn-volver" href="<?= e(admin_url('/personas/' . (int)get('id'))) ?>">Volver a la persona</a></p><?php endif; ?>
<h1 class="h4">Historial de cambios</h1>
<p class="small text-muted">Se conservan los últimos <?= (int)$dias ?> día(s); lo más antiguo se borra automáticamente (se cambia en Configuración).</p>
<div class="table-responsive"><table class="table table-sm bg-white tabla-resp"><thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Objeto</th><th>Detalle</th></tr></thead><tbody>
<?php foreach ($pg['items'] as $h): ?><tr><td data-l="Fecha"><?= e(hora_legible($h['fecha'])) ?></td><td data-l="Usuario"><?= e($h['usuario_nombre'] ?: '—') ?></td><td data-l="Acción"><?= e($h['accion']) ?></td><td data-l="Objeto"><?= e($h['objeto'] . ' ' . $h['objeto_id']) ?></td><td data-l="Detalle" class="text-break"><?= e($h['detalle']) ?></td></tr>
<?php endforeach; if (!$pg['items']): ?><tr><td colspan="5">Sin registros.</td></tr><?php endif; ?></tbody></table></div>
<?= paginador($pg) ?>
