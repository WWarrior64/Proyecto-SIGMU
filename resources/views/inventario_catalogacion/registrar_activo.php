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
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIGMU - Registrar Activo</title>
    <link rel="stylesheet" href="/assets/css/activo-form.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <button class="menu-btn" onclick="history.back()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
            <div class="logo">
                <img src="/assets/img/logo_unicaes.png" alt="UNICAES" class="logo-img">
            </div>
            <span class="page-title">Agregar activo</span>
        </div>
        <div class="header-right">
            <button class="icon-btn" onclick="history.back()" title="Volver">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
            </button>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
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
                               placeholder="Ej: ACT-001"
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
                            <option value="disponible" <?= (isset($formData['estado']) && $formData['estado'] === 'disponible') ? 'selected' : '' ?>>Disponible</option>
                            <option value="en_uso" <?= (isset($formData['estado']) && $formData['estado'] === 'en_uso') ? 'selected' : '' ?>>En uso</option>
                            <option value="reparacion" <?= (isset($formData['estado']) && $formData['estado'] === 'reparacion') ? 'selected' : '' ?>>Reparación</option>
                            <option value="descartado" <?= (isset($formData['estado']) && $formData['estado'] === 'descartado') ? 'selected' : '' ?>>Descartado</option>
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
                </div>

                <!-- Descripción -->
                <div class="form-group full-width">
                    <label for="descripcion">Descripción:</label>
                    <textarea id="descripcion" name="descripcion" rows="4" 
                              placeholder="Descripción detallada del activo..."
                              maxlength="500"><?= htmlspecialchars($formData['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <!-- Foto -->
                <div class="form-group full-width">
                    <label for="foto">Foto principal:</label>
                    <div class="file-input-wrapper">
                        <input type="file" id="foto" name="foto" accept="image/*" class="file-input">
                        <div class="file-input-label">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                            <span>Seleccionar archivo o arrastrar aquí</span>
                        </div>
                    </div>
                    <small class="form-hint">Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 5MB</small>
                </div>

                <!-- Botones -->
                <div class="form-actions">
                    <button type="button" class="btn btn-cancel" onclick="history.back()">CANCELAR</button>
                    <button type="submit" class="btn btn-submit">AGREGAR</button>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Validación del formulario
        document.getElementById('activoForm').addEventListener('submit', function(e) {
            const requiredFields = ['tipo_activo_id', 'codigo', 'nombre', 'estado'];
            let isValid = true;
            let firstError = null;

            requiredFields.forEach(function(fieldName) {
                const field = document.getElementById(fieldName);
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                    if (!firstError) firstError = field;
                } else {
                    field.classList.remove('error');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Por favor, complete todos los campos obligatorios marcados con *');
                if (firstError) firstError.focus();
            }
        });

        // Validación en tiempo real
        document.querySelectorAll('input[required], select[required]').forEach(function(field) {
            field.addEventListener('blur', function() {
                if (!this.value.trim()) {
                    this.classList.add('error');
                } else {
                    this.classList.remove('error');
                }
            });

            field.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.classList.remove('error');
                }
            });
        });

        // Drag and drop para archivo
        const fileInput = document.getElementById('foto');
        const fileLabel = document.querySelector('.file-input-label');

        fileLabel.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        fileLabel.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });

        fileLabel.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                updateFileLabel(files[0].name);
            }
        });

        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                updateFileLabel(this.files[0].name);
            }
        });

        function updateFileLabel(fileName) {
            const span = fileLabel.querySelector('span');
            span.textContent = fileName;
        }
    </script>
</body>
</html>