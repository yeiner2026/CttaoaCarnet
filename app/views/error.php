<?php $titulo = 'Error ' . $codigo; ?>
<h1 class="h4 mt-4">Error <?= (int)$codigo ?></h1>
<p><?= e($mensaje) ?></p>
<p><a class="btn-volver" href="<?= e(url('/')) ?>">Ir al inicio</a></p>
