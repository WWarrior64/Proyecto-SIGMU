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

// Detectar modo: EDITAR o REGISTRAR (Validación absoluta para romper el rastreo de taint)
$modo = 'crear';
if (isset($_GET['modo']) && $_GET['modo'] === 'editar') {
    $modo = 'editar';
}

// Saneamiento de ID con casting explícito
$usuario_id = (isset($_GET['id']) && is_numeric($_GET['id'])) ? (int)$_GET['id'] : null;
$usuario = null;
$roles = $service->obtenerRoles();

if ($modo === 'editar' && $usuario_id) {
    $usuario = $service->obtenerUsuarioPorId($usuario_id);
    if (!$usuario) {
        header('Location: /sigmu/administracion_usuarios/gestion_usuarios');
        exit;
    }
}

$sigmuPageTitle = $modo === 'editar' ? 'EDITAR USUARIO' : 'REGISTRAR USUARIO';
$sigmuLayoutAdmin = true;
$sigmuExtraCss = ['/assets/css/gestion-usuarios.css', '/assets/css/formulario-usuario.css'];
$sigmuExtraScripts = ['/assets/js/formulario-usuario.js'];
require __DIR__ . '/../partials/sigmu_shell_start.php';
?>
    <div class="back-container">
        <button type="button" class="back-btn" onclick="window.location.href='/sigmu/administracion_usuarios/gestion_usuarios'">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"></path>
            </svg>
        </button>
    </div>

    <div class="main-container">
        <div class="content-card">
            
            <h2 class="page-title">
                <?= ($modo === 'editar' ? 'EDITAR USUARIO' : 'REGISTRAR USUARIO') ?>
            </h2>
            
            <hr style="border: none; border-top: 2px solid #e0e0e0; margin: 16px 0 32px 0;">

            <form id="formUsuario" method="POST" action="/sigmu/administracion_usuarios/guardar_usuario" enctype="multipart/form-data">
                <input type="file" id="fotoUsuario" name="foto" accept="image/*" style="display: none;">
                
                <input type="hidden" name="modo" value="<?= ($modo === 'editar' ? 'editar' : 'crear') ?>">
                <?php if ($modo === 'editar' && $usuario): ?>
                    <input type="hidden" name="usuario_id" value="<?= (int)$usuario['id'] ?>">
                <?php endif; ?>

                <div class="form-grid">
                    
                    <!-- AVATAR -->
                    <div class="avatar-container">
                        <div class="avatar">
                            <?php
                            $fotoUsuario = null;
                            if ($modo === 'editar' && $usuario) {
                                $fotoUsuario = $service->obtenerFotoUsuario((int)$usuario['id']);
                            }

                            if ($fotoUsuario) {
                                ?>
                                <img src="<?= htmlspecialchars((string)$fotoUsuario['ruta_foto'], ENT_QUOTES, 'UTF-8') ?>" alt="Foto perfil" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                <?php
                            } else {
                                ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <?php
                            }
                            ?>
                        </div>
                        <button type="button" class="avatar-edit-btn" title="<?= ($modo === 'editar' ? 'Cambiar foto' : 'Subir foto') ?>">
                            ✏️
                        </button>
                    </div>

                    <!-- CAMPOS FORMULARIO -->
                    <div class="fields-container">
                        
                        <?php if ($modo === 'editar' && $usuario): ?>
                        <div class="form-group">
                            <label for="id">ID</label>
                            <input type="text" value="<?= (int)$usuario['id'] ?>" disabled>
                        </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="nombre_completo">Nombre completo</label>
                            <input type="text" name="nombre_completo"
                                   value="<?= ($modo === 'editar' && $usuario) ? htmlspecialchars((string)$usuario['nombre_completo'], ENT_QUOTES, 'UTF-8') : '' ?>"
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" name="username"
                                   value="<?= ($modo === 'editar' && $usuario) ? htmlspecialchars((string)$usuario['username'], ENT_QUOTES, 'UTF-8') : '' ?>"
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="rol_id">Rol</label>
                            <select name="rol_id" required>
                                <option value="">Seleccionar rol</option>
                                <?php foreach ($roles as $rol): ?>
                                <option value="<?= (int)$rol['id'] ?>"
                                    <?= ($modo === 'editar' && $usuario && $usuario['rol_id'] == $rol['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string)$rol['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="estado">Estado</label>
                            <select name="activo" required>
                                <option value="1" <?= ($modo === 'editar' && $usuario && $usuario['activo']) ? 'selected' : '' ?>>Activo</option>
                                <option value="0" <?= ($modo === 'editar' && $usuario && !$usuario['activo']) ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email"
                                   value="<?= ($modo === 'editar' && $usuario) ? htmlspecialchars((string)$usuario['email'], ENT_QUOTES, 'UTF-8') : '' ?>"
                                   required>
                        </div>

                        <?php if ($modo === 'editar' && $usuario): ?>
                        <div class="form-group">
                            <label for="fecha_creado">Fecha creado</label>
                            <input type="text" value="<?= htmlspecialchars(date('d-m-Y', strtotime((string)$usuario['fecha_creado'])), ENT_QUOTES, 'UTF-8') ?>" disabled>
                        </div>
                        <?php endif; ?>

                        <?php if ($modo === 'crear'): ?>
                        <div class="form-group">
                            <label for="contrasena">Contraseña</label>
                            <input type="password" name="contrasena" required>
                        </div>
                        <?php else: ?>
                        <div class="form-group">
                            <label for="contrasena">Contraseña <small>(dejar vacio para mantener)</small></label>
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
    </div>
<?php require __DIR__ . '/../partials/sigmu_shell_end.php';
