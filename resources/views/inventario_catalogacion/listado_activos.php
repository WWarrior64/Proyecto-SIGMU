<?php
declare(strict_types=1);

$salaId = $salaId ?? 0;
$activos = $activos ?? [];
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
$sala = $sala ?? null;
$edificio = $edificio ?? null;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIGMU - Gestión de Activos</title>
    <link rel="stylesheet" href="/assets/css/listado-activos.css">
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
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </button>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
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
                    <div class="table-cell cell-id">ID</div>
                    <div class="table-cell cell-name">Nombre</div>
                    <div class="table-cell cell-type">Tipo</div>
                    <div class="table-cell cell-status">Estado</div>
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
                            <div class="table-cell cell-id" data-label="ID"><?= (int) ($activo['id'] ?? 0) ?></div>
                            <div class="table-cell cell-name" data-label="Nombre"><?= htmlspecialchars((string) ($activo['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="table-cell cell-type" data-label="Tipo Activo"><?= htmlspecialchars((string) ($activo['tipo'] ?? 'Sin tipo'), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="table-cell cell-status" data-label="Estado">
                                <span class="status-badge status-<?= htmlspecialchars((string) ($activo['estado'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars((string) ($activo['estado'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                            <div class="table-cell cell-actions" data-label="Acciones">
                                <a href="/sigmu/activo/ver?id=<?= (int) ($activo['id'] ?? 0) ?>" class="action-btn action-edit" title="Editar">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </a>
                                <form method="POST" action="/sigmu/activo/eliminar" style="display: inline;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este activo? Esta acción no se puede deshacer.');">
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
        </div>
    </main>

    <script src="/assets/js/listado-activos.js"></script>
</body>
</html>