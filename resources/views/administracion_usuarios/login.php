<?php
declare(strict_types=1);

use App\Support\Csrf;

// Esta vista dibuja la pantalla de inicio de sesión.
// El controlador solo nos manda "error" cuando algo falló en BD.
$error = isset($error) ? (string) $error : null;

// Errores que llegan por querystring (por ejemplo: credenciales incorrectas).
$queryError = isset($_GET['error']) ? (string) $_GET['error'] : null;
$resetOk = isset($_GET['reset_ok']) ? (string) $_GET['reset_ok'] : '';

// Armamos un mensaje único para no estar mostrando 2 cosas a la vez.
$alert = null;
if (!empty($queryError)) {
    $alert = $queryError;
} elseif (!empty($resetOk)) {
    $alert = 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.';
} elseif ($error) {
    $alert = 'Error BD: ' . $error;
}

// Generar token CSRF para el formulario
$csrfToken = Csrf::getToken();
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIGMU - Inicio de sesión</title>
    <link rel="stylesheet" href="/assets/css/auth.css">
</head>
<body>
<div class="auth-bg">
    <div class="auth-card">
        <div class="auth-brand">
            <div class="unicaes-badge">
                <img class="unicaes-logo" src="/assets/img/unicaes_logo.png" alt="UNICAES">
            </div>
            <div class="sigmu-title">SIGMU</div>
            <div class="auth-divider"></div>
            <div class="auth-subtitle">Accede con:</div>
        </div>

        <?php if ($alert): ?>
            <!-- Mensajes de estado / error para el usuario -->
            <div class="auth-alert">
                <?= htmlspecialchars($alert, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <!-- Formulario de login -->
        <form class="form" method="post" action="/sigmu/login">
            <!-- Token CSRF para protección contra ataques CSRF -->
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            
            <div class="field">
                <label for="username">Usuario o email</label>
                <input id="username" name="username" type="text" placeholder="Tu usuario o correo" required>
            </div>

            <div class="field">
                <label for="password">Contraseña</label>
                <input id="password" name="password" type="password" placeholder="Tu contraseña" required>
            </div>

            <button class="btn" type="submit">Iniciar sesión</button>
        </form>

        <!-- Accesos rápidos -->
        <div class="auth-links">
            <a href="/sigmu/recuperar" title="Recuperar contraseña">Recuperar contraseña</a>
            <a href="/sigmu" title="Volver al inicio">Inicio</a>
        </div>
    </div>
</div>
</body>
</html>
