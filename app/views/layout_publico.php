<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($titulo ?? 'Carnet digital') ?> · CTTAOA</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= e(asset('app.css')) ?>" rel="stylesheet"></head>
<body class="t-body"><main class="container py-3" style="max-width:520px">
<?= flashes_html() ?>
<?= $contenido ?>
</main>
<?= pie_pagina() ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset('app.js')) ?>"></script></body></html>
