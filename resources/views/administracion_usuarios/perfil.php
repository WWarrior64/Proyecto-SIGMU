<?php
declare(strict_types=1);

use App\Support\Csrf;

$usuario = $usuario ?? [];
$success = $success ?? '';
$error = $error ?? '';

$fechaCreado = isset($usuario['fecha_creado']) ? date('d-m-Y', strtotime($usuario['fecha_creado'])) : '01-01-2026';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGMU - Mi Perfil</title>
    <link rel="stylesheet" href="/assets/css/perfil.css">
</head>
<body>

    <header class="header-bar">
        <div class="header-left">
            <button class="menu-btn" id="menuBtn" onclick="openSidebarMenu()">☰</button>
            <img src="/assets/img/unicaes_logo.png" alt="UNICAES" class="logo">
        </div>
        <div class="header-right">
            <button class="logout-btn" title="Cerrar Sesion" onclick="window.location.href='/sigmu/logout'">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="square">
                    <path d="M6 4v16h10M6 4h10M6 4v16" />
                    <path d="M11 12h11" />
                    <path d="M19 8l4 4-4 4" />
                </svg>
            </button>
        </div>
    </header>

    <div class="back-container">
        <button class="back-btn" onclick="window.history.back()">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"></path>
            </svg>
        </button>
    </div>

    <main class="main-container">
        <?php if ($success): ?>
            <div class="alert alert-success">Perfil actualizado correctamente</div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="profile-header-card">
            <div class="avatar-section">
                <div class="profile-avatar" id="avatarPreview">
                    <?php if (!empty($usuario['foto'])): ?>
                        <img src="<?= htmlspecialchars($usuario['foto']) ?>" alt="Avatar">
                    <?php else: ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    <?php endif; ?>
                    <div class="avatar-edit-overlay" id="avatarEditOverlay" style="display: none;" onclick="document.getElementById('fotoInput').click()">
                        <span>📷</span>
                    </div>
                </div>
            </div>
            <div class="user-info-summary">
                <h1 class="user-display-name" id="displayNombre"><?= htmlspecialchars($usuario['nombre_completo'] ?? 'NOMBRE DE USUARIO') ?></h1>
                <p class="user-role-label"><?= htmlspecialchars($usuario['rol_nombre'] ?? 'Rol del usuario') ?></p>
            </div>
            <div class="header-actions-profile">
                <button type="button" class="icon-btn edit-btn" id="btnEditToggle" title="Editar perfil" onclick="toggleEditMode()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </button>
            </div>
        </div>

        <div class="details-section">
            <div class="details-tab-header">
                <h3>Mi información</h3>
            </div>
            
            <form action="/sigmu/perfil/actualizar" method="POST" enctype="multipart/form-data" id="perfilForm">
                <input type="hidden" name="_csrf_token" value="<?= Csrf::getToken() ?>">
                <input type="file" id="fotoInput" name="foto" style="display: none;" accept="image/*" onchange="previewImage(this)">
                
                <div class="info-group">
                    <label>Nombre de usuario</label>
                    <input type="text" name="username" id="inputUsername" value="<?= htmlspecialchars($usuario['username'] ?? '') ?>" class="editable-input" readonly>
                </div>

                <div class="info-group">
                    <label>Nombre completo</label>
                    <input type="text" name="nombre_completo" id="inputNombre" value="<?= htmlspecialchars($usuario['nombre_completo'] ?? '') ?>" class="editable-input" readonly>
                </div>

                <div class="info-group">
                    <label>Email</label>
                    <input type="email" name="email" id="inputEmail" value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" class="editable-input" readonly>
                </div>

                <div class="info-group">
                    <label>Activo desde</label>
                    <p class="static-value"><?= $fechaCreado ?></p>
                </div>

                <div class="form-actions" id="formActions" style="display: none;">
                    <button type="button" class="btn btn-secondary" onclick="toggleEditMode(false)" style="margin-right: 10px;">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </main>

    <script src="/assets/js/perfil.js"></script>
    <script src="/assets/js/global-menu.js"></script>
</body>
</html>
