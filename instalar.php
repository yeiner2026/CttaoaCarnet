<?php
// Cra el PRIMER administrador (solo funciona si no existe ningún usuario). Bórralo después de usarlo.
require __DIR__ . '/app/core.php';
require __DIR__ . '/app/services.php';
iniciar_sesion();
header('X-Frame-Options: DENY');
header('Cache-Control: no-store');
$msg = '';
$ok = false;
$err = [];
try {
    $existe = (int)val('SELECT COUNT(*) FROM usuarios');
} catch (Exception $e) {
    http_response_code(500);
    exit('<p>No se pudo leer la base de datos. Revisa <b>config.php</b> e importa <b>sql/cttaoa.sql</b> en phpMyAdmin.</p>');
}
if ($existe > 0) {
    http_response_code(403);
    exit('<p>El sistema ya está instalado. <b>Elimina el archivo instalar.php</b> del servidor.</p>');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $u = post('usuario');
    $n = post('nombre');
    $pw = $_POST['password'] ?? '';
    if (!preg_match('/^[A-Za-z0-9_.-]{3,60}$/', $u)) $err[] = 'Usuario: 3 a 60 caracteres (letras, números, punto, guion).';
    if ($n === '') $err[] = 'Escribe tu nombre.';
    if (strlen($pw) < 10 || ctype_digit($pw)) $err[] = 'La contraseña debe tener al menos 10 caracteres y no ser solo números.';
    if ($pw !== ($_POST['password2'] ?? '')) $err[] = 'Las contraseñas no coinciden.';
    if (!$err) {
        insert('usuarios', ['usuario' => $u, 'nombre' => $n, 'password_hash' => password_hash($pw, PASSWORD_DEFAULT), 'rol' => 'principal', 'permisos' => '', 'activo' => 1, 'creado' => now()]);
        $ok = true;
        $borrado = @unlink(__FILE__);
    }
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Instalación · CTTAOA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <main class="container py-4" style="max-width:480px">
        <h1 class="h4">Crear administrador principal</h1>
        <?php if ($ok): ?><div class="alert alert-success">Administrador creado. Tu panel está en: <b><?= e('/' . cfg('admin_prefix') . '/login') ?></b><br>
                <?= !empty($borrado) ? 'Este instalador se eliminó solo.' : '<b>Elimina ahora el archivo instalar.php</b> de tu hosting.' ?></div>
            <?php else: foreach ($err as $x): ?><div class="alert alert-danger"><?= e($x) ?></div><?php endforeach; ?>
            <form method="post" class="card card-body shadow-sm"><?= csrf_field() ?>
                <label class="form-label">Usuario</label><input name="usuario" class="form-control mb-2" required value="<?= e(post('usuario')) ?>">
                <label class="form-label">Nombre</label><input name="nombre" class="form-control mb-2" required value="<?= e(post('nombre')) ?>">
                <label class="form-label">Contraseña (mínimo 10 caracteres)</label><input name="password" type="password" class="form-control mb-2" required>
                <label class="form-label">Repite la contraseña</label><input name="password2" type="password" class="form-control mb-3" required>
                <button class="btn btn-success">Crear administrador</button>
            </form><?php endif; ?>
    </main>
</body>

</html>