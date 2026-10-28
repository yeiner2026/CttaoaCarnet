<?php
$titulo = persona_nombre($p); $falt = persona_faltantes($p); $id = $p['id']; $est = $p['carne_estado'];
$puedeMod = true;      // el carnet de un administrador se puede ver y editar; solo se bloquea eliminarlo
?>
<p><a class="btn-volver" href="<?= $p['ficha_id'] ? e(admin_url('/fichas/' . $p['ficha_id'])) : e(admin_url('/personas')) ?>">Volver</a></p>
<div class="row g-4"><div class="col-lg-5"><div class="protegido"><?= carne_html($p, ['chica' => true]) ?></div></div>
<div class="col-lg-7"><h1 class="h4"><?= e(persona_nombre($p)) ?>
 <?php if ($adm): ?><span class="badge bg-dark fs-6"><?= $adm['rol'] === 'principal' ? 'Administrador principal' : 'Administrador' ?></span><?php endif; ?></h1>
<p><?= e((TIPOS_DOC[$p['tipo_documento']] ?? '') . ' ' . $p['numero_documento']) ?> · <?= e(TIPOS_PERSONA[$p['tipo_persona']]) ?> · Registro: <strong><?= e(ESTADOS_REGISTRO[$p['estado_registro']]) ?></strong> · Carnet: <strong><?= e(ESTADOS_CARNE[$est]) ?></strong><?php if (formacion_codigo($p) !== ''): ?> · Formación: <strong><?= e(formacion_etiqueta(formacion_codigo($p))) ?></strong><?= !empty($p['estado_formacion_fecha']) ? ' <span class="text-muted">(desde el ' . e(hora_legible($p['estado_formacion_fecha'])) . ')</span>' : '' ?><?php endif; ?></p>
<p class="small text-muted">Términos y privacidad: <?php if (!empty($p['terminos_version'])): ?><strong>aceptados</strong> el <?= e(hora_legible($p['terminos_fecha'])) ?> (versión <?= e($p['terminos_version']) ?>, IP <?= e($p['terminos_ip']) ?>)<?php else: ?><strong>aún no aceptados</strong><?php endif; ?>.</p>
<p class="small text-muted">Acceso del titular: <?php if ($p['pin_configurado']): ?><strong>PIN configurado</strong><?php elseif ($p['fecha_expedicion']): ?>pendiente de activación (el titular lo hace con documento y fecha de expedición)<?php else: ?>pendiente; <strong>sin fecha de expedición</strong>: edita el registro o genera un código de recuperación<?php endif; ?>.</p>
<?php if ($adm && !$puedeMod): ?><div class="alert alert-secondary py-2 small">Esta persona tiene acceso de administrador: solo un administrador principal puede modificar su registro.</div><?php endif; ?>
<?php if ($falt): ?><div class="alert alert-warning">Faltan: <?= e(implode(', ', $falt)) ?></div><?php endif; ?>
<div class="d-flex flex-wrap gap-2 mb-3">
<?php if ($puedeMod && tiene('editar')): ?><a class="btn btn-outline-secondary" href="<?= e(admin_url('/personas/' . $id . '/editar')) ?>">Editar</a><?php endif; ?>
<?php if ($puedeMod && tiene('estado')): if ($est !== 'activo'): ?><form method="post" action="<?= e(admin_url('/personas/' . $id . '/estado')) ?>"><?= csrf_field() ?><input type="hidden" name="estado" value="activo"><button class="btn btn-success"><?= $est === 'inactivo' ? 'Reactivar' : 'Activar' ?></button></form><?php endif;
 if ($est === 'activo'): ?><form method="post" action="<?= e(admin_url('/personas/' . $id . '/estado')) ?>" data-confirmar="¿Desactivar este carnet?"><?= csrf_field() ?><input type="hidden" name="estado" value="inactivo"><button class="btn btn-warning">Desactivar</button></form><?php endif; endif; ?>
<?php if (tiene('historial')): ?><a class="btn btn-outline-secondary" href="<?= e(admin_url('/historial')) ?>?objeto=Persona&id=<?= (int)$id ?>">Historial</a><?php endif; ?>
<a class="btn btn-outline-secondary" href="<?= e(admin_url('/personas/' . $id . '/pdf')) ?>" title="Se abre con el número de documento de la persona">Descargar PDF</a>
<?php if ($puedeMod && !$adm && tiene('editar')): ?><form method="post" action="<?= e(admin_url('/personas/' . $id . '/eliminar')) ?>" data-confirmar="¿Eliminar definitivamente este registro?"><?= csrf_field() ?><button class="btn btn-outline-danger">Eliminar</button></form><?php endif; ?></div>
<?php if ($puedeMod && tiene('fotos')): ?><form method="post" action="<?= e(admin_url('/personas/' . $id . '/foto')) ?>" enctype="multipart/form-data" class="card card-body" data-carga><?= csrf_field() ?>
 <label class="form-label">Subir o reemplazar fotografía (JPG o PNG, máx. 3 MB)</label><input data-quitar-fondo type="file" name="foto" accept="image/jpeg,image/png" class="form-control mb-2" required><button class="btn btn-success" type="submit">Subir</button></form><?php endif; ?>
</div></div>
