<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($titulo ?? 'Mi carnet') ?> · CTTAOA</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= e(asset('app.css')) ?>" rel="stylesheet"></head>
<body class="t-body" data-guarda="1" data-idle="<?= (int)cfg('titular_sesion') ?>" data-ping="<?= e(url('/ping')) ?>?r=titular">
<header class="t-bar py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
 <strong class="text-success">CTTAOA · Carnet digital</strong>
 <nav class="d-flex gap-2 align-items-center flex-wrap" aria-label="Menú del titular">
  <a class="btn btn-sm btn-outline-verde" href="<?= e(url('/mi-carne')) ?>">Mi carnet</a>
  <?php if (acceso_admin_actual()): ?><a class="btn btn-sm btn-success" href="<?= e(admin_url('/')) ?>">Panel de administración</a><?php endif; ?>
  <a class="btn btn-sm btn-outline-verde" href="<?= e(url('/ayuda')) ?>">Ayuda</a>
  <form id="form-salir" method="post" action="<?= e(url('/salir')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary">Cerrar sesión</button></form>
  <form method="post" action="<?= e(url('/cambiar-documento')) ?>" data-confirmar="¿Salir y consultar otro documento? Se desvinculará este dispositivo."><?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary">Consultar otro documento</button></form>
 </nav></header>
<main class="container py-3" style="max-width:640px">
<?= flashes_html() ?>
<?= $contenido ?>
</main>
<?= pie_pagina() ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset('app.js')) ?>"></script></body></html>
