<?php
declare(strict_types=1);

// Vista para solicitar recuperación de contraseña.
// Se trabaja con un mensaje genérico para no revelar si el usuario existe o no.
$message = isset($message) ? (string) $message : '';
$debugToken = isset($debugToken) ? (string) $debugToken : '';
$error = isset($error) ? (string) $error : '';
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIGMU - Recuperar contraseña</title>
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
            <div class="auth-subtitle">Recuperar contraseña</div>
        </div>

        <?php if ($error): ?>
            <!-- Mensaje de error cuando el request no se pudo procesar -->
            <div class="auth-alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <!-- Mensaje informativo (éxito) -->
            <div class="auth-alert" style="background: rgba(240,180,41,0.10); border-color: rgba(240,180,41,0.30); color: #6b3b00;">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (!$message): ?>
            <!-- Formulario para solicitar el link de recuperación -->
            <form class="form" method="post" action="/sigmu/recuperar">
                <div class="field">
                    <label for="login">Usuario o email</label>
                    <input id="login" name="login" type="text" placeholder="Tu usuario o correo" required>
                </div>
                <button class="btn" type="submit">Solicitar</button>
            </form>

            <div class="auth-links">
                <a href="/sigmu">Volver a login</a>
                <span style="opacity:0.7;">&nbsp;</span>
            </div>
        <?php else: ?>
            <div style="margin-top: 8px;">
                <p style="margin: 0 0 12px; font-size: 13px; opacity: 0.85;">
                    Si la cuenta existe, recibirás instrucciones para crear una nueva contraseña.
                </p>
                <?php if ($debugToken): ?>
                    <!-- En local mostramos el token para probar sin correo -->
                    <p style="margin: 0 0 10px; font-size: 13px;">
                        Modo local (debug): token generado:
                        <code><?= htmlspecialchars($debugToken, ENT_QUOTES, 'UTF-8') ?></code>
                    </p>
                    <p style="margin: 0 0 6px; font-size: 13px;">
                        <a href="/sigmu/reset?token=<?= urlencode($debugToken) ?>">Ir a crear nueva contraseña</a>
                    </p>
                <?php endif; ?>
                <p style="margin: 12px 0 0; font-size: 13px;">
                    <a href="/sigmu">Volver a login</a>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>

