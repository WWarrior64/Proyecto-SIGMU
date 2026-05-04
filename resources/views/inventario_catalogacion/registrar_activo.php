<?php
declare(strict_types=1);

use App\Support\Csrf;

// Vista para registrar un nuevo activo
$error = isset($error) ? (string) $error : '';
$success = isset($success) ? (string) $success : '';

// Datos para los dropdowns
$tiposActivo = isset($tiposActivo) && is_array($tiposActivo) ? $tiposActivo : [];
$salaId = isset($salaId) ? (int) $salaId : 0;

// Valores del formulario (para mantener en caso de error)
$formData = isset($formData) && is_array($formData) ? $formData : [];

// Generar token CSRF
$csrfToken = Csrf::getToken();

$sigmuPageTitle = 'REGISTRAR ACTIVO';
$sigmuLayoutAdmin = false;
$sigmuExtraCss = ['/assets/css/activo-form.css'];
$sigmuExtraScripts = ['/assets/js/activo-form.js'];
require __DIR__ . '/../partials/sigmu_shell_start.php';
?>
    <div class="main-content">
        <div class="form-card">
            <h1 class="form-title">AGREGAR ACTIVO</h1>

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

            <form id="activoForm" method="post" action="/sigmu/activo/registrar" enctype="multipart/form-data">
                <!-- Token CSRF -->
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <!-- Sala ID oculto -->
                <input type="hidden" name="sala_id" value="<?= (int) $salaId ?>">
                
                <!-- Fila 1 -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="id">ID:</label>
                        <input type="text" id="id" name="id" disabled class="input-disabled" placeholder="Se genera automáticamente">
                    </div>

                    <div class="form-group">
                        <label for="tipo_activo_id">Tipo de activo: <span class="required">*</span></label>
                        <select id="tipo_activo_id" name="tipo_activo_id" required>
                            <option value="">Seleccionar tipo...</option>
                            <?php foreach ($tiposActivo as $tipo): ?>
                                <option value="<?= (int) $tipo['id'] ?>"
                                    <?= (isset($formData['tipo_activo_id']) && $formData['tipo_activo_id'] == $tipo['id']) ? 'selected' : '' ?>>
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
                               placeholder="Automatically generated when entering the name"
                               value="<?= htmlspecialchars($formData['codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
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
                               value="<?= htmlspecialchars($formData['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               maxlength="100">
                    </div>

                    <div class="form-group">
                        <label for="estado">Estado: <span class="required">*</span></label>
                        <select id="estado" name="estado" required>
                            <option value="">Seleccionar estado...</option>
                            <?php foreach (\App\Models\Activo::ESTADOS as $key => $label): ?>
                                <option value="<?= $key ?>" <?= (isset($formData['estado']) && $formData['estado'] === $key) ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="fecha_creado">Fecha creado:</label>
                        <input type="date" id="fecha_creado" name="fecha_creado" 
                               value="<?= htmlspecialchars($formData['fecha_creado'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>"
                               class="input-date">
                    </div>

                    <div class="form-group">
                        <label for="fecha_actualizado">Fecha actualizado:</label>
                        <input type="date" id="fecha_actualizado" name="fecha_actualizado" 
                               value="<?= htmlspecialchars($formData['fecha_actualizado'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>"
                               class="input-date" disabled>
                    </div>

                    <div class="form-group">
                        <label for="cantidad">Cantidad: <span class="required">*</span></label>
                        <input type="number" id="cantidad" name="cantidad" required 
                               min="1" max="100" step="1" value="1"
                               placeholder="1"
                               value="<?= htmlspecialchars($formData['cantidad'] ?? '1', ENT_QUOTES, 'UTF-8') ?>">
                        <small class="form-hint">Cantidad de activos iguales a crear (max 100)</small>
                    </div>
                </div>

                <!-- Descripción -->
                <div class="form-group full-width">
                    <label for="descripcion">Descripción:</label>
                    <textarea id="descripcion" name="descripcion" rows="4" 
                              placeholder="Descripción detallada del activo..."
                              maxlength="500"><?= htmlspecialchars($formData['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <!-- Fotos -->
                <div class="form-group full-width">
                    <label for="fotos">Fotos del activo:</label>
                    <div class="file-input-wrapper">
                        <input type="file" id="fotos" name="fotos[]" accept="image/*" class="file-input" multiple onchange="previewNewPhotos(this, false)">
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

                    <small class="form-hint">Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 5MB por archivo. <strong>La primera imagen de la lista será la principal.</strong></small>
                </div>

                <!-- Botones -->
                <div class="form-actions">
                    <button type="button" class="btn btn-cancel" onclick="window.location.href='/sigmu/sala?sala_id=<?= (int) $salaId ?>'">CANCELAR</button>
                    <button type="submit" class="btn btn-submit">AGREGAR</button>
                </div>
            </form>
        </div>
    </div>
<?php require __DIR__ . '/../partials/sigmu_shell_end.php';