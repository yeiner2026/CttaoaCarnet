<?php $titulo = 'Fotografías masivas'; ?>
<?php if ($items !== null): ?><p class="mb-2"><a class="btn-volver" href="<?= e(admin_url('/fotos')) ?>">Volver</a></p><?php endif; ?>
<h1 class="h4">Carga masiva de fotografías</h1>
<p class="text-muted">Nombra cada archivo con el número de documento (ej. <code>1001234567.jpg</code>).</p>
<form method="post" enctype="multipart/form-data" class="card card-body mb-3" data-carga><?= csrf_field() ?>
 <input type="file" data-quitar-fondo="masivo" name="fotos[]" multiple accept="image/jpeg,image/png" class="form-control mb-2" required><button class="btn btn-success" type="submit">Revisar coincidencias</button></form>
<?php if ($items): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="accion" value="confirmar">
 <table class="table table-sm bg-white"><thead><tr><th></th><th>Archivo</th><th>Persona</th><th>Resultado</th></tr></thead><tbody>
 <?php foreach ($items as $i): ?><tr><td><?php if ($i['tmp']): ?><input type="checkbox" name="aplicar[]" value="<?= e($i['tmp']) ?>" checked><?php endif; ?></td><td><?= e($i['archivo']) ?></td><td><?= e($i['nombre']) ?></td><td><?= e($i['estado']) ?></td></tr><?php endforeach; ?></tbody></table>
 <button class="btn btn-primary" type="submit">Confirmar asociaciones</button></form><?php endif; ?>
