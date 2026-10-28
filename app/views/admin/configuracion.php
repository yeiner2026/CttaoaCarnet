<?php $titulo = 'Configuración'; $num = function ($k, $label, $help, $type = 'text') use ($cfg, $err) {
    return '<div class="col-md-6"><label class="form-label small" for="c_' . $k . '">' . e($label) . '</label><input id="c_' . $k . '" name="' . $k . '" class="form-control" value="' . e($cfg[$k]) . '" inputmode="numeric"><div class="form-text">' . e($help) . '</div>' . campo_error($err, $k) . '</div>'; }; ?>
<h1 class="h4">Configuración</h1>
<form method="post" class="card card-body mb-3"><?= csrf_field() ?><input type="hidden" name="accion" value="guardar">
 <h2 class="h6">Seguridad de acceso</h2>
 <div class="row g-3">
  <?= $num('max_intentos_pin', 'Intentos permitidos por documento', 'Entre 1 y 10. Predeterminado: 3. Se cuenta por número de documento, no por IP: un WiFi compartido no afecta a nadie.') ?>
  <?= $num('bloqueo_minutos', 'Duración del primer bloqueo (minutos)', 'Entre 1 y 1440. Predeterminado: 15. Si vuelve a fallar, el segundo bloqueo seguido dura 1 hora y el tercero 24 horas.') ?>
  <?= $num('recuperacion_minutos', 'Vigencia del enlace para crear un PIN nuevo (minutos)', 'Entre 5 y 1440. Predeterminado: 30.') ?>
  <div class="col-md-6 d-flex align-items-center"><div class="form-check"><input class="form-check-input" type="checkbox" id="c_bm" name="bloqueo_manual" value="1"<?= chk($cfg['bloqueo_manual']) ?>><label class="form-check-label" for="c_bm"><strong>Desbloqueo solo por un administrador</strong><div class="form-text mt-0">Si está activo, quien se bloquee por fallar el PIN no podrá volver a intentarlo hasta que tú lo desbloquees aquí (no se desbloquea solo).</div></label></div></div>
 </div>
 <h2 class="h6 mt-4">Historial</h2>
 <div class="row g-3"><?= $num('historial_dias', 'Conservar el historial (días)', 'Entre 1 y 365. Lo más viejo se borra automáticamente. Ejemplo: 2 = 48 horas, 7 = una semana.') ?></div>
 <h2 class="h6 mt-4" id="fichas-alertas">Fichas por terminar o terminadas</h2>
 <?php if (empty($alertasFichas)): ?><p class="small text-muted">Ninguna ficha termina en los próximos <?= (int)ficha_aviso_dias() ?> días ni terminó en los últimos 60 días.</p>
 <?php else: ?><ul class="list-unstyled mb-0"><?php foreach ($alertasFichas as $af): ?><li class="alert alert-<?= $af['clase'] === 'vencida' ? 'danger' : 'warning' ?> py-2 small mb-2">Ficha <?= e($af['numero']) ?> · <?= e($af['programa']) ?>: <?= e($af['texto']) ?> · <a href="<?= e(admin_url('/fichas/' . $af['id'])) ?>">ver ficha</a></li><?php endforeach; ?></ul><?php endif; ?>
 <h2 class="h6 mt-4" id="alertas">Alertas de actividad sospechosa</h2>
 <div class="row g-3">
  <div class="col-md-6 d-flex align-items-center"><div class="form-check"><input class="form-check-input" type="checkbox" id="c_al" name="alertas_activas" value="1"<?= chk($cfg['alertas_activas']) ?>><label class="form-check-label" for="c_al">Activar alertas</label></div></div>
  <?= $num('alerta_fallos', 'Avisar desde (cuentas bloqueadas en 1 hora)', 'Entre 1 y 20. Predeterminado: 5. Avisa cuando se bloquean tantas cuentas de titulares en una hora (posible intento de adivinar PINs).') ?>
 </div>
 <h2 class="h6 mt-4">Copia de seguridad automática</h2>
 <div class="row g-3"><?= $num('copia_horas', 'Crear una copia cada (horas)', 'Entre 1 y 168. Predeterminado: 24. Siempre queda solo la más reciente.') ?></div>
 <button class="btn btn-success mt-4 align-self-start" type="submit">Guardar configuración</button>
 <p class="small text-muted mb-0 mt-2">Los cambios se aplican de inmediato.</p>
</form>

