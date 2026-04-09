<?php

declare(strict_types=1);

use App\Support\Session;
use App\Services\SigmuService;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SigmuController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EdificioController;
use App\Http\Controllers\ActivoController;

// Rutas públicas / navegación base.
$router->get('/', static function (): string {
    $controller = new HomeController();
    return $controller->index();
});

// Rutas de autenticación
$router->get('/sigmu', static function (): string {
    $controller = new SigmuController();
    return $controller->dashboard();
});

$router->get('/sigmu/admin/usuarios', static function (): string {
    require_once __DIR__ . '/../resources/views/administracion_usuarios/gestion_usuarios.php';
    return '';
});

// Login (POST) - valida usuario/contraseña contra tabla usuarios.
$router->post('/sigmu/login', static function (): string {
    $controller = new AuthController();
    $controller->login();
    return '';
});

// Logout - limpia sesión PHP y también la sesión en MySQL (@usuario_id_sesion).
$router->get('/sigmu/logout', static function (): string {
    $controller = new AuthController();
    $controller->logout();
    return '';
});

// Recuperar contraseña - formulario
$router->get('/sigmu/recuperar', static function (): string {
    $controller = new AuthController();
    return $controller->recuperarPasswordForm();
});

// Recuperar contraseña - enviar formulario
$router->post('/sigmu/recuperar', static function (): string {
    $controller = new AuthController();
    return $controller->recuperarPasswordPost();
});

// Resetear contraseña - formulario con token
$router->get('/sigmu/reset', static function (): string {
    $controller = new AuthController();
    return $controller->resetPasswordForm();
});

// Resetear contraseña - guardar nueva contraseña
$router->post('/sigmu/reset', static function (): string {
    $controller = new AuthController();
    return $controller->resetPasswordPost();
});

// Rutas de edificios y salas
$router->get('/sigmu/edificios', static function (): string {
    $controller = new EdificioController();
    return $controller->dashboard();
});

$router->get('/sigmu/edificio', static function (): string {
    $controller = new EdificioController();
    return $controller->salasPorEdificio();
});

// Rutas de activos
$router->get('/sigmu/sala', static function (): string {
    $controller = new ActivoController();
    return $controller->activosPorSala();
});

$router->get('/sigmu/activo/registrar', static function (): string {
    $controller = new ActivoController();
    return $controller->registrarActivoGet();
});

$router->post('/sigmu/activo/registrar', static function (): string {
    $controller = new ActivoController();
    return $controller->registrarActivoPost();
});

// Endpoint AJAX para generar código basado en nombre del activo
$router->get('/sigmu/activo/generar-codigo', static function (): void {
    $controller = new ActivoController();
    $controller->generarCodigo();
});

// Routes for asset CRUD (using query parameters)
$router->get('/activos', static function (): string {
    $controller = new ActivoController();
    return $controller->index();
});

$router->get('/activos/create', static function (): string {
    $controller = new ActivoController();
    return $controller->create();
});

$router->post('/activos', static function (): string {
    $controller = new ActivoController();
    $controller->store();
    return '';
});

// Rutas adicionales para compatibilidad con vistas
$router->get('/sigmu/activo/ver', static function (): string {
    $id = (int) ($_GET['id'] ?? 0);
    $controller = new ActivoController();
    return $controller->show($id);
});

$router->get('/sigmu/activo/editar', static function (): string {
    $id = (int) ($_GET['id'] ?? 0);
    $controller = new ActivoController();
    return $controller->edit($id);
});

$router->post('/sigmu/activo/actualizar', static function (): string {
    $id = (int) ($_POST['id'] ?? 0);
    $controller = new ActivoController();
    $controller->update($id);
    return '';
});

$router->post('/sigmu/activo/eliminar', static function (): string {
    $id = (int) ($_POST['id'] ?? 0);
    $controller = new ActivoController();
    $controller->destroy($id);
    return '';
});

// RUTAS ADMINISTRACION USUARIOS
$router->get('/sigmu/administracion_usuarios/gestion_usuarios', static function (): string {
    require_once __DIR__ . '/../resources/views/administracion_usuarios/gestion_usuarios.php';
    return '';
});

