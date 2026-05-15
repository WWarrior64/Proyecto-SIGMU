<?php
/**
 * @var array $roles
 */
declare(strict_types=1);

$sigmuPageTitle = 'GESTIÓN DE ROLES';
$sigmuLayoutAdmin = true;
$sigmuExtraCss = ['/assets/css/gestion-roles.css'];
$sigmuExtraScripts = ['/assets/js/gestion-roles.js'];
require __DIR__ . '/../partials/sigmu_shell_start.php';
?>

<div class="main-container">
    <div class="back-button">
        <button type="button" class="back-btn" onclick="window.location.href='/sigmu/administracion_usuarios/gestion_usuarios'" title="Volver a gestión de usuarios">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"></path>
            </svg>
        </button>
    </div>

    <div class="content-card">
        <h2 class="page-title">Roles del Sistema</h2>
        
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>¿Ver todo?</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roles as $rol): ?>
                <tr>
                    <td><?= (int)$rol['id'] ?></td>
                    <td><?= htmlspecialchars($rol['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($rol['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= isset($rol['ver_todo']) && $rol['ver_todo'] ? 'Sí' : 'No' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../partials/sigmu_shell_end.php'; ?>
