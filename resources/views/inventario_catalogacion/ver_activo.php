<?php
declare(strict_types=1);

$activo = $activo ?? null;
$error = $_GET['error'] ?? '';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIGMU - Detalle del Activo</title>
    <link rel="stylesheet" href="/assets/css/ver-activo.css">
    <link rel="stylesheet" href="/assets/css/historial-activo.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <button class="menu-btn" id="menuBtn" onclick="openSidebarMenu()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
            <div class="logo">
                <img src="/assets/img/logo_unicaes.png" alt="UNICAES" class="logo-img">
            </div>
        </div>
        <div class="header-right">
            <button class="logout-btn" onclick="window.location.href='/sigmu/logout'" title="Cerrar sesión">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="square">
                    <path d="M6 4v16h10M6 4h10M6 4v16" />
                    <path d="M11 12h11" />
                    <path d="M19 8l4 4-4 4" />
                </svg>
            </button>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Back Button -->
        <div class="back-button">
            <button class="back-btn" onclick="window.location.href='/sigmu/sala?sala_id=<?= (int) ($activo['sala_id'] ?? 0) ?>'">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
            </button>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($activo): ?>
            <!-- Section Header -->
            <div class="section-header">
                <h1 class="section-title"><?= htmlspecialchars((string) ($activo['nombre'] ?? 'Detalle del Activo'), ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="action-buttons">
                    <a href="/sigmu/activo/editar?id=<?= (int) $activo['id'] ?>" class="edit-btn" title="Editar activo">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </a>
                    
                    <a href="/sigmu/activo/historial?id=<?= (int) $activo['id'] ?>" class="btn-historial" title="Ver historial de cambios">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        Historial
                    </a>

                    <a href="/sigmu/reporte-falla?activo_id=<?= (int) $activo['id'] ?>" class="btn-reporte" title="Reportar falla o incidencia">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                            <path d="M12 9v4"></path>
                            <path d="M12 17h.01"></path>
                        </svg>
                        Reportar Falla
                    </a>
                    
                    <?php 
                        // Mostrar botón dar de baja solo si usuario tiene permisos y activo no esta descartado
                        $usuarioRol = $_SESSION['auth_user']['rol_nombre'] ?? '';
                        $puedeDarBaja = in_array($usuarioRol, ['Administrador', 'Responsable de Area']);
                        
                        if ($puedeDarBaja && $activo['estado'] !== 'descartado'): 
                    ?>
                    <button class="edit-btn delete-btn" id="btnDarBaja" title="Dar de baja activo">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            <line x1="10" y1="11" x2="10" y2="17"></line>
                            <line x1="14" y1="11" x2="14" y2="17"></line>
                        </svg>
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Left Column - Image and Metadata -->
                <div class="left-column">
                    <!-- Image Container -->
                    <div class="image-container">
                        <?php if (!empty($activo['fotos'])): ?>
                            <img id="mainImage" src="/<?= htmlspecialchars((string) $activo['fotos'][0]['ruta_foto'], ENT_QUOTES, 'UTF-8') ?>" 
                                 alt="Imagen del activo" 
                                 class="asset-image"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="image-placeholder" style="display: none;">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                                <span>Imagen no disponible</span>
                            </div>
                        <?php else: ?>
                            <div class="image-placeholder">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                                <span>Sin imagen</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Gallery -->
                    <?php if (!empty($activo['fotos']) && count($activo['fotos']) > 1): ?>
                        <div class="gallery-thumbnails" style="display: flex; gap: 8px; margin-top: 10px; overflow-x: auto; padding-bottom: 5px;">
                            <?php foreach ($activo['fotos'] as $foto): ?>
                                <img src="/<?= htmlspecialchars((string) $foto['ruta_foto'], ENT_QUOTES, 'UTF-8') ?>" 
                                     alt="Miniatura" 
                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 2px solid transparent;"
                                     onclick="changeMainImage(this)">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Metadata Cards -->
                    <div class="metadata-cards">
                        <div class="metadata-card">
                            <span class="metadata-label">ID</span>
                            <span class="metadata-value"><?= (int) ($activo['id'] ?? 0) ?></span>
                        </div>
                        <div class="metadata-card">
                            <span class="metadata-label">Ubicación</span>
                            <span class="metadata-value" style="font-size: 0.85em;">
                                <?= htmlspecialchars((string) ($activo['sala_nombre'] ?? 'Sin sala'), ENT_QUOTES, 'UTF-8') ?><br>
                                <small style="opacity: 0.8;"><?= htmlspecialchars((string) ($activo['edificio_nombre'] ?? 'Sin edificio'), ENT_QUOTES, 'UTF-8') ?></small>
                            </span>
                        </div>
                        <div class="metadata-card">
                            <span class="metadata-label">Código</span>
                            <span class="metadata-value"><?= htmlspecialchars((string) ($activo['codigo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Details -->
                <div class="right-column">
                    <div class="details-container">
                        <!-- Descripción -->
                        <div class="detail-group">
                            <label class="detail-label">Descripción</label>
                            <div class="detail-value description">
                                <?php if (!empty($activo['descripcion'])): ?>
                                    <?= htmlspecialchars((string) $activo['descripcion'], ENT_QUOTES, 'UTF-8') ?>
                                <?php else: ?>
                                    <span class="no-value">Sin descripción</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Tipo de Activo -->
                        <div class="detail-group">
                            <label class="detail-label">Tipo de Activo</label>
                            <div class="detail-value">
                                <?= htmlspecialchars((string) ($activo['tipo'] ?? 'Sin tipo'), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>

                        <!-- Creado Por -->
                        <div class="detail-group">
                            <label class="detail-label">Creado Por</label>
                            <div class="detail-value">
                                <?= htmlspecialchars((string) ($activo['usuario_creador_nombre'] ?? $activo['usuario_creador_id'] ?? 'Sistema'), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>

                        <!-- Estado -->
                        <div class="detail-group">
                            <label class="detail-label">Estado</label>
                            <div class="detail-value">
                                <span class="status-badge status-<?= htmlspecialchars((string) ($activo['estado'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars(\App\Models\Activo::ESTADOS[$activo['estado']] ?? ($activo['estado'] ?? 'Sin estado'), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                        </div>

                        <!-- Fechas -->
                        <div class="detail-group">
                            <label class="detail-label">Fecha de Creación</label>
                            <div class="detail-value">
                                <?= htmlspecialchars((string) ($activo['fecha_creado'] ?? 'No disponible'), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>

                        <div class="detail-group">
                            <label class="detail-label">Última Actualización</label>
                            <div class="detail-value">
                                <?= htmlspecialchars((string) ($activo['fecha_actualizado'] ?? 'Nunca'), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                Asset not found.
                <h2>Activo no encontrado</h2>
                <p>El activo que estás buscando no existe o ha sido eliminado.</p>
                <a href="/sigmu" class="btn btn-primary">Volver al Listado</a>
            </div>
        <?php endif; ?>
    </main>

    <?php if ($activo): ?>
    <!-- Modal Confirmación Dar de Baja -->
    <div id="modalConfirmacionBaja" class="modal-overlay" style="display: none;">
        <div class="modal-container">
            <div class="modal-header">
                <h3>⚠️ Confirmar Dar de Baja</h3>
                <button class="modal-close" id="btnCerrarModal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Está a punto de dar de baja este activo:</p>
                <p style="font-weight: bold; font-size: 16px; margin: 12px 0; color: #dc2626;">
                    <?= htmlspecialchars($activo['nombre'] ?? 'Activo') ?>
                </p>
                <p>El activo cambiará su estado a <strong>descartado</strong> y no aparecerá en el listado general.</p>
                <p>Esta acción es reversible mediante el filtro de estado.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="btnCancelarBaja">Cancelar</button>
                <form method="POST" action="/sigmu/activo/dar-baja" style="display: inline;">
                    <input type="hidden" name="id" value="<?= (int)$activo['id'] ?>">
                    <button type="submit" class="btn btn-danger">Confirmar Dar de Baja</button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>



    <script src="/assets/js/global-menu.js"></script>
    <script src="/assets/js/ver-activo.js"></script>
    <script src="/assets/js/historial-activo.js"></script>
</body>
</html>
