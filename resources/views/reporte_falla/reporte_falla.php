<?php
/** @var array $sessionUser */
/** @var array $activo */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGMU - Reportar Falla</title>
    <link rel="stylesheet" href="/assets/css/reporte-falla.css">
</head>
<body>

    <!-- BARRA SUPERIOR -->
    <header class="header-bar">
        <div class="header-left">
            <button class="menu-btn" id="menuBtn" onclick="openSidebarMenu()">☰</button>
            <img src="/assets/img/unicaes_logo.png" alt="UNICAES" class="logo">
            <h1 class="header-title">REPORTAR FALLA</h1>
        </div>
        <div class="header-right">
            <button class="icon-btn logout-btn" title="Cerrar Sesión" onclick="window.location.href='/sigmu/logout'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </button>
        </div>
    </header>

    <div class="back-btn-container">
        <button class="back-btn" onclick="history.back()" title="Regresar">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
        </button>
    </div>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-container">
        <div class="ticket-card">
            <div class="ticket-header">
                <h2>TICKETS</h2>
            </div>

            <form id="formReporteFalla">
                <input type="hidden" name="activo_id" value="<?= (int)$activo['id'] ?>">
                
                <div class="form-grid">
                    <!-- Nombres (Activo) -->
                    <div class="form-group">
                        <label for="activo_nombre">Nombres:</label>
                        <input type="text" id="activo_nombre" class="form-control" value="<?= htmlspecialchars($activo['nombre']) ?>" disabled>
                    </div>

                    <!-- Estado (Pendiente por defecto) -->
                    <div class="form-group">
                        <label for="estado">Estado:</label>
                        <select id="estado" class="form-control" disabled>
                            <option value="pendiente" selected>Pendiente</option>
                        </select>
                    </div>

                    <!-- Tipo de incidencia -->
                    <div class="form-group">
                        <label for="tipo_falla">Tipo de incidencia:</label>
                        <select name="tipo_falla" id="tipo_falla" class="form-control" required>
                            <option value="">Seleccione tipo...</option>
                            <option value="hardware">Falla de Hardware</option>
                            <option value="software">Falla de Software</option>
                            <option value="electrico">Problema Eléctrico</option>
                            <option value="fisico">Daño Físico / Estructural</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>

                    <!-- Descripción -->
                    <div class="form-group full-width">
                        <label for="descripcion">Descripción:</label>
                        <textarea name="descripcion" id="descripcion" class="form-control" placeholder="Describa el problema detectado..." required></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-cancel" onclick="history.back()">CANCELAR</button>
                    <button type="submit" class="btn btn-confirm">CONFIRMAR</button>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Datos del usuario para el menú global
        globalThis.authUser = {
            id: <?= (int)$sessionUser['id'] ?>,
            nombre_completo: '<?= addslashes($sessionUser['nombre_completo']) ?>',
            foto: '<?= $sessionUser['foto'] ?? '' ?>'
        };
    </script>
    <script src="/assets/js/global-menu.js"></script>
    <script src="/assets/js/reporte-falla.js"></script>
</body>
</html>
