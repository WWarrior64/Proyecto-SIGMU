<?php

declare(strict_types=1);

use App\Http\Controllers\HomeController;
use App\Http\Controllers\SigmuController;

$router->get('/', static function (): string {
    $controller = new HomeController();
    return $controller->index();
});

$router->get('/sigmu', static function (): string {
    $controller = new SigmuController();
    return $controller->dashboard();
});

$router->post('/sigmu/login', static function (): string {
    $controller = new SigmuController();
    $controller->login();
    return '';
});

$router->get('/sigmu/logout', static function (): string {
    $controller = new SigmuController();
    $controller->logout();
    return '';
});

$router->get('/sigmu/edificio', static function (): string {
    $controller = new SigmuController();
    return $controller->salasPorEdificio();
});

$router->get('/sigmu/sala', static function (): string {
    $controller = new SigmuController();
    return $controller->activosPorSala();
});

$router->get('/sigmu/activo/registrar', static function (): string {
    $controller = new SigmuController();
    return $controller->registrarActivoGet();
});

$router->post('/sigmu/activo/registrar', static function (): string {
    $controller = new SigmuController();
    return $controller->registrarActivoPost();
});
