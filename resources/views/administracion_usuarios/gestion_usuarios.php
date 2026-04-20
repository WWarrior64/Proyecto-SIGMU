<?php

// ✅ PROTECCION: Solo Administrador puede acceder
use App\Support\Session;

// Si no hay sesion redirigir al login CORRECTO
if (!Session::has('auth_user')) {
    header('Location: /sigmu');
    exit;
}

$sessionUser = Session::get('auth_user');

// Verificar que sea Administrador exclusivamente
if ($sessionUser['rol_nombre'] !== 'Administrador') {
    header('Location: /sigmu');
    exit;
}

// Cargar usuarios desde la base de datos
use App\Services\SigmuService;
use App\Support\Database;

// Iniciar sesion de BD
$service = new SigmuService();
if (Session::has('auth_user')) {
    $service->iniciarSesionBd($sessionUser['id']);
}

$usuarios = $service->obtenerTodosUsuarios();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGMU - Gestión Usuarios</title>
    <link rel="stylesheet" href="/assets/css/gestion-usuarios.css">
</head>
<body>

    <!-- BARRA SUPERIOR -->
    <header class="header-bar">
        <div class="header-left">
            <button class="menu-btn" id="menuBtn" onclick="openSidebarMenu()">☰</button>
            <img src="/assets/img/unicaes_logo.png" alt="UNICAES" class="logo">
        </div>
        <div class="header-right">
            <button class="icon-btn" title="Opciones Administrador">🔑</button>
            <button class="icon-btn logout-btn" title="Cerrar Sesion" onclick="window.location.href='/sigmu/logout'">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="square">
                    <path d="M6 4v16h10M6 4h10M6 4v16" />
                    <path d="M11 12h11" />
                    <path d="M19 8l4 4-4 4" />
                </svg>
            </button>
        </div>
    </header>

    <!-- BOTON VOLVER -->
    <div class="back-container">
        <button class="back-btn" onclick="window.location.href='/sigmu'">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"></path>
            </svg>
        </button>
    </div>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-container">
        <div class="content-card">
            
            <div class="header-row">
                <h2 class="page-title">USUARIOS</h2>
                <div class="header-actions">
                    <button class="btn btn-secondary">ADMINISTRAR ESPACIOS</button>
                    <button class="btn btn-primary" onclick="window.location.href='/sigmu/administracion_usuarios/formulario_usuario?modo=crear'">+</button>
                </div>
            </div>

            <div class="filters-bar">
                <div class="search-box">
                    <input type="text" placeholder="Buscar usuario...">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="M21 21l-4.35-4.35"></path>
                    </svg>
                </div>
                <button class="filter-btn" id="toggleFilterPanel">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 6h18M7 12h10M10 18h4"></path>
                    </svg>
                    <span>Filtro</span>
                </button>
            </div>

            <!-- PANEL DE FILTROS FUNCIONAL -->
            <div id="filterPanel" class="filter-panel" style="display: none;">
                <div class="filter-grid">
                    <div>
                        <label for="filterRol" class="filter-label">Filtrar por Rol:</label>
                        <select id="filterRol" class="filter-select">
                            <option value="">Todos los Roles</option>
                            <option value="Administrador">Administrador</option>
                            <option value="Responsable de Area">Responsable de Area</option>
                            <option value="Personal Mantenimiento">Personal Mantenimiento</option>
                        </select>
                    </div>
                    <div>
                        <label for="filterEstado" class="filter-label">Filtrar por Estado:</label>
                        <select id="filterEstado" class="filter-select">
                            <option value="">Todos los Estados</option>
                            <option value="Activo">Solo Activos</option>
                            <option value="Inactivo">Solo Inactivos</option>
                        </select>
                    </div>
                    <div>
                        <label class="filter-label">&nbsp;</label>
                        <button id="resetFilters" class="btn-reset">Limpiar Filtros</button>
                    </div>
                </div>
            </div>

            <!-- LISTADO DE USUARIOS -->
            <div class="users-list" id="usersList">

                <!-- ENCABEZADO -->
                <div class="user-item header">
                    <div class="user-avatar"></div>
                    <div class="user-username">Nombre de Usuario</div>
                    <div class="user-role">Rol</div>
                    <div class="user-status">Activo/Inactivo</div>
                    <div class="user-actions"></div>
                </div>

                <?php foreach ($usuarios as $usuario): ?>
                <div class="user-item">
                    <div class="user-avatar">
                        <?php
                        $foto = $service->obtenerFotoUsuario($usuario['id']);
                        if ($foto):
                        ?>
                            <img src="<?= htmlspecialchars($foto['ruta_foto']) ?>" alt="" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                        <?php else: ?>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        <?php endif; ?>
                    </div>
                    <div class="user-username"><?= htmlspecialchars($usuario['username']) ?></div>
                    <div class="user-role"><?= htmlspecialchars($usuario['rol_nombre']) ?></div>
                    <div class="user-status"><?= $usuario['activo'] ? '✅ Activo' : '❌ Inactivo' ?></div>
                    <div class="user-actions">
                        <button class="icon-btn edit-btn" title="Editar usuario" onclick="window.location.href='/sigmu/administracion_usuarios/formulario_usuario?modo=editar&id=<?= $usuario['id'] ?>'">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>

            </div>

        </div>
    </main>
    <script src="/assets/js/gestion-usuarios.js"></script>
    <script src="/assets/js/global-menu.js"></script>
</body>
</html>
