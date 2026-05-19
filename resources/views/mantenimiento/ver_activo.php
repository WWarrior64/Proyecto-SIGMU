<?php
declare(strict_types=1);

$activo = $activo ?? null;
$error = $_GET['error'] ?? '';

$sigmuPageTitle = 'DETALLE DEL ACTIVO';
$sigmuLayoutAdmin = false;
$sigmuExtraCss = ['/assets/css/ver-activo.css'];
$sigmuExtraScripts = ['/assets/js/ver-activo.js'];
require __DIR__ . '/../partials/sigmu_shell_start.php';
?>
    <div class="main-content">
        <!-- Back Button -->
        <div class="back-button">
            <button class="back-btn" onclick="window.location.href='/sigmu/mantenimiento'" title="Volver al panel técnico">
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

                        <!-- Valor de Adquisicion -->
                        <div class="detail-group">
                            <label class="detail-label">Valor de Adquisición</label>
                            <div class="detail-value">
                                <?= $activo['valor_adquisicion'] !== null ? '$' . number_format((float)$activo['valor_adquisicion'], 2) : '<span class="no-value">No registrado</span>' ?>
                            </div>
                        </div>

                        <!-- Estado -->
                        <div class="detail-group">
                            <label class="detail-label">Estado</label>
                            <div class="detail-value">
                                <span class="status-badge status-<?= htmlspecialchars((string) ($activo['estado'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= str_replace(['_', '-'], ' ', htmlspecialchars(\App\Models\Activo::ESTADOS[$activo['estado']] ?? ($activo['estado'] ?? 'Sin estado'), ENT_QUOTES, 'UTF-8')) ?>
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
                </svg>
                <h2>Activo no encontrado</h2>
                <p>El activo que estás buscando no existe o ha sido eliminado.</p>
                <a href="/sigmu/mantenimiento" class="btn btn-primary">Volver al Panel</a>
            </div>
        <?php endif; ?>
    </div>

<?php require __DIR__ . '/../partials/sigmu_shell_end.php';
