<?php $titulo = 'Importar Excel'; ?>
<h1 class="h4">Importar Excel</h1>
<div class="card card-body mb-3 border-success"><strong>1. Descarga la plantilla</strong>
 <span class="text-muted small mb-2">Archivo Excel con las columnas exactas que espera el sistema, listas desplegables y una fila de ejemplo.</span>
 <a class="btn btn-outline-success align-self-start" href="<?= e(admin_url('/importar/plantilla')) ?>">Descargar plantilla Excel (.xlsx)</a></div>
<form method="post" enctype="multipart/form-data" class="card card-body mb-3" data-carga><?= csrf_field() ?>
 <label class="form-label"><strong>2. Sube el archivo diligenciado</strong> · .xlsx (máx. 5 MB). Usa texto para documentos y fichas.</label>
 <input type="file" name="archivo" accept=".xlsx" class="form-control mb-2" required><button class="btn btn-success" type="submit">Subir y revisar</button></form>
<h2 class="h6">Últimas importaciones</h2>
<ul><?php foreach ($historial as $i): ?><li><a href="<?= e(admin_url('/importar/' . $i['id'])) ?>"><?= e($i['nombre_original']) ?></a> · <?= e($i['estado']) ?> · <?= e(hora_legible($i['creada'])) ?></li><?php endforeach; ?></ul>
