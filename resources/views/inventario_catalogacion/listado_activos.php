<?php
declare(strict_types=1);

$salaId = $salaId ?? 0;
$activos = $activos ?? [];
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
$sala = $sala ?? null;
$edificio = $edificio ?? null;

// Valores por defecto para paginacion (compatibilidad con ambos metodos del controlador)
$pagina = $pagina ?? 1;
$totalPaginas = $totalPaginas ?? 1;
$total = $total ?? count($activos);
$busqueda = $busqueda ?? '';
$ordenarPor = $ordenarPor ?? 'id';
$ordenDireccion = $ordenDireccion ?? 'DESC';

// Filtros pasados desde el controlador
$tiposDisponibles = $tiposDisponibles ?? [];
$estadosSeleccionados = $estadosSeleccionados ?? [];
$tiposSeleccionados = $tiposSeleccionados ?? [];

$sigmuPageTitle = 'ACTIVOS';
$sigmuLayoutAdmin = false;
$sigmuExtraCss = ['/assets/css/listado-activos.css', '/assets/css/delete-modal.css'];
$sigmuExtraScripts = ['/assets/js/listado-activos.js', '/assets/js/delete-modal.js'];
require __DIR__ . '/../partials/sigmu_shell_start.php';
?>
    <div class="main-content">
    
        <!-- Back Button -->
        <div class="back-button">
            <button class="back-btn" onclick="window.location.href='/sigmu/edificio?edificio_id=<?= (int) ($edificio_id ?? 0) ?>'">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
            </button>
        </div>
        <!-- Section Header -->
        <div class="section-header">
            <h1 class="section-title">
                <?php if ($edificio && $sala): ?>
                    <?= htmlspecialchars((string)$edificio, ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars((string)$sala, ENT_QUOTES, 'UTF-8') ?>
                <?php else: ?>
                    Activos Registrados
                <?php endif; ?>
            </h1>
            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="exportarInventario('pdf')" class="btn-reporte" style="background: #dc3545; color: white; border: none; padding: 8px 15px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                    PDF
                </button>
                <button type="button" onclick="exportarInventario('excel')" class="btn-reporte" style="background: #198754; color: white; border: none; padding: 8px 15px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
                    Excel
                </button>
                <script>
                function exportarInventario(formato) {
                    const urlParams = new URLSearchParams(window.location.search);
                    urlParams.set('formato', formato);
                    window.location.href = '/sigmu/reporte/inventario/exportar?' + urlParams.toString();
                }
                </script>
                <button class="add-btn" style="background-color: #4b5563;" onclick="window.location.href='/sigmu/activo/importar?sala_id=<?= $salaId ?>'" title="Importar activos desde Excel/CSV">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                </button>
                <button class="add-btn" onclick="window.location.href='/sigmu/activo/registrar?sala_id=<?= $salaId ?>'" title="Agregar nuevo activo">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                </button>
            </div>
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

        <!-- Search and Filter Bar -->
        <div class="search-filter-bar">
            <div class="search-container">
                <form onsubmit="return false;" style="display:inline-block; width: 100%;">
                    <!-- Campos trampa para engañar al autocompletador -->
                    <input type="text" name="prevent_autofill_user" style="display:none">
                    <input type="password" name="prevent_autofill_pass" style="display:none">
                    
                    <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" class="search-input" placeholder="Buscar activos..." id="searchInput" autocomplete="off" name="search_field_dummy">
                </form>
            </div>
            <button class="filter-btn" id="filterBtn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                </svg>
                <span>Filtro</span>
            </button>
        </div>

        <!-- Table Container -->
        <div class="table-container">
            <!-- Table Header -->
            <div class="table-header">
                <div class="table-row">
                    <div class="table-cell cell-id sortable" data-sort="codigo">
                        Código
                        <span class="sort-icon <?= $ordenarPor === 'codigo' ? 'active' : '' ?>">
                            <?= $ordenarPor === 'codigo' ? ($ordenDireccion === 'ASC' ? '↑' : '↓') : '' ?>
                        </span>
                    </div>
                    <div class="table-cell cell-name sortable" data-sort="nombre">
                        Nombre
                        <span class="sort-icon <?= $ordenarPor === 'nombre' ? 'active' : '' ?>">
                            <?= $ordenarPor === 'nombre' ? ($ordenDireccion === 'ASC' ? '↑' : '↓') : '' ?>
                        </span>
                    </div>
                    <div class="table-cell cell-type sortable" data-sort="tipo">
                        Tipo
                        <span class="sort-icon <?= $ordenarPor === 'tipo' ? 'active' : '' ?>">
                            <?= $ordenarPor === 'tipo' ? ($ordenDireccion === 'ASC' ? '↑' : '↓') : '' ?>
                        </span>
                    </div>
                    <div class="table-cell cell-valor sortable" data-sort="valor_adquisicion">
                        Valor
                        <span class="sort-icon <?= $ordenarPor === 'valor_adquisicion' ? 'active' : '' ?>">
                            <?= $ordenarPor === 'valor_adquisicion' ? ($ordenDireccion === 'ASC' ? '↑' : '↓') : '' ?>
                        </span>
                    </div>
                    <div class="table-cell cell-status sortable" data-sort="estado">
                        Estado
                        <span class="sort-icon <?= $ordenarPor === 'estado' ? 'active' : '' ?>">
                            <?= $ordenarPor === 'estado' ? ($ordenDireccion === 'ASC' ? '↑' : '↓') : '' ?>
                        </span>
                    </div>
                    <div class="table-cell cell-ubicacion sortable" data-sort="sala_nombre">
                        Ubicación
                        <span class="sort-icon <?= $ordenarPor === 'sala_nombre' ? 'active' : '' ?>">
                            <?= $ordenarPor === 'sala_nombre' ? ($ordenDireccion === 'ASC' ? '↑' : '↓') : '' ?>
                        </span>
                    </div>
                    <div class="table-cell cell-actions">Acciones</div>
                </div>
            </div>

            <div class="table-body" id="activosTableBody">
                <?php partial('activos_table_rows', ['activos' => $activos, 'salaId' => $salaId]); ?>
            </div>

            <div id="paginationContainer" class="pagination-container">
                <?php partial('pagination_ajax', [
                    'items' => $activos,
                    'total' => $total,
                    'pagina' => $pagina,
                    'totalPaginas' => $totalPaginas,
                    'label' => 'activos',
                    'ajaxClass' => 'ajax-page'
                ]); ?>
            </div>
        </div>
    </div>

    <script>
    // Variables globales para el sistema de filtros
    window.SIGMU_DATA = {
        tiposDisponibles: <?= json_encode($tiposDisponibles ?? []) ?>,
        estadosSeleccionados: <?= json_encode($estadosSeleccionados ?? []) ?>,
        tiposSeleccionados: <?= json_encode($tiposSeleccionados ?? []) ?>
    };
    </script>
<?php require __DIR__ . '/../partials/sigmu_shell_end.php';