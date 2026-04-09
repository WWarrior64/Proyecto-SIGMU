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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGMU - Panel Administrador</title>
    <link rel="stylesheet" href="/assets/css/admin-panel.css">
</head>
<body>

    <!-- BARRA SUPERIOR -->
    <header class="header-bar">
        <div class="header-left">
            <button class="menu-btn">☰</button>
            <img src="/assets/img/unicaes_logo.png" alt="UNICAES" class="logo">
            <h1 class="header-title">INICIO</h1>
        </div>
        <div class="header-right">
            <button class="icon-btn" title="Opciones Administrador">🔑</button>
            <button class="icon-btn logout-btn" title="Cerrar Sesion" onclick="window.location.href='/sigmu/logout'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </button>
        </div>
    </header>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-container">
        <div class="content-card">
            <h2 class="page-title">INICIO</h2>
            
            <div class="menu-grid">
                <!-- ESPACIOS -->
                <a href="/sigmu/edificios" class="menu-card">
                    <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                        <line x1="12" y1="22.08" x2="12" y2="12"></line>
                    </svg>
                    <span class="menu-label">ESPACIOS</span>
                </a>

                <!-- HISTORIAL -->
                <a href="/sigmu/historial" class="menu-card">
                    <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <span class="menu-label">HISTORIAL</span>
                </a>

                <!-- USUARIOS -->
                <a href="/sigmu/admin/usuarios" class="menu-card">
                    <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span class="menu-label">USUARIOS</span>
                </a>

                <!-- REPORTES -->
                <a href="/sigmu/reportes" class="menu-card">
                    <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    <span class="menu-label">REPORTES</span>
                </a>

                <!-- MANTENIMIENTO -->
                <a href="/sigmu/mantenimiento" class="menu-card">
                    <svg class="menu-icon" width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" />
                    </svg>
                    <span class="menu-label">MANTENIMIENTO</span>
                </a>
            </div>
        </div>
    </main>

</body>
</html>