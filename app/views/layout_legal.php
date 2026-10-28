<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($titulo ?? 'Política de privacidad') ?> · CTTAOA</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= e(asset('app.css')) ?>" rel="stylesheet"></head>
<body class="t-body">
<header class="t-bar py-2 px-3 d-flex justify-content-between align-items-center">
 <strong class="text-success">CTTAOA · Carnet digital</strong>
 <a class="btn-volver" href="<?= e(url('/')) ?>">Volver al inicio</a>
</header>
<main class="container-xl py-4">
<?= $contenido ?>
</main>
<?= pie_pagina() ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset('app.js')) ?>"></script></body></html>
