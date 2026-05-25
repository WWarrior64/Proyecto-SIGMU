<?php
declare(strict_types=1);

/** @var array|null $activo */
/** @var array $historial */
/** @var int $pagina */
/** @var int $totalPaginas */
/** @var int $total */
/** @var string $busqueda */
/** @var string $filtroAccion */
/** @var string $filtroEstado */
/** @var string $ordenarPor */
/** @var string $ordenDireccion */

$activo = $activo ?? null;
$historial = $historial ?? [];
$pagina = $pagina ?? 1;
$totalPaginas = $totalPaginas ?? 1;
$total = $total ?? 0;
$busqueda = $busqueda ?? '';
$filtroAccion = $filtroAccion ?? '';
$filtroEstado = $filtroEstado ?? '';
$ordenarPor = $ordenarPor ?? 'fecha';
$ordenDireccion = $ordenDireccion ?? 'DESC';

$sigmuPageTitle = 'HISTORIAL DEL ACTIVO';
$sigmuLayoutAdmin = false;
$sigmuExtraCss = ['/assets/css/listado-activos.css', '/assets/css/historial-activo.css'];
$sigmuExtraScripts = ['/assets/js/historial-activo.js'];
require __DIR__ . '/../partials/sigmu_shell_start.php';
?>
    <div class="main-content">

        <!-- Back Button -->
        <div class="back-button">
            <button class="back-btn" onclick="window.location.href='/sigmu/activo/ver?id=<?= (int) ($activo['id'] ?? 0) ?>'">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
            </button>
        </div>

        <!-- Section Header -->
        <div class="section-header">
            <h1 class="section-title">HISTORIAL DE CAMBIOS</h1>
        </div>

        <?php if ($activo): ?>
        <div class="asset-info-bar">
            <span><strong>Activo:</strong> <?= htmlspecialchars((string) ($activo['nombre'] ?? 'Activo'), ENT_QUOTES, 'UTF-8') ?></span>
            <span><strong>Código:</strong> <?= htmlspecialchars((string) ($activo['codigo'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <?php endif; ?>

        <!-- Search and Filter Bar -->
        <form method="GET" action="" class="search-filter-bar" onsubmit="return false;">
            <?= \App\Support\Csrf::field() ?>
            <input type="hidden" name="id" value="<?= (int) ($activo['id'] ?? 0) ?>">
            
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
            </select>

            <select name="estado" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-color); min-width: 160px;">
                <option value="">Todos los estados</option>
                <option value="disponible" <?= ($filtroEstado ?? '') === 'disponible' ? 'selected' : '' ?>>Disponible</option>
                <option value="en_uso" <?= ($filtroEstado ?? '') === 'en_uso' ? 'selected' : '' ?>>En Uso</option>
                <option value="reparacion" <?= ($filtroEstado ?? '') === 'reparacion' ? 'selected' : '' ?>>Reparación</option>
                <option value="descartado" <?= ($filtroEstado ?? '') === 'descartado' ? 'selected' : '' ?>>Descartado</option>
            </select>

            <button type="button" class="filter-btn" id="limpiarFiltrosBtn" style="background: #ffffff; border: 2px solid #212529; color: #212529;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
                <span>Limpiar</span>
            </button>
        </form>

        <!-- Table Container -->
        <div class="table-container historial-table">

            <!-- Table Header -->
            <div class="table-header">
                <div class="table-row">
                    <div class="table-cell cell-id sortable" data-sort="id">
                        ID
                        <span class="sort-icon <?= $ordenarPor === 'id' ? 'active' : '' ?>">
                            <?= $ordenarPor === 'id' ? ($ordenDireccion === 'ASC' ? '↑' : '↓') : '' ?>
                        </span>
                    </div>
                    <div class="table-cell cell-name sortable" data-sort="accion">
                        Acción / Detalle
                        <span class="sort-icon <?= $ordenarPor === 'accion' ? 'active' : '' ?>">
                            <?= $ordenarPor === 'accion' ? ($ordenDireccion === 'ASC' ? '↑' : '↓') : '' ?>
                        </span>
                    </div>
                    <div class="table-cell cell-status sortable" data-sort="estado_nuevo">
                        Estado
                        <span class="sort-icon <?= $ordenarPor === 'estado_nuevo' ? 'active' : '' ?>">
                            <?= $ordenarPor === 'estado_nuevo' ? ($ordenDireccion === 'ASC' ? '↑' : '↓') : '' ?>
                        </span>
                    </div>
                    <div class="table-cell sortable" data-sort="sala_anterior_nombre">
                        Sala Anterior
                        <span class="sort-icon <?= $ordenarPor === 'sala_anterior_nombre' ? 'active' : '' ?>">
                            <?= $ordenarPor === 'sala_anterior_nombre' ? ($ordenDireccion === 'ASC' ? '↑' : '↓') : '' ?>
                        </span>
                    </div>
                    <div class="table-cell sortable" data-sort="sala_nueva_nombre">
                        Sala Actual
                        <span class="sort-icon <?= $ordenarPor === 'sala_nueva_nombre' ? 'active' : '' ?>">
                            <?= $ordenarPor === 'sala_nueva_nombre' ? ($ordenDireccion === 'ASC' ? '↑' : '↓') : '' ?>
                        </span>
                    </div>
                    <div class="table-cell cell-date sortable" data-sort="fecha">
                        Fecha
                        <span class="sort-icon <?= $ordenarPor === 'fecha' ? 'active' : '' ?>">
                            <?= $ordenarPor === 'fecha' ? ($ordenDireccion === 'ASC' ? '↑' : '↓') : '' ?>
                        </span>
                    </div>
                    <div class="table-cell cell-user sortable" data-sort="usuario_nombre">
                        Usuario
                        <span class="sort-icon <?= $ordenarPor === 'usuario_nombre' ? 'active' : '' ?>">
                            <?= $ordenarPor === 'usuario_nombre' ? ($ordenDireccion === 'ASC' ? '↑' : '↓') : '' ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Table Body -->
            <div class="table-body" id="historialTableBody">
                <?php partial('historial_table_rows', ['historial' => $historial, 'general' => false]); ?>
            </div>

            <div id="paginationContainer" class="pagination-container">
                <?php partial('pagination_ajax', [
                    'items' => $historial,
                    'total' => $total,
                    'pagina' => $pagina,
                    'totalPaginas' => $totalPaginas,
                    'label' => 'registros',
                    'ajaxClass' => 'ajax-page-historial'
                ]); ?>
            </div>
        </div>
    </div>

    <script src="/assets/js/listado-activos.js"></script>
<?php require __DIR__ . '/../partials/sigmu_shell_end.php'; ?>