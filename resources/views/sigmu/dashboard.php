<?php
declare(strict_types=1);

$error = isset($error) ? (string) $error : null;
$sessionUser = (isset($sessionUser) && is_array($sessionUser)) ? $sessionUser : null;
$edificios = (isset($edificios) && is_array($edificios)) ? $edificios : [];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>SIGMU - Dashboard</title>
</head>
<body>
    <h1>SIGMU</h1>
    <p>Demo MVC conectada con vistas/procedimientos de la base de datos.</p>

    <?php if (!empty($_GET['error'])): ?>
        <p style="color: #b00020;">Error: <?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p style="color: #b00020;">Error BD: <?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if (!$sessionUser): ?>
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
    <?php else: ?>
        <p>
            Sesion activa:
            <strong><?= htmlspecialchars((string) $sessionUser['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></strong>
            (<?= htmlspecialchars((string) $sessionUser['username'], ENT_QUOTES, 'UTF-8') ?>)
            - Rol: <strong><?= htmlspecialchars((string) $sessionUser['rol_nombre'], ENT_QUOTES, 'UTF-8') ?></strong>
        </p>
        <?php if (!empty($sessionUser['ver_todo'])): ?>
            <p>Perfil administrador: acceso global habilitado.</p>
        <?php else: ?>
            <p>Perfil restringido: solo edificios asignados.</p>
        <?php endif; ?>
        <p><a href="/sigmu/logout">Cerrar sesion</a></p>

        <h2>Mis edificios</h2>
        <?php if (!$edificios): ?>
            <p>No hay edificios asignados para este usuario.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($edificios as $edificio): ?>
                    <li>
                        <a href="/sigmu/edificio?edificio_id=<?= (int) $edificio['id'] ?>">
                            <?= htmlspecialchars((string) $edificio['nombre'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        - pisos: <?= (int) $edificio['cantidad_pisos'] ?>
                        - salas: <?= (int) $edificio['total_salas'] ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>
