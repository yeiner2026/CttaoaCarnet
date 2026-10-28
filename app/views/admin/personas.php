<?php $titulo = 'Consultar personas'; $tipos = ['todos' => 'Todos', 'aprendiz' => 'Aprendices', 'instructor' => 'Instructores', 'funcionario' => 'Funcionarios', 'contratista' => 'Contratistas', 'no_aprendices' => 'Instructores, funcionarios y contratistas']; ?>
<h1 class="h4">Consultar personas</h1>
<form method="get" class="row g-2 mb-3"><div class="col-md-6"><input name="q" value="<?= e($q) ?>" class="form-control" placeholder="Número de cédula o nombre" inputmode="search" autofocus></div>
 <div class="col-md-4"><select name="tipo" class="form-select" onchange="this.form.submit()"><?php foreach ($tipos as $k => $t): ?><option value="<?= $k ?>"<?= sel($tipo, $k) ?>><?= e($t) ?></option><?php endforeach; ?></select></div>
 <div class="col-md-2 d-grid"><button class="btn btn-success">Buscar</button></div></form>
<p class="text-muted small"><?= (int)$pg['total'] ?> persona(s).</p>
<table class="table table-sm bg-white tabla-resp align-middle"><thead><tr><th>Foto</th><th>Nombre</th><th>Documento</th><th>Tipo</th><th>Área / ficha</th><th>Carnet</th><th>Acciones</th></tr></thead><tbody>
<?php foreach ($pg['items'] as $p): ?><tr>
 <td data-l="Foto"><?= foto_mini($p) ?></td><td data-l="Nombre"><?= e(persona_nombre($p)) ?><?php if (!empty($p['admin_rol'])): ?> <span class="badge bg-dark">Administrador</span><?php endif; ?></td><td data-l="Documento"><?= e((TIPOS_DOC[$p['tipo_documento']] ?? '') . ' ' . $p['numero_documento']) ?></td>
 <td data-l="Tipo"><?= e(TIPOS_PERSONA[$p['tipo_persona']]) ?><?php if (formacion_codigo($p) !== ''): ?><div class="small text-muted"><?= e(formacion_etiqueta(formacion_codigo($p))) ?></div><?php endif; ?></td><td data-l="Área / ficha"><?= $p['ficha_numero'] ? 'Ficha ' . e($p['ficha_numero']) : e($p['dependencia']) ?></td><td data-l="Carnet"><?= badge_carne($p['carne_estado']) ?></td>
 <td data-l="Acciones"><a class="btn btn-sm btn-outline-secondary" href="<?= e(admin_url('/personas/' . $p['id'])) ?>">Ver</a>
 <?php $mod = tiene('editar'); if ($mod): ?><a class="btn btn-sm btn-outline-secondary" href="<?= e(admin_url('/personas/' . $p['id'] . '/editar')) ?>">Editar</a>
  <?php if (empty($p['admin_rol'])): ?><form method="post" action="<?= e(admin_url('/personas/' . $p['id'] . '/eliminar')) ?>" class="d-inline" data-confirmar="¿Eliminar definitivamente este registro?"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Eliminar</button></form><?php endif; endif; ?></td></tr>
<?php endforeach; if (!$pg['items']): ?><tr><td colspan="7">No hay personas con ese criterio.</td></tr><?php endif; ?></tbody></table>
<?= paginador($pg) ?>
