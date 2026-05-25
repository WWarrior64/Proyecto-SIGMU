<?php
declare(strict_types=1);

/** @var array $historial */
/** @var array $usuarios */
/** @var bool $esAdministrador */
/** @var string $busqueda */
/** @var string $filtroAccion */
/** @var string $filtroEstado */
/** @var int $filtroUsuario */
/** @var int $pagina */
/** @var int $totalPaginas */
/** @var int $total */
/** @var string $ordenarPor */
/** @var string $ordenDireccion */

$historial = $historial ?? [];
$usuarios = $usuarios ?? [];
$esAdministrador = $esAdministrador ?? false;
$busqueda = $busqueda ?? '';
$filtroAccion = $filtroAccion ?? '';
$filtroEstado = $filtroEstado ?? '';
$filtroUsuario = $filtroUsuario ?? 0;
$pagina = $pagina ?? 1;
$totalPaginas = $totalPaginas ?? 1;
$total = $total ?? 0;
$ordenarPor = $ordenarPor ?? 'fecha';
$ordenDireccion = $ordenDireccion ?? 'DESC';

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
        <form method="GET" action="" class="search-filter-bar" onsubmit="return false;">
            <?= \App\Support\Csrf::field() ?>
            <div class="search-container">
                <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" class="search-input" placeholder="Buscar en historial..." 
                       name="busqueda" id="searchInputHistorial" value="<?= htmlspecialchars($busqueda ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <select name="accion" class="filter-select">
                <option value="">Todas las acciones</option>
                <option value="registro" <?= ($filtroAccion ?? '') === 'registro' ? 'selected' : '' ?>>Registro</option>
                <option value="modificacion" <?= ($filtroAccion ?? '') === 'modificacion' ? 'selected' : '' ?>>Modificación</option>
                <option value="traslado" <?= ($filtroAccion ?? '') === 'traslado' ? 'selected' : '' ?>>Traslado</option>
                <option value="cambio_estado" <?= ($filtroAccion ?? '') === 'cambio_estado' ? 'selected' : '' ?>>Cambio de Estado</option>
                <option value="mantenimiento" <?= ($filtroAccion ?? '') === 'mantenimiento' ? 'selected' : '' ?>>Mantenimiento</option>
                <option value="retiro" <?= ($filtroAccion ?? '') === 'retiro' ? 'selected' : '' ?>>Retiro</option>
                <option value="eliminacion" <?= ($filtroAccion ?? '') === 'eliminacion' ? 'selected' : '' ?>>Eliminación</option>
            </select>

            <select name="estado" class="filter-select">
                <option value="">Todos los estados</option>
                <option value="disponible" <?= ($filtroEstado ?? '') === 'disponible' ? 'selected' : '' ?>>Disponible</option>
                <option value="en_uso" <?= ($filtroEstado ?? '') === 'en_uso' ? 'selected' : '' ?>>En Uso</option>
                <option value="reparacion" <?= ($filtroEstado ?? '') === 'reparacion' ? 'selected' : '' ?>>Reparación</option>
                <option value="descartado" <?= ($filtroEstado ?? '') === 'descartado' ? 'selected' : '' ?>>Descartado</option>
            </select>

            <?php if ($esAdministrador): ?>
            <select name="usuario" class="filter-select">
                <option value="">Todos los usuarios</option>
                <?php foreach ($usuarios as $usuario): ?>
                <option value="<?= (int) $usuario['id'] ?>" <?= ($filtroUsuario ?? '') == $usuario['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($usuario['nombre_completo'], ENT_QUOTES, 'UTF-8') ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>

            <button type="button" class="filter-btn btn-clean" id="limpiarFiltrosBtn">
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
                    <div class="table-cell cell-user sortable" data-sort="usuario_nombre">
                        Usuario
                        <span class="sort-icon <?= $ordenarPor === 'usuario_nombre' ? 'active' : '' ?>">
                            <?= $ordenarPor === 'usuario_nombre' ? ($ordenDireccion === 'ASC' ? '↑' : '↓') : '' ?>
                        </span>
                    </div>
                    <div class="table-cell cell-id sortable" data-sort="id">
                        ID
                        <span class="sort-icon <?= $ordenarPor === 'id' ? 'active' : '' ?>">
                            <?= $ordenarPor === 'id' ? ($ordenDireccion === 'ASC' ? '↑' : '↓') : '' ?>
                        </span>
                    </div>
                    <div class="table-cell sortable" data-sort="activo_codigo">
                        Activo
                        <span class="sort-icon <?= $ordenarPor === 'activo_codigo' ? 'active' : '' ?>">
                            <?= $ordenarPor === 'activo_codigo' ? ($ordenDireccion === 'ASC' ? '↑' : '↓') : '' ?>
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
                </div>
            </div>

            <!-- Table Body -->
            <div class="table-body" id="historialTableBody">
                <?php partial('historial_table_rows', ['historial' => $historial, 'general' => true]); ?>
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
<?php require __DIR__ . '/../partials/sigmu_shell_end.php'; ?>