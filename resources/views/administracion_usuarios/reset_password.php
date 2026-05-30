<?php
declare(strict_types=1);

// Vista donde el usuario define su nueva contraseña.
// El token viene por GET y se reenvía por POST en un input hidden.
$error = isset($error) ? (string) $error : '';
$tokenProvided = isset($token) ? (string) $token : '';
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIGMU - Nueva contraseña</title>
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
            <div class="auth-subtitle">Crear nueva contraseña</div>
        </div>

        <?php if ($error): ?>
            <!-- Aquí mostramos errores: token inválido, contraseña corta, confirmación diferente, etc. -->
            <div class="auth-alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <!-- Formulario para guardar la nueva contraseña -->
        <form class="form" method="post" action="/sigmu/reset">
            <input type="hidden" name="token" value="<?= htmlspecialchars($tokenProvided, ENT_QUOTES, 'UTF-8') ?>">

            <div class="field">
                <label for="password1">Nueva contraseña</label>
                <input id="password1" name="password" type="password" placeholder="Mínimo 8 caracteres" required>
            </div>

            <div class="field">
                <label for="password2">Confirmar contraseña</label>
                <input id="password2" name="password_confirmation" type="password" placeholder="Repite la contraseña" required>
            </div>

            <button class="btn" type="submit">Guardar</button>
        </form>

        <div class="auth-links">
            <a href="/sigmu">Volver a login</a>
            <span style="opacity:0.7;">&nbsp;</span>
        </div>
    </div>
</div>
</body>
</html>

