<?php

declare(strict_types=1);

use App\Http\Controllers\HomeController;
use App\Http\Controllers\SigmuController;

// Rutas públicas / navegación base.
$router->get('/', static function (): string {
    $controller = new HomeController();
    return $controller->index();
});

// Entrada al sistema (login si no hay sesión, panel si ya hay sesión).
$router->get('/sigmu', static function (): string {
    $controller = new SigmuController();
    return $controller->dashboard();
});

// Login (POST) - valida usuario/contraseña contra tabla usuarios.
$router->post('/sigmu/login', static function (): string {
    $controller = new SigmuController();
    $controller->login();
    return '';
});

// Logout - limpia sesión PHP y también la sesión en MySQL (@usuario_id_sesion).
$router->get('/sigmu/logout', static function (): string {
    $controller = new SigmuController();
    $controller->logout();
    return '';
});

// Recuperación de contraseña (solicitud).
$router->get('/sigmu/recuperar', static function (): string {
    $controller = new SigmuController();
    return $controller->recuperarPasswordGet();
});

$router->post('/sigmu/recuperar', static function (): string {
    $controller = new SigmuController();
    return $controller->recuperarPasswordPost();
});

// Recuperación de contraseña (nuevo password usando token).
$router->get('/sigmu/reset', static function (): string {
    $controller = new SigmuController();
    return $controller->resetPasswordGet();
});

$router->post('/sigmu/reset', static function (): string {
    $controller = new SigmuController();
    return $controller->resetPasswordPost();
});

// Navegación jerárquica: edificios → salas → activos (según vistas restringidas).
$router->get('/sigmu/edificio', static function (): string {
    $controller = new SigmuController();
    return $controller->salasPorEdificio();
});

$router->get('/sigmu/sala', static function (): string {
    $controller = new SigmuController();
    return $controller->activosPorSala();
});
