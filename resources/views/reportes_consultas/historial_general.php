<?php
declare(strict_types=1);

$historial = $historial ?? [];
$usuarios = $usuarios ?? [];
$esAdministrador = $esAdministrador ?? false;

$sigmuPageTitle = 'HISTORIAL GENERAL';
$sigmuLayoutAdmin = (bool) $esAdministrador;
$sigmuExtraCss = ['/assets/css/listado-activos.css', '/assets/css/historial-activo.css', '/assets/css/historial-general.css'];
$sigmuExtraScripts = ['/assets/js/listado-activos.js', '/assets/js/historial-activo.js'];
require __DIR__ . '/../partials/sigmu_shell_start.php';
?>
    <div class="main-content">

        <!-- Back Button -->
        <div class="back-button">
            <button class="back-btn" onclick="window.location.href='/sigmu'">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
            </button>
        </div>

        <!-- Section Header -->
        <div class="section-header">
            <h1 class="section-title">HISTORIAL GENERAL DE CAMBIOS</h1>
        </div>

        <!-- Search and Filter Bar -->
        <form method="GET" action="" class="search-filter-bar">
            
            <div class="search-container">
                <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" class="search-input" placeholder="Buscar en historial..." 
                       name="busqueda" id="searchInputHistorial" value="<?= htmlspecialchars($busqueda ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <select name="accion" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-color); min-width: 160px;">
                <option value="">Todas las acciones</option>
                <option value="registro" <?= ($filtroAccion ?? '') === 'registro' ? 'selected' : '' ?>>Registro</option>
                <option value="modificacion" <?= ($filtroAccion ?? '') === 'modificacion' ? 'selected' : '' ?>>Modificación</option>
                <option value="traslado" <?= ($filtroAccion ?? '') === 'traslado' ? 'selected' : '' ?>>Traslado</option>
                <option value="cambio_estado" <?= ($filtroAccion ?? '') === 'cambio_estado' ? 'selected' : '' ?>>Cambio de Estado</option>
                <option value="mantenimiento" <?= ($filtroAccion ?? '') === 'mantenimiento' ? 'selected' : '' ?>>Mantenimiento</option>
                <option value="retiro" <?= ($filtroAccion ?? '') === 'retiro' ? 'selected' : '' ?>>Retiro</option>
                <option value="eliminacion" <?= ($filtroAccion ?? '') === 'eliminacion' ? 'selected' : '' ?>>Eliminación</option>
            </select>

            <select name="estado" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-color); min-width: 160px;">
                <option value="">Todos los estados</option>
                <option value="disponible" <?= ($filtroEstado ?? '') === 'disponible' ? 'selected' : '' ?>>Disponible</option>
                <option value="en_uso" <?= ($filtroEstado ?? '') === 'en_uso' ? 'selected' : '' ?>>En Uso</option>
                <option value="reparacion" <?= ($filtroEstado ?? '') === 'reparacion' ? 'selected' : '' ?>>Reparación</option>
                <option value="descartado" <?= ($filtroEstado ?? '') === 'descartado' ? 'selected' : '' ?>>Descartado</option>
            </select>

            <?php if ($esAdministrador): ?>
            <select name="usuario" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-color); min-width: 180px;">
                <option value="">Todos los usuarios</option>
                <?php foreach ($usuarios as $usuario): ?>
                <option value="<?= (int) $usuario['id'] ?>" <?= ($filtroUsuario ?? '') == $usuario['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($usuario['nombre_completo'], ENT_QUOTES, 'UTF-8') ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>

            <button type="button" class="filter-btn" id="limpiarFiltrosBtn" style="background: #ffffff; border: 2px solid #212529; color: #212529;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
                <span>Limpiar</span>
            </button>
        </form>

        <!-- Table Container -->
        <div class="table-container historial-table historial-general-table">

            <!-- Table Header -->
            <div class="table-header">
                <div class="table-row">
                    <div class="table-cell cell-user" style="width: 140px;">Usuario</div>
                    <div class="table-cell cell-id">ID</div>
                    <div class="table-cell">Activo</div>
                    <div class="table-cell cell-name">Acción / Detalle</div>
                    <div class="table-cell cell-status">Estado</div>
                    <div class="table-cell">Sala Anterior</div>
                    <div class="table-cell">Sala Actual</div>
                    <div class="table-cell cell-date">Fecha</div>
                </div>
            </div>

            <!-- Table Body -->
            <div class="table-body" id="historialTableBody">
                <?php if (empty($historial)): ?>
                    <div class="empty-state">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M9 11l3 3l8-8M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p>No hay registros en el historial general</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($historial as $registro): ?>
                        <div class="table-row historial-row">

                            <!-- USUARIO -->
                            <div class="table-cell cell-user" data-label="Usuario" style="width: 140px;">
                                <div class="user-inline">
                                    <div class="user-avatar-small">
                                        <?= strtoupper(substr($registro['usuario_nombre'] ?? 'U', 0, 1)) ?>
                                    </div>
                                    <div class="user-info">
                                        <span class="user-fullname"><?= htmlspecialchars((string) ($registro['usuario_nombre'] ?? 'Usuario desconocido'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="user-username">@<?= htmlspecialchars((string) ($registro['usuario_username'] ?? 'usuario'), ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- ID -->
                            <div class="table-cell cell-id" data-label="ID">
                                <?= (int) ($registro['id'] ?? 0) ?>
                            </div>

                            <!-- ACTIVO -->
                            <div class="table-cell" data-label="Activo">
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <span style="font-weight: 600;"><?= htmlspecialchars((string) ($registro['activo_codigo'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <span style="font-size: 0.85rem; color: #6c757d;"><?= htmlspecialchars((string) ($registro['activo_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </div>

                            <!-- ACCIÓN / DETALLE -->
                            <div class="table-cell cell-name" data-label="Acción / Detalle">
                                <span class="action-badge action-<?= htmlspecialchars((string) ($registro['accion'] ?? 'desconocida'), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars(ucfirst((string) ($registro['accion'] ?? 'N/A')), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <span class="detail-text">
                                    <?= htmlspecialchars((string) ($registro['detalle'] ?? 'Sin detalle'), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>

                            <!-- ESTADO -->
                            <div class="table-cell cell-status" data-label="Estado">
                                <?php if (!empty($registro['estado_anterior']) && !empty($registro['estado_nuevo'])): ?>
                                    <div class="status-changes">
                                        <span class="status-old"><?= htmlspecialchars($registro['estado_anterior'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="status-arrow">→</span>
                                        <span class="status-new"><?= htmlspecialchars($registro['estado_nuevo'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                <?php elseif (!empty($registro['estado_nuevo'])): ?>
                                    <span class="status-only"><?= htmlspecialchars($registro['estado_nuevo'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php else: ?>
                                    <span class="empty-value">-</span>
                                <?php endif; ?>
                            </div>

                            <!-- SALA ANTERIOR -->
                            <div class="table-cell" data-label="Sala Anterior">
                                <?php if (!empty($registro['sala_anterior_nombre'])): ?>
                                    <span class="sala-anterior"><?= htmlspecialchars($registro['sala_anterior_nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php else: ?>
                                    <span class="empty-value">Ninguna</span>
                                <?php endif; ?>
                            </div>

                            <!-- SALA ACTUAL -->
                            <div class="table-cell" data-label="Sala Actual">
                                <?php if (!empty($registro['sala_nueva_nombre'])): ?>
                                    <span class="sala-nueva"><?= htmlspecialchars($registro['sala_nueva_nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php else: ?>
                                    <span class="empty-value">-</span>
                                <?php endif; ?>
                            </div>

                            <!-- FECHA -->
                            <div class="table-cell cell-date" data-label="Fecha">
                                <?php if (!empty($registro['fecha']) && strtotime($registro['fecha']) !== false): ?>
                                    <span><?= date('d-m-Y', strtotime($registro['fecha'])) ?></span>
                                    <span class="time-text"><?= date('H:i', strtotime($registro['fecha'])) ?></span>
                                <?php else: ?>
                                    <span class="empty-value">Fecha no disponible</span>
                                <?php endif; ?>
                            </div>

                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php require __DIR__ . '/../partials/sigmu_shell_end.php';