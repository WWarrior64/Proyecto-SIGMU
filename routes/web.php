<?php

declare(strict_types=1);

use App\Support\Session;
use App\Services\SigmuService;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SigmuController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EdificioController;
use App\Http\Controllers\SalaController;
use App\Http\Controllers\ActivoController;
use App\Http\Controllers\HistorialController;
use App\Http\Controllers\TipoActivoController;

// Rutas públicas / navegación base.
$router->get('/', static function (): string {
    $controller = new HomeController();
    $controller->index();
    return '';
});

// Rutas de autenticación
$router->get('/sigmu', static function (): string {
    $controller = new SigmuController();
    return $controller->dashboard();
});

$router->get('/sigmu/admin/usuarios', static function (): string {
    return view('administracion_usuarios.gestion_usuarios');
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

// Perfil de usuario
$router->get('/sigmu/perfil', static function (): string {
    $controller = new \App\Http\Controllers\UserController();
    return $controller->perfil();
});

$router->post('/sigmu/perfil/actualizar', static function (): string {
    $controller = new \App\Http\Controllers\UserController();
    $controller->actualizarPerfil();
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

$router->post('/sigmu/edificios/guardar', static function (): string {
    $controller = new EdificioController();
    return $controller->guardar();
});

$router->post('/sigmu/edificio/actualizar-foto', static function (): string {
    $controller = new EdificioController();
    return $controller->updatePhoto();
});

$router->post('/sigmu/edificios/guardar-sala', static function (): string {
    $controller = new EdificioController();
    return $controller->guardarSala();
});

$router->post('/sigmu/edificio/eliminar', static function (): string {
    $controller = new EdificioController();
    return $controller->eliminar();
});

$router->post('/sigmu/sala/eliminar', static function (): string {
    $controller = new EdificioController();
    return $controller->eliminarSala();
});

// Rutas de salas (NUEVO)
$router->get('/sigmu/sala', static function (): string {
    $controller = new SalaController();
    return $controller->activos();
});

// Rutas de activos (CRUD REESTRUCTURADO)
$router->get('/sigmu/activo/ver', static function (): string {
    $id = (int) ($_GET['id'] ?? 0);
    $controller = new ActivoController();
    return $controller->show($id);
});

$router->get('/sigmu/activo/importar', static function (): string {
    $controller = new ActivoController();
    return $controller->import();
});

$router->post('/sigmu/activo/importar', static function (): string {
    $controller = new ActivoController();
    $controller->processImport();
    return '';
});

$router->get('/sigmu/activo/registrar', static function (): string {
    $controller = new ActivoController();
    return $controller->create();
});

$router->post('/sigmu/activo/registrar', static function (): string {
    $controller = new ActivoController();
    $controller->store();
    return '';
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

$router->post('/sigmu/activo/foto/principal', static function (): void {
    $controller = new ActivoController();
    $controller->setPrincipalPhoto();
});

$router->post('/sigmu/activo/foto/eliminar', static function (): void {
    $controller = new ActivoController();
    $controller->deletePhoto();
});

$router->post('/sigmu/activo/dar-baja', static function (): string {
    $id = (int) ($_POST['id'] ?? 0);
    $controller = new ActivoController();
    $controller->darDeBaja($id);
    return '';
});

$router->post('/sigmu/activo/eliminar', static function (): string {
    $id = (int) ($_POST['id'] ?? 0);
    $controller = new ActivoController();
    $controller->destroy($id);
    return '';
});

// Historial de cambios del activo
$router->get('/sigmu/activo/historial', static function (): string {
    $id = (int) ($_GET['id'] ?? 0);
    $controller = new ActivoController();
    return $controller->historial($id);
});

// RUTAS DE REPORTES
$router->get('/sigmu/reporte/individual', static function (): string {
    $id = (int) ($_GET['id'] ?? 0);
    $controller = new \App\Http\Controllers\ReporteController();
    return $controller->individualConfig($id);
});

$router->post('/sigmu/reporte/individual/exportar', static function (): void {
    $controller = new \App\Http\Controllers\ReporteController();
    $controller->exportarIndividual();
});

$router->post('/sigmu/reporte/individual/preview', static function (): void {
    $controller = new \App\Http\Controllers\ReporteController();
    $controller->previewIndividual();
});

$router->get('/sigmu/reportes', static function (): string {
    $controller = new \App\Http\Controllers\ReporteController();
    return $controller->general();
});

$router->post('/sigmu/reporte/general/exportar', static function (): void {
    $controller = new \App\Http\Controllers\ReporteController();
    $controller->exportarGeneral();
});

$router->post('/sigmu/buscar-salas', static function (): void {
    $controller = new \App\Http\Controllers\ReporteController();
    $controller->buscarSalasAjax();
});

$router->post('/sigmu/reporte/general/preview', static function (): void {
    $controller = new \App\Http\Controllers\ReporteController();
    $controller->previewGeneral();
});

$router->get('/sigmu/reporte/inventario/exportar', static function (): void {
    $controller = new \App\Http\Controllers\ReporteController();
    $controller->exportarInventario();
});

// Endpoints AJAX
$router->get('/sigmu/activo/generar-codigo', static function (): void {
    $controller = new ActivoController();
    $controller->generarCodigo();
});

$router->get('/sigmu/activo/generar-codigo-completo', static function (): void {
    $controller = new ActivoController();
    $controller->generarCodigoCompleto();
});

$router->get('/sigmu/activo/tipos', static function (): void {
    $controller = new TipoActivoController();
    $controller->index();
});

// Endpoints AJAX para selección dinámica
$router->get('/sigmu/ajax/salas', static function (): void {
    $edificioId = (int) ($_GET['edificio_id'] ?? 0);
    $controller = new SigmuController();
    $controller->getSalasAjax($edificioId);
});

$router->get('/sigmu/ajax/activos', static function (): void {
    $salaId = (int) ($_GET['sala_id'] ?? 0);
    $controller = new SigmuController();
    $controller->getActivosAjax($salaId);
});

// RUTAS ADMINISTRACION USUARIOS
$router->get('/sigmu/administracion_usuarios/gestion_roles', static function (): string {
    $controller = new \App\Http\Controllers\RolController();
    return $controller->index();
});

$router->post('/sigmu/administracion_usuarios/rol/guardar', static function (): string {
    $controller = new \App\Http\Controllers\RolController();
    return $controller->guardar();
});

$router->post('/sigmu/administracion_usuarios/rol/eliminar', static function (): string {
    $controller = new \App\Http\Controllers\RolController();
    return $controller->eliminar();
});

$router->get('/sigmu/administracion_usuarios/gestion_usuarios', static function (): string {
    return view('administracion_usuarios.gestion_usuarios');
});

$router->get('/sigmu/administracion_usuarios/asignacion_espacios', static function (): string {
    $controller = new \App\Http\Controllers\UserAssignmentController();
    return $controller->index();
});

$router->post('/sigmu/administracion_usuarios/asignar_espacio', static function (): string {
    $controller = new \App\Http\Controllers\UserAssignmentController();
    return $controller->asignar();
});

$router->post('/sigmu/administracion_usuarios/quitar_espacio', static function (): string {
    $controller = new \App\Http\Controllers\UserAssignmentController();
    return $controller->quitar();
});

$router->get('/sigmu/administracion_usuarios/edificios_disponibles', static function (): string {
    $controller = new \App\Http\Controllers\UserAssignmentController();
    return $controller->edificiosDisponibles();
});

$router->get('/sigmu/administracion_usuarios/formulario_usuario', static function (): string {
    return view('administracion_usuarios.formulario_usuario');
});

$router->post('/sigmu/administracion_usuarios/guardar_usuario', static function (): string {

    if (!Session::has('auth_user')) {
        http_response_code(403);
        return json_encode(['success' => false, 'message' => 'Acceso denegado']);
    }

    $sessionUser = Session::get('auth_user');
    if (!\App\Support\Roles::is($sessionUser['rol_id'] ?? 0, \App\Support\Roles::ADMIN)) {
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
                if ($usuarioAEditar && \App\Support\Roles::is($usuarioAEditar['rol_id'] ?? 0, \App\Support\Roles::ADMIN)) {
                    // Contar cuantos administradores activos quedarian
                    $todosUsuarios = $service->obtenerTodosUsuarios();
                    $contadorAdminsActivos = 0;
                    
                    foreach ($todosUsuarios as $u) {
                        if (\App\Support\Roles::is($u['rol_id'] ?? 0, \App\Support\Roles::ADMIN) && $u['activo'] && $u['id'] != (int)$_POST['usuario_id']) {
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

// Historial General de Cambios
$router->get('/sigmu/historial', static function (): string {
    $controller = new \App\Http\Controllers\HistorialController();
    return $controller->index();
});

// RUTAS REPORTE FALLA
$router->get('/sigmu/reporte-falla', static function (): string {
    $controller = new \App\Http\Controllers\FallaController();
    return $controller->index();
});

$router->post('/sigmu/reporte-falla/guardar', static function (): string {
    $controller = new \App\Http\Controllers\FallaController();
    return $controller->guardar();
});

// RUTAS MANTENIMIENTO
$router->get('/sigmu/mantenimiento', static function (): string {
    $controller = new \App\Http\Controllers\MantenimientoController();
    return $controller->index();
});

$router->get('/sigmu/mantenimiento/reportar', static function (): string {
    $controller = new \App\Http\Controllers\MantenimientoController();
    return $controller->reportarFallaForm();
});

$router->post('/sigmu/mantenimiento/reportar', static function (): string {
    $controller = new \App\Http\Controllers\MantenimientoController();
    return $controller->registrarFalla();
});

$router->post('/sigmu/mantenimiento/agendar', static function (): string {
    $controller = new \App\Http\Controllers\MantenimientoController();
    return $controller->agendar();
});

$router->get('/sigmu/mantenimiento/listado', static function (): string {
    $controller = new \App\Http\Controllers\MantenimientoController();
    return $controller->listado();
});

$router->post('/sigmu/mantenimiento/completar', static function (): string {
    $controller = new \App\Http\Controllers\MantenimientoController();
    return $controller->completar();
});

// NUEVA RUTA: Ver detalle de activo para personal de mantenimiento
$router->get('/sigmu/mantenimiento/activo/ver', static function (): string {
    $id = (int) ($_GET['id'] ?? 0);
    $controller = new \App\Http\Controllers\MantenimientoController();
    return $controller->verActivo($id);
});
