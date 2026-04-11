<?php

declare(strict_types=1);

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

// Endpoint AJAX para obtener tipos de activo (filtros)
$router->get('/sigmu/activo/tipos', static function (): void {
    $controller = new ActivoController();
    $controller->obtenerTiposActivo();
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

$router->post('/sigmu/activo/dar-baja', static function (): string {
    $id = (int) ($_POST['id'] ?? 0);
    $controller = new ActivoController();
    return $controller->darDeBaja($id);
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
