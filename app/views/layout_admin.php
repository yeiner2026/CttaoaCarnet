<?php $u = admin_actual(); ?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($titulo ?? 'Panel') ?> · CTTAOA</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= e(asset('app.css')) ?>" rel="stylesheet">
<script>(function(){try{if(localStorage.getItem('cttaoa_tema')==='dark')document.documentElement.setAttribute('data-bs-theme','dark');}catch(e){}})();</script></head>
<body data-guarda="1" data-idle="<?= (int)cfg('admin_sesion') ?>" data-ping="<?= e(url('/ping')) ?>?r=admin"><div class="p-wrap">
<aside class="p-side">
 <div class="offcanvas-lg offcanvas-start" tabindex="-1" id="menuPanel" aria-labelledby="menuTitulo">
  <div class="offcanvas-header"><span id="menuTitulo" class="fw-bold">CTTAOA · Administración</span>
   <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#menuPanel" aria-label="Cerrar menú"></button></div>
  <div class="offcanvas-body d-block p-0"><div class="p-3 fw-bold d-none d-lg-block">CTTAOA · Administración</div>
   <nav aria-label="Navegación del panel">
    <a href="<?= e(admin_url('/')) ?>">Inicio</a>
    <?php if (tiene('consultar')): ?><a href="<?= e(admin_url('/fichas')) ?>">Fichas y carnets</a><a href="<?= e(admin_url('/personas')) ?>">Personas</a><?php endif; ?>
    <?php if (tiene('importar')): ?><a href="<?= e(admin_url('/importar')) ?>">Importar Excel</a><?php endif; ?>
    <?php if (tiene('editar')): ?><a href="<?= e(admin_url('/personas/nueva')) ?>">Crear carnet individual</a><?php endif; ?>
    <?php if (tiene('fotos')): ?><a href="<?= e(admin_url('/fotos')) ?>">Fotografías masivas</a><?php endif; ?>
    <?php if (tiene('plantilla')): ?><a href="<?= e(admin_url('/plantilla')) ?>">Diseño de plantilla</a><?php endif; ?>
    <?php if (es_principal()): ?><a href="<?= e(admin_url('/usuarios')) ?>">Usuarios y permisos</a><?php endif; ?>
    <?php if (tiene('historial')): ?><a href="<?= e(admin_url('/historial')) ?>">Historial</a><a href="<?= e(admin_url('/consultas')) ?>">Consultas públicas</a><?php endif; ?>
    <?php if (tiene('configuracion')): ?><a href="<?= e(admin_url('/configuracion')) ?>">Configuración</a><?php endif; ?>
    <a href="<?= e(admin_url('/cuenta')) ?>">Mi cuenta</a>
    <hr class="border-secondary my-2"><a href="<?= e(url('/')) ?>">Consulta de carnet (página principal)</a>
   </nav></div>
 </div>
</aside>
<div class="p-main"><div class="p-top px-3 py-2 d-flex justify-content-between align-items-center gap-2">
 <div class="d-flex align-items-center gap-2">
  <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuPanel" aria-controls="menuPanel" aria-label="Abrir menú"><span aria-hidden="true" style="font-size:1.3rem;line-height:1">☰</span></button>
  <span class="small">Conectado: <strong><?= e($u['usuario']) ?></strong></span></div>
 <div class="d-flex gap-2 align-items-center"><button type="button" id="btn-tema" class="btn btn-sm btn-outline-secondary" aria-label="Cambiar entre modo claro y modo oscuro">Modo oscuro</button>
 <form id="form-salir" method="post" action="<?= e(admin_url('/logout')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary">Cerrar sesión</button></form></div></div>
<main class="p-3"><?= flashes_html() ?><?= $contenido ?></main>
<?= pie_pagina() ?></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset('app.js')) ?>"></script>
<script src="<?= e(asset('fondo.js')) ?>"></script></body></html>
