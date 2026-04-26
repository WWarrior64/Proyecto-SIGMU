<?php
declare(strict_types=1);

// Panel principal después del login.
// Aquí mostramos edificios accesibles según el usuario logueado.

/** @var array<string, mixed> $sessionUser */
$sessionUser = (isset($sessionUser) && is_array($sessionUser)) ? $sessionUser : [];
/** @var array<int, array<string, mixed>> $edificios */
$edificios = (isset($edificios) && is_array($edificios)) ? $edificios : [];
/** @var string|null $error */
$error = isset($error) ? (string) $error : null;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>SIGMU - Localizacion | Edificios (RF-07 a RF-09)</title>
</head>
<body>
    <?php if (isset($sessionUser['rol_nombre']) && $sessionUser['rol_nombre'] === 'Administrador'): ?>
    <!-- Back Button Solo para Administradores -->
    <div class="back-button" style="margin: 20px 0;">
        <button class="back-btn" onclick="window.location.href='/sigmu'" style="background: none; border: none; cursor: pointer; padding: 8px;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
        </button>
    </div>
    <?php endif; ?>
    
    <h1>SIGMU</h1>
    <p><strong>Modulo:</strong> Localizacion y asignacion — jerarquia edificio &rarr; sala &rarr; activos.</p>

    <?php if (!empty($_GET['error'])): ?>
        <p style="color: #b00020; background-color: #fde8e8; padding: 10px; border-radius: 4px; border: 1px solid #b00020;">
            Error: <?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <?php if (!empty($_GET['info'])): ?>
        <p style="color: #0c5460; background-color: #d1ecf1; padding: 10px; border-radius: 4px; border: 1px solid #bee5eb;">
            ℹ️ <?= htmlspecialchars((string) $_GET['info'], ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p style="color: #b00020; background-color: #fde8e8; padding: 10px; border-radius: 4px; border: 1px solid #b00020;">
            Error BD: <?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <!-- Datos básicos de la sesión -->
    <p>
        Sesion activa:
        <strong><?= htmlspecialchars((string) ($sessionUser['nombre_completo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
        (<?= htmlspecialchars((string) ($sessionUser['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)
        - Rol: <strong><?= htmlspecialchars((string) ($sessionUser['rol_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
    </p>
    <?php if (!empty($sessionUser['ver_todo'])): ?>
        <p>Perfil administrador: acceso global habilitado.</p>
    <?php else: ?>
        <p>Perfil restringido: solo edificios asignados.</p>
    <?php endif; ?>
    <p><a href="/sigmu/logout">Cerrar sesion</a></p>

    <!-- Lista de edificios: clic lleva a salas del edificio -->
    <h2>Edificios accesibles</h2>
    <?php if (!$edificios): ?>
        <p>No hay edificios asignados para este usuario.</p>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
            <?php foreach ($edificios as $edificio): ?>
                <div style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <div style="height: 180px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                        <?php if (!empty($edificio['foto'])): ?>
                            <img src="/<?= htmlspecialchars((string) $edificio['foto'], ENT_QUOTES, 'UTF-8') ?>" 
                                 alt="<?= htmlspecialchars((string) $edificio['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                                 style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div style="text-align: center; color: #999;">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                                </svg>
                                <p>Sin foto</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="padding: 15px;">
                        <h3 style="margin: 0 0 10px 0;">
                            <a href="/sigmu/edificio?edificio_id=<?= (int) $edificio['id'] ?>" style="text-decoration: none; color: #007bff;">
                                <?= htmlspecialchars((string) $edificio['nombre'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </h3>
                        <p style="font-size: 0.9em; color: #666; margin-bottom: 10px;">
                            pisos: <?= (int) $edificio['cantidad_pisos'] ?> | salas: <?= (int) $edificio['total_salas'] ?>
                        </p>
                        
                        <?php if (in_array($sessionUser['rol_nombre'], ['Administrador', 'Responsable de Area'])): ?>
                            <div style="margin-top: 10px; text-align: right;">
                                <button type="button" onclick="toggleUploadForm(<?= (int) $edificio['id'] ?>)" style="background: none; border: 1px solid #007bff; color: #007bff; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.75em;">
                                    <?= !empty($edificio['foto']) ? 'Cambiar foto' : 'Agregar foto' ?>
                                </button>
                            </div>

                            <form id="form-upload-<?= (int) $edificio['id'] ?>" action="/sigmu/edificio/actualizar-foto" method="POST" enctype="multipart/form-data" style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 10px; display: none;">
                                <label style="font-size: 0.8em; display: block; margin-bottom: 5px;">Seleccionar imagen:</label>
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <input type="hidden" name="edificio_id" value="<?= (int) $edificio['id'] ?>">
                                    <input type="file" name="foto" accept="image/*" required style="font-size: 0.8em; width: 100%;">
                                    <div style="display: flex; gap: 5px;">
                                        <button type="submit" style="background: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8em; flex: 1;">Subir</button>
                                        <button type="button" onclick="toggleUploadForm(<?= (int) $edificio['id'] ?>)" style="background: #6c757d; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8em;">Cancelar</button>
                                    </div>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <script src="/assets/js/global-menu.js"></script>
</body>
</html>