$router->get('/sigmu/administracion_usuarios/formulario_usuario', static function (): string {
    require_once __DIR__ . '/../resources/views/administracion_usuarios/formulario_usuario.php';
    return '';
});

$router->post('/sigmu/administracion_usuarios/guardar_usuario', static function (): string {

    if (!Session::has('auth_user')) {
        http_response_code(403);
        return json_encode(['success' => false, 'message' => 'Acceso denegado']);
    }

    $sessionUser = Session::get('auth_user');
    if ($sessionUser['rol_nombre'] !== 'Administrador') {
        http_response_code(403);
        return json_encode(['success' => false, 'message' => 'Acceso denegado']);
    }

        $service = new SigmuService();
        $service->iniciarSesionBd($sessionUser['id']);

        $modo = $_POST['modo'] ?? 'crear';

        try {
            // BLOQUEO DE SEGURIDAD: NO PERMITIR INACTIVAR EL ULTIMO ADMINISTRADOR
            if ($modo === 'editar' && isset($_POST['activo']) && !(bool)$_POST['activo']) {
                $usuarioAEditar = $service->obtenerUsuarioPorId((int)$_POST['usuario_id']);
                
                // Si el usuario es Administrador
                if ($usuarioAEditar && $usuarioAEditar['rol_nombre'] === 'Administrador') {
                    // Contar cuantos administradores activos quedarian
                    $todosUsuarios = $service->obtenerTodosUsuarios();
                    $contadorAdminsActivos = 0;
                    
                    foreach ($todosUsuarios as $user) {
                        if ($user['rol_nombre'] === 'Administrador' && $user['activo'] && $user['id'] != (int)$_POST['usuario_id']) {
                            $contadorAdminsActivos++;
                        }
                    }
                    
                    // Si es el ultimo administrador activo, bloquear operacion
                    if ($contadorAdminsActivos === 0) {
                        return json_encode([
                            'success' => false,
                            'message' => '⚠️  NO SE PUEDE INACTIVAR: Este es el ÚLTIMO ADMINISTRADOR activo del sistema. Debe existir al menos un administrador activo.',
                            'tipo_error' => 'ultimo_admin'
                        ]);
                    }

                    // No permitir que un administrador se inactive a si mismo
                    if ((int)$_POST['usuario_id'] === (int)$sessionUser['id']) {
                        return json_encode([
                            'success' => false,
                            'message' => '⚠️  NO SE PUEDE INACTIVAR: No puedes desactivar tu propia cuenta de administrador.',
                            'tipo_error' => 'auto_inactivacion'
                        ]);
                    }
                }
            }

            if ($modo === 'crear') {
            // NOTA: Service ya hace el hash internamente, NO HACERLO AQUI
            $usuarioId = $service->registrarUsuario(
                $_POST['username'],
                $_POST['email'],
                $_POST['contrasena'], // PASAR CONTRASEÑA PLANA, NO HASH
                $_POST['nombre_completo'],
                (int)$_POST['rol_id']
            );
        } else {
            $usuarioId = (int)$_POST['usuario_id'];
            
            $service->editarUsuario(
                $usuarioId,
                $_POST['email'],
                $_POST['nombre_completo'],
                (int)$_POST['rol_id'],
                (bool)$_POST['activo']
            );

            if (!empty($_POST['contrasena'])) {
                // NOTA: Service ya hace el hash internamente, PASAR CONTRASEÑA PLANA
                $service->cambiarContrasena($usuarioId, $_POST['contrasena']);
            }
        }

        // GESTIONAR FOTO DE PERFIL
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../public/img/usuarios/';
            
            // Crear carpeta si no existe
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $extension = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $nombreArchivo = 'usuario_' . $usuarioId . '_' . time() . '.' . $extension;
            $rutaCompleta = $uploadDir . $nombreArchivo;
            
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $rutaCompleta)) {
                $rutaDb = '/img/usuarios/' . $nombreArchivo;
                $service->agregarFotoUsuario($usuarioId, $rutaDb, 'Foto de perfil');
            }
        }

        return json_encode(['success' => true, 'usuario_id' => $usuarioId]);
    } catch (Throwable $e) {
        http_response_code(500);
        return json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
});
