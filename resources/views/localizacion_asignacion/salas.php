<?php
declare(strict_types=1);

// Vista de salas por edificio.
// El controlador nos manda el edificioId y el listado de salas.
$edificioId = isset($edificioId) ? (int) $edificioId : 0;
$salas = (isset($salas) && is_array($salas)) ? $salas : [];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>SIGMU - Localizacion | Salas por edificio (RF-07 a RF-09)</title>
</head>
<body>
    <h1>Salas del edificio <?= (int) $edificioId ?></h1>
    <p><strong>Modulo:</strong> Localizacion y asignacion.</p>
    <p><a href="/sigmu">Volver a edificios</a></p>

    <?php if (!$salas): ?>
        <p>No hay salas para este edificio o no tienes acceso.</p>
    <?php else: ?>
        <!-- Cada sala lleva al listado de activos -->
        <ul>
            <?php foreach ($salas as $sala): ?>
                <li>
                    <a href="/sigmu/sala?sala_id=<?= (int) $sala['id'] ?>">
                        <?= htmlspecialchars((string) $sala['nombre'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                    - piso <?= (int) $sala['numero_piso'] ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</body>
</html>
