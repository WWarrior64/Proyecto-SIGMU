<?php
declare(strict_types=1);

$sigmuPageTitle = 'Administrar Espacios de Usuario';
$sigmuLayoutAdmin = true;
$sigmuExtraCss = ['/assets/css/asignacion-espacios.css'];
$sigmuExtraScripts = ['/assets/js/asignacion-espacios.js'];

require __DIR__ . '/../partials/sigmu_shell_start.php';
?>

<div class="sigmu-back-row">
    <button type="button" class="sigmu-back-btn" onclick="window.location.href='/sigmu'">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
        <span>Volver al panel</span>
    </button>
</div>

<div class="asignacion-container">
    <div class="asignacion-header">
        <h1>Administrar Espacios de Usuario</h1>
        
        <div class="search-box">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" id="search-input" placeholder="Buscar usuario o edificio...">
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="sigmu-alert sigmu-alert--error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="asignacion-table">
            <thead>
                <tr>
                    <th style="width: 80px;">ID</th>
                    <th>Nombre</th>
                    <th>Espacio asignado</th>
                    <th>Acción</th>
                    <th style="width: 60px; text-align: center;">Añadir</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                    <?php 
                        $userAsigs = $asignaciones[(int)$usuario['id']] ?? [];
                        $totalAsigs = count($userAsigs);
                        $firstRow = true;
                    ?>
                    
                    <?php if ($totalAsigs === 0): ?>
                        <tr class="user-group-border">
                            <td class="user-id"><?= str_pad((string)$usuario['id'], 3, '0', STR_PAD_LEFT) ?></td>
                            <td class="user-name"><?= htmlspecialchars($usuario['nombre_completo']) ?></td>
                            <td class="no-assignments">Sin espacios asignados</td>
                            <td>—</td>
                            <td style="text-align: center;">
                                <button class="btn-expand" onclick="abrirModalAsignar(<?= $usuario['id'] ?>, '<?= htmlspecialchars($usuario['nombre_completo'], ENT_QUOTES) ?>')">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                </button>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($userAsigs as $index => $asig): ?>
                            <tr class="<?= $firstRow ? 'user-group-border' : '' ?> <?= $totalAsigs > 1 ? 'user-row--multiple' : 'user-row--first' ?>">
                                <?php if ($firstRow): ?>
                                    <td class="user-id" rowspan="<?= $totalAsigs ?>"><?= str_pad((string)$usuario['id'], 3, '0', STR_PAD_LEFT) ?></td>
                                    <td class="user-name" rowspan="<?= $totalAsigs ?>"><?= htmlspecialchars($usuario['nombre_completo']) ?></td>
                                <?php endif; ?>
                                
                                <td>
                                    <span class="building-badge">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"></path><path d="M9 8h1"></path><path d="M9 12h1"></path><path d="M9 16h1"></path><path d="M14 8h1"></path><path d="M14 12h1"></path><path d="M14 16h1"></path><path d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"></path></svg>
                                        <?= htmlspecialchars($asig['edificio_nombre']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a class="action-link" onclick="quitarAsignacion(<?= $usuario['id'] ?>, <?= $asig['edificio_id'] ?>, '<?= htmlspecialchars($asig['edificio_nombre'], ENT_QUOTES) ?>')">Quitar</a>
                                </td>
                                
                                <?php if ($firstRow): ?>
                                    <td style="text-align: center;" rowspan="<?= $totalAsigs ?>">
                                        <button class="btn-expand" onclick="abrirModalAsignar(<?= $usuario['id'] ?>, '<?= htmlspecialchars($usuario['nombre_completo'], ENT_QUOTES) ?>')">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                        </button>
                                    </td>
                                <?php endif; ?>
                            </tr>
                            <?php $firstRow = false; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para asignar edificio -->
<div id="asignar-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Asignar Espacio</h2>
            <button class="btn-close">&times;</button>
        </div>
        <form id="asignar-form">
            <div class="modal-body">
                <input type="hidden" name="usuario_id" id="modal-usuario-id">
                
                <div class="form-group">
                    <label>Usuario seleccionado</label>
                    <div id="modal-usuario-nombre" style="font-weight: 700; color: var(--sigmu-ink);"></div>
                </div>

                <div class="form-group">
                    <label for="edificio_id">Seleccionar Edificio</label>
                    <select name="edificio_id" id="edificio_id" class="form-select" required>
                        <option value="">-- Seleccione un edificio --</option>
                        <?php foreach ($edificios as $edificio): ?>
                            <option value="<?= $edificio['id'] ?>"><?= htmlspecialchars($edificio['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="sigmu-btn btn-secondary">Cancelar</button>
                <button type="submit" class="sigmu-btn btn-primary">Asignar</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../partials/sigmu_shell_end.php'; ?>
