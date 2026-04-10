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
            <button class="menu-btn" id="menuBtn">
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
            <button class="back-btn" onclick="history.back()">
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
            <div class="section-header" style="display: flex; align-items: center; justify-content: space-between; gap: 20px;">
                <h1 class="section-title" style="margin: 0; flex: 1;"><?= htmlspecialchars((string) ($activo['nombre'] ?? 'Detalle del Activo'), ENT_QUOTES, 'UTF-8') ?></h1>
                <div style="display: flex; align-items: center; gap: 16px;">
                    <a href="/sigmu/activo/editar?id=<?= (int) $activo['id'] ?>" class="edit-btn" title="Editar activo" style="margin: 0;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </a>
                    <a href="/sigmu/activo/historial?id=<?= (int) $activo['id'] ?>" class="btn-historial" title="Ver historial de cambios" style="margin: 0;">
                        <span>📋</span> Historial
                    </a>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Left Column - Image and Metadata -->
                <div class="left-column">
                    <!-- Image Container -->
                    <div class="image-container">
                        <?php if (!empty($activo['imagen'])): ?>
                            <img src="/<?= htmlspecialchars((string) $activo['imagen'], ENT_QUOTES, 'UTF-8') ?>" 
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

                    <!-- Metadata Cards -->
                    <div class="metadata-cards">
                        <div class="metadata-card">
                            <span class="metadata-label">ID</span>
                            <span class="metadata-value"><?= (int) ($activo['id'] ?? 0) ?></span>
                        </div>
                        <div class="metadata-card">
                            <span class="metadata-label">Sala ID</span>
                            <span class="metadata-value"><?= (int) ($activo['sala_id'] ?? 0) ?></span>
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
                                <?= htmlspecialchars((string) ($activo['usuario_creador_id'] ?? 'Sistema'), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>

                        <!-- Estado -->
                        <div class="detail-group">
                            <label class="detail-label">Estado</label>
                            <div class="detail-value">
                                <span class="status-badge status-<?= htmlspecialchars((string) ($activo['estado'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars((string) ($activo['estado'] ?? 'Sin estado'), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                        </div>

                        <!-- Fecha de Creación -->
                        <div class="detail-group">
                            <label class="detail-label">Fecha de Creación</label>
                            <div class="detail-value">
                                <?= htmlspecialchars((string) ($activo['fecha_creado'] ?? 'No disponible'), ENT_QUOTES, 'UTF-8') ?>
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

    <script src="/assets/js/ver-activo.js"></script>
    <script src="/assets/js/historial-activo.js"></script>
</body>
</html>
