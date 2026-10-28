<?php $titulo = 'Vincular persona'; ?>
<p><a class="btn-volver" href="<?= e(admin_url('/usuarios')) ?>">Volver</a></p>
<h1 class="h4">Vincular persona a «<?= e($u['usuario']) ?>»</h1>
<p class="text-muted small">Así el administrador tendrá su carnet y aparecerá en «Personas».</p>
<?php foreach ($err as $x): ?><div class="alert alert-danger"><?= e($x) ?></div><?php endforeach; ?>
<form method="get" class="d-flex gap-2 mb-3" style="max-width:520px"><input name="cedula" class="form-control" placeholder="Cédula de la persona" value="<?= e($cedula) ?>" inputmode="numeric"><button class="btn btn-outline-success">Buscar</button></form>
<?php if ($cedula !== ''): ?>
<form method="post" enctype="multipart/form-data" class="card card-body" style="max-width:720px"><?= csrf_field() ?><input type="hidden" name="cedula" value="<?= e($cedula) ?>">
 <?php if ($encontrada && $yaAdmin): ?><div class="alert alert-warning">Esa persona ya está vinculada a otro administrador.</div>
 <?php elseif ($encontrada): ?><div class="alert alert-success py-2 small">Persona encontrada: <strong><?= e(persona_nombre($encontrada)) ?></strong> (<?= e(TIPOS_PERSONA[$encontrada['tipo_persona']]) ?>).</div><button class="btn btn-success align-self-start">Vincular</button>
 <?php else: include __DIR__ . '/_persona_nueva.php'; ?><button class="btn btn-success align-self-start">Crear persona y vincular</button><?php endif; ?>
</form><?php endif; ?>
