<?php $titulo = 'Eliminar ficha'; ?>
<p><a class="btn-volver" href="<?= e(admin_url('/fichas/' . $f['id'])) ?>">Volver a la ficha</a></p>
<h1 class="h4 text-danger">Eliminar ficha completa</h1>
<div class="card border-danger" style="max-width:640px"><div class="card-body">
 <p class="mb-2">Vas a eliminar la ficha <strong><?= e($f['numero']) ?></strong> (<?= e($f['programa']) ?>) <strong>y a todas las personas que pertenecen a ella</strong>.</p>
 <?php if (!empty($admins)): ?><div class="alert alert-danger">Hay <?= (int)$admins ?> integrante(s) con acceso de administrador: no se puede eliminar la ficha hasta retirarles ese acceso (Usuarios y permisos).</div><?php endif; ?>
 <ul><li>Personas que se eliminarán: <strong><?= (int)$total ?></strong></li><li>Con carnet activo: <strong><?= (int)$activos ?></strong></li>
 <li>Se borran también sus carnets, fotografías y accesos (PIN).</li><li>Esta acción <strong>no se puede deshacer</strong>. Queda registrada en el historial.</li></ul>
 <form method="post"><?= csrf_field() ?>
  <label class="form-label" for="confirmar">Para confirmar, escribe el número de la ficha: <strong><?= e($f['numero']) ?></strong></label>
  <input id="confirmar" name="confirmar" class="form-control mb-3" autocomplete="off" inputmode="numeric" required>
  <button class="btn btn-danger" type="submit">Eliminar definitivamente</button>
  <a class="btn btn-outline-secondary" href="<?= e(admin_url('/fichas/' . $f['id'])) ?>">Cancelar</a></form></div></div>
