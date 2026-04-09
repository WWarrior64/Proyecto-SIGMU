<?php

use App\Support\Session;
use App\Services\SigmuService;

// Proteccion: Solo Administrador puede acceder
if (!Session::has('auth_user')) {
    header('Location: /sigmu');
    exit;
}

$sessionUser = Session::get('auth_user');
if ($sessionUser['rol_nombre'] !== 'Administrador') {
    header('Location: /sigmu');
    exit;
}

$service = new SigmuService();
$service->iniciarSesionBd($sessionUser['id']);

// Detectar modo: EDITAR o REGISTRAR
$modo = $_GET['modo'] ?? 'crear';
$usuario_id = $_GET['id'] ?? null;
$usuario = null;
$roles = $service->obtenerRoles();

if ($modo === 'editar' && $usuario_id) {
    $usuario = $service->obtenerUsuarioPorId($usuario_id);
    if (!$usuario) {
        header('Location: /sigmu/administracion_usuarios/gestion_usuarios');
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGMU - <?= $modo === 'editar' ? 'Editar Usuario' : 'Registrar Usuario' ?></title>
    <link rel="stylesheet" href="/assets/css/gestion-usuarios.css">
    <link rel="stylesheet" href="/assets/css/formulario-usuario.css">
</head>
<body>

    <!-- BARRA SUPERIOR -->
    <header class="header-bar">
        <div class="header-left">
            <button class="menu-btn">☰</button>
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
        <button class="back-btn" onclick="window.location.href='/sigmu/administracion_usuarios/gestion_usuarios'">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"></path>
            </svg>
        </button>
    </div>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-container">
        <div class="content-card">
            
            <h2 class="page-title">
                <?= $modo === 'editar' ? 'EDITAR USUARIO' : 'REGISTRAR USUARIO' ?>
            </h2>
            
            <hr style="border: none; border-top: 2px solid #e0e0e0; margin: 16px 0 32px 0;">

            <form id="formUsuario" method="POST" action="/sigmu/administracion_usuarios/guardar_usuario" enctype="multipart/form-data">
                <input type="file" id="fotoUsuario" name="foto" accept="image/*" style="display: none;">
                
                <input type="hidden" name="modo" value="<?= $modo ?>">
                <?php if ($modo === 'editar'): ?>
                    <input type="hidden" name="usuario_id" value="<?= $usuario['id'] ?>">
                <?php endif; ?>

                <div class="form-grid">
                    
                    <!-- AVATAR -->
                    <div class="avatar-container">
                        <div class="avatar">
                            <?php
                            $fotoUsuario = null;
                            if ($modo === 'editar') {
                                $fotoUsuario = $service->obtenerFotoUsuario($usuario['id']);
                            }
                            
                            if ($fotoUsuario): 
                            ?>
                                <img src="<?= htmlspecialchars($fotoUsuario['ruta_foto']) ?>" alt="Foto perfil" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                            <?php else: ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="avatar-edit-btn" title="<?= $modo === 'editar' ? 'Cambiar foto' : 'Subir foto' ?>">
                            ✏️
                        </button>
                    </div>

                    <!-- CAMPOS FORMULARIO -->
                    <div class="fields-container">
                        
                        <?php if ($modo === 'editar'): ?>
                        <div class="form-group">
                            <label>ID</label>
                            <input type="text" value="<?= $usuario['id'] ?>" disabled>
                        </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label>Nombre completo</label>
                            <input type="text" name="nombre_completo" 
                                   value="<?= $modo === 'editar' ? htmlspecialchars($usuario['nombre_completo']) : '' ?>"
                                   required>
                        </div>

                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" 
                                   value="<?= $modo === 'editar' ? htmlspecialchars($usuario['username']) : '' ?>"
                                   required>
                        </div>

                        <div class="form-group">
                            <label>Rol</label>
                            <select name="rol_id" required>
                                <option value="">Seleccionar rol</option>
                                <?php foreach ($roles as $rol): ?>
                                <option value="<?= $rol['id'] ?>" 
                                    <?= $modo === 'editar' && $usuario['rol_id'] == $rol['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($rol['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Estado</label>
                            <select name="activo" required>
                                <option value="1" <?= $modo === 'editar' && $usuario['activo'] ? 'selected' : '' ?>>Activo</option>
                                <option value="0" <?= $modo === 'editar' && !$usuario['activo'] ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" 
                                   value="<?= $modo === 'editar' ? htmlspecialchars($usuario['email']) : '' ?>"
                                   required>
                        </div>

                        <?php if ($modo === 'editar'): ?>
                        <div class="form-group">
                            <label>Fecha creado</label>
                            <input type="text" value="<?= date('d-m-Y', strtotime($usuario['fecha_creado'])) ?>" disabled>
                        </div>
                        <?php endif; ?>

                        <?php if ($modo === 'crear'): ?>
                        <div class="form-group">
                            <label>Contraseña</label>
                            <input type="password" name="contrasena" required>
                        </div>
                        <?php else: ?>
                        <div class="form-group">
                            <label>Contraseña <small>(dejar vacio para mantener)</small></label>
                            <input type="password" name="contrasena">
                        </div>
                        <?php endif; ?>

                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" 
                            onclick="window.location.href='/sigmu/administracion_usuarios/gestion_usuarios'">
                        CANCELAR
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <?= $modo === 'editar' ? 'CONFIRMAR' : 'CREAR USUARIO' ?>
                    </button>
                </div>

            </form>

        </div>
    </main>

    <script src="/assets/js/formulario-usuario.js"></script>

</body>
</html>