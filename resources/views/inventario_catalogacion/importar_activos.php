<?php
declare(strict_types=1);

use App\Support\Csrf;

$salaId = $salaId ?? 0;
$error = $error ?? '';
$success = $success ?? '';
$results = $results ?? null;

// Limpiar resultados de la sesión después de mostrarlos
if ($results) {
    \App\Support\Session::remove('import_results');
}

$csrfToken = Csrf::getToken();

$sigmuPageTitle = 'IMPORTAR ACTIVOS';
$sigmuLayoutAdmin = false;
$sigmuExtraCss = ['/assets/css/activo-form.css'];
require __DIR__ . '/../partials/sigmu_shell_start.php';
?>
<style>
        .import-info {
            background-color: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #4b5563;
        }
        .import-info ul {
            margin-top: 10px;
            margin-left: 20px;
        }
        .results-container {
            margin-top: 30px;
            padding: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }
        .error-list {
            max-height: 200px;
            overflow-y: auto;
            background: #fff5f5;
            padding: 10px;
            border: 1px solid #feb2b2;
            border-radius: 4px;
            margin-top: 10px;
            font-size: 0.9em;
        }
    </style>
    <div class="main-content">
        <div class="form-card">
            <h1 class="form-title">IMPORTAR ACTIVOS</h1>

            <div class="import-info">
                <p><strong>Instrucciones:</strong></p>
                <ul>
                    <li>Sube un archivo en formato <strong>Excel (.xlsx)</strong> o <strong>CSV</strong>.</li>
                    <li>El sistema intentará identificar automáticamente las columnas (Nombre, Código, Tipo, etc.).</li>
                    <li>La columna <strong>Nombre</strong> es obligatoria.</li>
                    <li>Si no se proporciona un código, el sistema generará uno automáticamente.</li>
                    <li>Los estados se normalizarán según las palabras clave encontradas (bueno, uso, reparación, etc.).</li>
                </ul>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if ($results): ?>
                <div class="results-container">
                    <h3>Resultados de la última importación:</h3>
                    <p style="color: #059669; font-weight: bold;">✅ Éxito: <?= $results['success'] ?> activos importados.</p>
                    
                    <?php if (!empty($results['errors'])): ?>
                        <p style="color: #dc2626; font-weight: bold; margin-top: 10px;">❌ Errores (<?= count($results['errors']) ?>):</p>
                        <div class="error-list">
                            <ul>
                                <?php foreach ($results['errors'] as $err): ?>
                                    <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form action="/sigmu/activo/importar" method="POST" enctype="multipart/form-data" style="margin-top: 20px;">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="sala_id" value="<?= (int) $salaId ?>">

                <div class="form-group">
                    <label for="archivo">Seleccionar archivo Excel o CSV:</label>
                    <div class="file-input-wrapper">
                        <input type="file" id="archivo" name="archivo" accept=".xlsx, .csv" required class="file-input">
                        <div class="file-input-label">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                            <span>Seleccionar archivo (.xlsx, .csv)</span>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-cancel" onclick="window.location.href='/sigmu/sala?sala_id=<?= (int) $salaId ?>'">VOLVER</button>
                    <button type="submit" class="btn btn-submit">PROCESAR IMPORTACIÓN</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Animación simple para el input de archivo
        const fileInput = document.querySelector('.file-input');
        const fileLabel = document.querySelector('.file-input-label span');
        if (fileInput && fileLabel) {
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                fileLabel.textContent = e.target.files[0].name;
            }
        });
        }
    </script>
<?php require __DIR__ . '/../partials/sigmu_shell_end.php';
