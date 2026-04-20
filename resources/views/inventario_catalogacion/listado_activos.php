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
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIGMU - Gestión de Activos</title>
    <link rel="stylesheet" href="/assets/css/listado-activos.css">
    <link rel="stylesheet" href="/assets/css/delete-modal.css">
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
                    <?= htmlspecialchars($edificio, ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($sala, ENT_QUOTES, 'UTF-8') ?>
                <?php else: ?>
                    Activos Registrados
                <?php endif; ?>
            </h1>
            <button class="add-btn" onclick="window.location.href='/sigmu/activo/registrar?sala_id=<?= $salaId ?>'" title="Agregar nuevo activo">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
            </button>
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
                <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" class="search-input" placeholder="Buscar activos..." id="searchInput">
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

            <!-- Table Body -->
            <div class="table-body">
                <?php if (empty($activos)): ?>
                    <div class="empty-state">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <p>No hay activos registrados</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($activos as $activo): ?>
                        <div class="table-row">
                            <div class="table-cell cell-id" data-label="Código"><?= htmlspecialchars((string) ($activo['codigo'] ?? $activo['id']), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="table-cell cell-name" data-label="Nombre"><?= htmlspecialchars((string) ($activo['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="table-cell cell-type" data-label="Tipo Activo" data-tipo-id="<?= (int) ($activo['tipo_activo_id'] ?? 0) ?>"><?= htmlspecialchars((string) ($activo['tipo'] ?? 'Sin tipo'), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="table-cell cell-status" data-label="Estado">
                                <span class="status-badge status-<?= htmlspecialchars((string) ($activo['estado'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars((string) ($activo['estado'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                            <div class="table-cell cell-ubicacion" data-label="Ubicación"><?= htmlspecialchars((string) ($activo['sala_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="table-cell cell-actions" data-label="Acciones">
                                <a href="/sigmu/activo/ver?id=<?= (int) ($activo['id'] ?? 0) ?>" class="action-btn action-view" title="Ver detalle">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </a>
                                <a href="/sigmu/activo/editar?id=<?= (int) ($activo['id'] ?? 0) ?>" class="action-btn action-edit" title="Editar">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </a>
                                <form method="POST" action="/sigmu/activo/eliminar" style="display: inline;" class="delete-form">
                                    <input type="hidden" name="id" value="<?= (int) ($activo['id'] ?? 0) ?>">
                                    <button type="submit" class="action-btn action-delete" title="Eliminar">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            <line x1="10" y1="11" x2="10" y2="17"></line>
                                            <line x1="14" y1="11" x2="14" y2="17"></line>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

<?php
// Generar la cadena de consulta base manteniendo los filtros y ordenamiento
$currentParams = $_GET;
unset($currentParams['pagina']);
$queryString = http_build_query($currentParams);
$baseUrl = '?' . $queryString . (empty($queryString) ? '' : '&') . 'pagina=';
?>

<!-- Pagination -->
<?php if (isset($totalPaginas) && $totalPaginas > 1): ?>
<div class="pagination-container">
    <div class="pagination-info">
        Mostrando <?= count($activos) ?> de <?= $total ?> activos
    </div>
    <div class="pagination">
        <?php if ($pagina > 1): ?>
            <a href="<?= $baseUrl . ($pagina - 1) ?>" class="pagination-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                Anterior
            </a>
        <?php endif; ?>

        <div class="pagination-pages">
            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                <?php if ($i == $pagina): ?>
                    <span class="pagination-btn active"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= $baseUrl . $i ?>" class="pagination-btn"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>

        <?php if ($pagina < $totalPaginas): ?>
            <a href="<?= $baseUrl . ($pagina + 1) ?>" class="pagination-btn">
                Siguiente
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
        </div>
    </main>

    <script>
    // Variables globales para el sistema de filtros
    window.SIGMU_DATA = {
        tiposDisponibles: <?= json_encode($tiposDisponibles) ?>,
        estadosSeleccionados: <?= json_encode($estadosSeleccionados) ?>,
        tiposSeleccionados: <?= json_encode($tiposSeleccionados) ?>
    };
    </script>

    <script>
    // Sistema de ordenamiento
    console.log('✅ Script de ordenamiento cargado');
    
    document.addEventListener('DOMContentLoaded', function() {
        const sortableHeaders = document.querySelectorAll('.sortable');
        console.log('🔍 Encontrados', sortableHeaders.length, 'encabezados ordenables');
        
        sortableHeaders.forEach(header => {
            header.style.cursor = 'pointer';
            header.style.userSelect = 'none';
            
            header.addEventListener('click', function(e) {
                e.preventDefault();
                
                const sortField = this.getAttribute('data-sort');
                console.log('👉 Click en ordenar por:', sortField);
                
                // Usar URLSearchParams para capturar todos los parámetros GET actuales
                let params = new URLSearchParams(window.location.search);
                
                // Si ya estamos ordenando por este campo, invertir la dirección
                if (params.get('ordenar_por') === sortField) {
                    params.set('orden_direccion', params.get('orden_direccion') === 'ASC' ? 'DESC' : 'ASC');
                } else {
                    params.set('ordenar_por', sortField);
                    params.set('orden_direccion', 'ASC');
                }
                
                // Resetear a página 1 al cambiar ordenamiento
                params.set('pagina', '1');
                
                // Redirigir conservando todos los parámetros (filtros, búsqueda, sala_id, etc.)
                window.location.href = window.location.pathname + '?' + params.toString();
            });
            
            // Efecto hover
            header.addEventListener('mouseenter', function() {
                this.style.backgroundColor = 'rgba(59, 130, 246, 0.05)';
            });
            
            header.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });
    });
    </script>
    
    <style>
    .sortable {
        position: relative;
        transition: background-color 0.15s ease;
    }
    
    .sort-icon {
        margin-left: 6px;
        opacity: 0.4;
        font-size: 12px;
        font-weight: bold;
    }
    
    .sort-icon.active {
        opacity: 1;
        color: #3b82f6;
    }
    
    .sortable:hover .sort-icon {
        opacity: 0.7;
    }
    </style>

    <script src="/assets/js/global-menu.js"></script>
    <script src="/assets/js/listado-activos.js"></script>
    <script src="/assets/js/delete-modal.js"></script>
</body>
</html>