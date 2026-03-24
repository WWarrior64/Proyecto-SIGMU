<?php
declare(strict_types=1);

/** @var string|null $error Error de conexion/consulta BD al cargar contexto. */
$error = isset($error) ? (string) $error : null;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>SIGMU - Inicio de sesion | Administracion de usuarios (RF-17, RF-18, RF-19)</title>
</head>
<body>
    <h1>SIGMU</h1>
    <p><strong>Modulo:</strong> Administracion de usuarios — autenticacion y sesiones.</p>

    <?php if (!empty($_GET['error'])): ?>
        <p style="color: #b00020;">Error: <?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p style="color: #b00020;">Error BD: <?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <h2>Iniciar sesion</h2>
    <p>Accede con <code>username</code> o <code>email</code> de la tabla <code>usuarios</code>.</p>
    <form method="post" action="/sigmu/login">
        <label for="username">Usuario o email:</label>
        <input id="username" type="text" name="username" required>
        <br><br>
        <label for="password">Contrasena:</label>
        <input id="password" type="password" name="password" required>
        <br><br>
        <button type="submit">Entrar</button>
    </form>
</body>
</html>
