<?php
declare(strict_types=1);

use App\Support\Csrf;

// Vista para editar un activo existente
$error = isset($error) ? (string) $error : '';
$success = isset($success) ? (string) $success : '';

// Datos para los dropdowns
$tiposActivo = isset($tiposActivo) && is_array($tiposActivo) ? $tiposActivo : [];
$habitaciones = isset($habitaciones) && is_array($habitaciones) ? $habitaciones : [];

// Datos del activo a editar
$activo = $activo ?? null;

// Generar token CSRF
$csrfToken = Csrf::getToken();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIGMU - Editar Activo</title>
    <link rel="stylesheet" href="/assets/css/activo-form.css">
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
            <span class="page-title">Editar activo</span>
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
        <div class="form-card">
            <h1 class="form-title">EDITAR ACTIVO</h1>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php 
            $successMessage = $success ?: ($_GET['success'] ?? '');
            if ($successMessage): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if ($activo): ?>
                <form id="activoForm" method="post" action="/sigmu/activo/actualizar" enctype="multipart/form-data">
                    <!-- Token CSRF -->
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <!-- ID del activo oculto -->
                    <input type="hidden" name="id" value="<?= (int) $activo['id'] ?>">
                    
                    <!-- Fila 1 -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="id">ID:</label>
                            <input type="text" id="id" name="id" disabled class="input-disabled" 
                                   value="<?= (int) $activo['id'] ?>">
                        </div>

                        <div class="form-group">
                            <label for="tipo_activo_id">Tipo de activo: <span class="required">*</span></label>
                            <select id="tipo_activo_id" name="tipo_activo_id" required>
                                <option value="">Seleccionar tipo...</option>
                                <?php foreach ($tiposActivo as $tipo): ?>
                                    <option value="<?= (int) $tipo['id'] ?>"
                                        <?= ($tipo['id'] == ($activo['tipo_activo_id'] ?? 0)) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($tipo['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="usuario">Usuario:</label>
                            <input type="text" id="usuario" name="usuario" disabled class="input-disabled" 
                                   value="<?= htmlspecialchars($_SESSION['auth_user']['nombre_completo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <div class="form-group">
                            <label for="codigo">Código: <span class="required">*</span></label>
                            <input type="text" id="codigo" name="codigo" required 
                                   placeholder="Ej: ACT-001"
                                   value="<?= htmlspecialchars((string) ($activo['codigo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                   pattern="[A-Za-z0-9\-]+"
                                   title="Solo letras, números y guiones"
                                   readonly class="input-readonly">
                        </div>
                    </div>

                    <!-- Fila 2 -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombre">Nombre: <span class="required">*</span></label>
                            <input type="text" id="nombre" name="nombre" required 
                                   placeholder="Nombre del activo"
                                   value="<?= htmlspecialchars((string) ($activo['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                   maxlength="100">
                        </div>

                        <div class="form-group">
                            <label for="estado">Estado: <span class="required">*</span></label>
                            <select id="estado" name="estado" required>
                                <option value="">Seleccionar estado...</option>
                                <?php foreach (\App\Models\Activo::ESTADOS as $key => $label): ?>
                                    <option value="<?= $key ?>" <?= (($activo['estado'] ?? '') === $key) ? 'selected' : '' ?>>
                                        <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="edificio_id">Edificio: <span class="required">*</span></label>
                            <select id="edificio_id" name="edificio_id" required>
                                <option value="">Seleccionar edificio...</option>
                                <?php foreach ($edificios as $edificio): ?>
                                    <option value="<?= (int) $edificio['id'] ?>"
                                        <?= ($edificio['id'] == ($edificioActualId ?? 0)) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($edificio['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="sala_id">Sala: <span class="required">*</span></label>
                            <select id="sala_id" name="sala_id" required>
                                <option value="">Seleccionar sala...</option>
                                <?php foreach ($habitaciones as $habitacion): ?>
                                    <option value="<?= (int) $habitacion['id'] ?>"
                                        data-edificio="<?= (int) $habitacion['edificio_id'] ?>"
                                        <?= ($habitacion['id'] == ($activo['sala_id'] ?? 0)) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($habitacion['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="fecha_creado">Fecha creado:</label>
                            <input type="date" id="fecha_creado" name="fecha_creado" 
                                   value="<?= htmlspecialchars((string) (date('Y-m-d', strtotime($activo['fecha_creado'])) ?? date('Y-m-d')), ENT_QUOTES, 'UTF-8') ?>"
                                   class="input-date" readonly>
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div class="form-group full-width">
                        <label for="descripcion">Descripción:</label>
                        <textarea id="descripcion" name="descripcion" rows="4" 
                                  placeholder="Descripción detallada del activo..."
                                  maxlength="500"><?= htmlspecialchars((string) ($activo['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <!-- Imágenes actuales -->
                    <?php 
                    $fotos = \App\Support\Database::connection()->prepare("SELECT id, ruta_foto, es_principal FROM activo_foto WHERE activo_id = ? ORDER BY es_principal DESC, id DESC");
                    $fotos->execute([(int)$activo['id']]);
                    $fotosActuales = $fotos->fetchAll();
                    if (!empty($fotosActuales)): ?>
                        <div class="form-group full-width">
                            <label>Imágenes actuales:</label>
                            <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
                                <?php foreach ($fotosActuales as $f): ?>
                                    <div style="position: relative; border: 1px solid #ddd; border-radius: 4px; padding: 5px; background: #f9f9f9; <?= $f['es_principal'] ? 'border-color: #007bff; box-shadow: 0 0 5px rgba(0,123,255,0.3);' : '' ?>">
                                        <img src="/<?= htmlspecialchars((string) $f['ruta_foto'], ENT_QUOTES, 'UTF-8') ?>" 
                                             alt="Imagen del activo" 
                                             style="width: 100px; height: 100px; object-fit: cover; border-radius: 2px; display: block;">
                                        
                                        <div class="photo-actions-container">
                                            <?php if (!$f['es_principal']): ?>
                                                <button type="button" class="btn-photo-action btn-star" onclick="submitFotoAction('/sigmu/activo/foto/principal', <?= (int)$f['id'] ?>)" title="Hacer principal">⭐</button>
                                                <button type="button" class="btn-photo-action btn-delete" onclick="if(confirm('¿Eliminar esta foto permanentemente?')) submitFotoAction('/sigmu/activo/foto/eliminar', <?= (int)$f['id'] ?>)" title="Eliminar">🗑️</button>
                                            <?php else: ?>
                                                <span class="label-is-principal">Principal</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Nuevas fotos -->
                    <div class="form-group full-width">
                        <label for="fotos">Agregar nuevas fotos:</label>
                        <div class="file-input-wrapper">
                            <input type="file" id="fotos" name="fotos[]" accept="image/*" class="file-input" multiple onchange="previewNewPhotos(this, <?= !empty($fotosActuales) ? 'true' : 'false' ?>)">
                            <div class="file-input-label">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                                <span id="fileInputText">Seleccionar archivos o arrastrar aquí (puedes elegir varios)</span>
                            </div>
                        </div>
                        
                        <!-- Contenedor de Previsualización Temporal -->
                        <div id="newPhotosPreview"></div>
                        
                        <small class="form-hint">Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 5MB por archivo. <strong>Las fotos de arriba se subirán solo cuando presiones ACTUALIZAR.</strong></small>
                    </div>

                    <!-- Botones -->
                    <div class="form-actions">
                        <button type="button" class="btn btn-cancel" onclick="window.location.href='/sigmu/activo/ver?id=<?= (int) ($activo['id'] ?? 0) ?>'">CANCELAR</button>
                        <button type="submit" class="btn btn-submit">ACTUALIZAR</button>
                    </div>
                </form>

                <!-- Formulario oculto para acciones de fotos -->
                <form id="fotoActionForm" method="POST" style="display: none;">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="foto_id" id="action_foto_id">
                    <input type="hidden" name="activo_id" value="<?= (int)$activo['id'] ?>">
                </form>
            <?php else: ?>
                <div class="alert alert-error">
                    <h2>Activo no encontrado</h2>
                    <p>El activo que intentas editar no existe o ha sido eliminado.</p>
                    <a href="/sigmu" class="btn btn-cancel" style="margin-top: 15px;">Volver al listado</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="/assets/js/global-menu.js"></script>
    <script src="/assets/js/activo-form.js"></script>
</body>
</html>