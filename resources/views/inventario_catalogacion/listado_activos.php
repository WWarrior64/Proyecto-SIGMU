<?php
declare(strict_types=1);

$salaId = isset($salaId) ? (int) $salaId : 0;
$activos = (isset($activos) && is_array($activos)) ? $activos : [];
$success = $_GET['success'] ?? '';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>SIGMU - Inventario | Activos por sala (RF-01 a RF-06, consulta RF-02)</title>
</head>
<body>
    <h1>Activos de la sala <?= (int) $salaId ?></h1>
    <p><strong>Modulo:</strong> Inventario y catalogacion — consulta por ubicacion.</p>
    <p>
        <a href="/sigmu">Volver a edificios</a> | 
        <a href="/sigmu/activo/registrar?sala_id=<?= $salaId ?>">Agregar Activo</a>
    </p>

    <?php if ($success): ?>
        <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 10px 12px; border-radius: 8px; margin-bottom: 12px;">
            <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if (!$activos): ?>
        <p>No hay activos para esta sala o no tienes acceso.</p>
    <?php else: ?>
        <table border="1" cellpadding="6" cellspacing="0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Codigo</th>
                    <th>Nombre</th>
                    <th>Estado</th>
                    <th>Foto principal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activos as $activo): ?>
                    <tr>
                        <td><?= (int) $activo['id'] ?></td>
                        <td><?= htmlspecialchars((string) $activo['codigo'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $activo['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $activo['estado'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($activo['foto_principal'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