<div class="card card-body mb-3" id="bloqueos">
 <h2 class="h6">Cuentas bloqueadas</h2>
 <?php if (!$bloqueadas): ?><p class="text-muted small mb-0">No hay cuentas ni IP bloqueadas en este momento.</p>
 <?php else: ?><p class="small text-muted">Quien aparece aquí no puede volver a intentarlo hasta que termine el tiempo o tú lo desbloquees.</p>
 <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Quién</th><th>Tipo</th><th>Tiempo restante</th><th></th></tr></thead><tbody>
 <?php $tipos = ['titular' => 'Titular (PIN)', 'admin' => 'Administrador', 'ip-titular' => 'IP · consulta pública', 'ip-admin' => 'IP · panel']; foreach ($bloqueadas as $b): ?>
  <tr><td><?= e($b['etiqueta']) ?><?= $b['nombre'] ? '<div class="small text-muted">' . e($b['nombre']) . '</div>' : '' ?></td><td><?= e($tipos[$b['tipo']] ?? $b['tipo']) ?><?= !empty($b['nivel']) ? ' · bloqueo n.º ' . (int)$b['nivel'] : '' ?></td>
   <td><?php if ($b['manual']): ?><span class="badge bg-danger">Hasta que lo desbloquees</span><?php else: ?><span class="cuenta-atras fw-semibold" data-segundos="<?= (int)$b['segundos'] ?>"><?= gmdate('i:s', $b['segundos']) ?></span><?php endif; ?></td>
   <td class="text-end"><form method="post" action="<?= e(admin_url('/configuracion/desbloquear')) ?>"><?= csrf_field() ?><input type="hidden" name="clave" value="<?= e($b['clave']) ?>"><button class="btn btn-sm btn-outline-success">Desbloquear</button></form></td></tr>
 <?php endforeach; ?></tbody></table></div><?php endif; ?>
</div>

<div class="card card-body mb-3">
 <h2 class="h6">Alertas recientes</h2>
 <?php if (!$cfg['alertas_activas']): ?><p class="small text-muted mb-0">Las alertas están desactivadas.</p>
 <?php elseif (!$alertas): ?><p class="small text-muted mb-0">Sin actividad sospechosa reciente.</p>
 <?php else: ?><ul class="list-unstyled mb-0"><?php foreach ($alertas as $a): ?><li class="alert alert-<?= $a[0] === 'alta' ? 'danger' : 'warning' ?> py-2 small mb-2"><?= e($a[1]) ?></li><?php endforeach; ?></ul><?php endif; ?>
</div>

<?php if (es_principal()): ?>
<div class="card card-body mb-3" id="asignar-admin">
 <h2 class="h6">Asignar administrador</h2>
 <p class="small text-muted mb-2">Busca a la persona por su número de cédula. Si está registrada, podrás darle acceso al panel y elegir sus permisos (o hacerla administradora principal). Esta es la única forma de dar acceso de administrador.</p>
 <form method="get" action="<?= e(admin_url('/usuarios')) ?>" class="d-flex gap-2 mb-0"><input name="cedula" class="form-control" placeholder="Número de cédula" inputmode="numeric" required><button class="btn btn-outline-success">Buscar</button></form>
</div>
<div class="card card-body mb-3" id="correo">
 <?php $cc = correo_cfg(); ?>
 <h2 class="h6">Correo del sistema (recuperación de PIN)</h2>
 <p class="small mb-2">Modo: <strong><?= e($cc['modo']) ?></strong><?php if ($cc['modo'] === 'smtp'): ?> · servidor <?= e((string)($cc['smtp']['host'] ?? '')) ?>:<?= (int)($cc['smtp']['puerto'] ?? 587) ?><?php endif; ?> · remitente <?= e($cc['desde'] !== '' ? $cc['desde'] : '(automático)') ?> · enlaces hacia <?= e(correo_sitio()) ?></p>
 <?php if ($cc['modo'] === 'archivo'): ?><div class="alert alert-warning py-2 small">El modo «archivo» NO envía correos: solo los guarda en <code>storage/correos</code>. Cámbialo a «smtp» o «mail» en <code>config.php</code>.</div><?php endif; ?>
 <form method="post" action="<?= e(admin_url('/configuracion/correo-prueba')) ?>" class="d-flex flex-wrap gap-2 mb-1"><?= csrf_field() ?><input type="email" name="correo" class="form-control w-auto flex-grow-1" placeholder="Correo donde recibir la prueba" required maxlength="190"><button class="btn btn-outline-success">Enviar correo de prueba</button></form>
 <p class="small text-muted mb-0">Si la prueba llega, la recuperación de PIN por correo funcionará. Los datos del correo se cambian en <code>config.php</code>.</p>
</div>
<?php endif; ?>
<div class="card card-body mb-3">
 <h2 class="h6">Copia de seguridad</h2>
 <p class="small mb-2"><?php if ($copia['existe']): ?>Última copia: <strong><?= e(hora_legible($copia['fecha'])) ?></strong> · <?= round($copia['bytes'] / 1024) ?> KB<?php else: ?>Todavía no hay copia.<?php endif; ?></p>
 <div class="d-flex flex-wrap gap-2">
  <form method="post" action="<?= e(admin_url('/configuracion/copia')) ?>"><?= csrf_field() ?><button class="btn btn-outline-success">Crear copia ahora</button></form>
  <?php if ($copia['existe']): ?><a class="btn btn-outline-secondary" href="<?= e(admin_url('/configuracion/copia/descargar')) ?>">Descargar la última copia</a><?php endif; ?>
 </div>
 <p class="small text-muted mb-0 mt-2">Se guarda en <code>storage/backups</code> y reemplaza a la anterior. Para restaurarla: en phpMyAdmin usa la opción Importar. <strong>Descarga una copia de vez en cuando y guárdala fuera del hosting.</strong> No incluye las fotos (carpeta <code>storage/fotos</code>).</p>
</div>
