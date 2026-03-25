<?php
declare(strict_types=1);

// Panel principal después del login.
// Aquí mostramos edificios accesibles según el usuario logueado.

/** @var array<string, mixed> $sessionUser */
$sessionUser = (isset($sessionUser) && is_array($sessionUser)) ? $sessionUser : [];
/** @var array<int, array<string, mixed>> $edificios */
$edificios = (isset($edificios) && is_array($edificios)) ? $edificios : [];
/** @var string|null $error */
$error = isset($error) ? (string) $error : null;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>SIGMU - Localizacion | Edificios (RF-07 a RF-09)</title>
</head>
<body>
    <h1>SIGMU</h1>
    <p><strong>Modulo:</strong> Localizacion y asignacion — jerarquia edificio &rarr; sala &rarr; activos.</p>

    <?php if (!empty($_GET['error'])): ?>
        <p style="color: #b00020;">Error: <?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p style="color: #b00020;">Error BD: <?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <!-- Datos básicos de la sesión -->
    <p>
        Sesion activa:
        <strong><?= htmlspecialchars((string) ($sessionUser['nombre_completo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
        (<?= htmlspecialchars((string) ($sessionUser['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)
        - Rol: <strong><?= htmlspecialchars((string) ($sessionUser['rol_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
    </p>
    <?php if (!empty($sessionUser['ver_todo'])): ?>
        <p>Perfil administrador: acceso global habilitado.</p>
    <?php else: ?>
        <p>Perfil restringido: solo edificios asignados.</p>
    <?php endif; ?>
    <p><a href="/sigmu/logout">Cerrar sesion</a></p>

    <!-- Lista de edificios: clic lleva a salas del edificio -->
    <h2>Edificios accesibles</h2>
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
</body>
</html>
