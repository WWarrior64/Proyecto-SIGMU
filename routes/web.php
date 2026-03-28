<?php

declare(strict_types=1);

use App\Http\Controllers\HomeController;
use App\Http\Controllers\SigmuController;
use App\Http\Controllers\ActivoController;

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

<<<<<<< Updated upstream
$router->get('/sigmu/activo/registrar', static function (): string {
    $controller = new SigmuController();
    return $controller->registrarActivoGet();
});

$router->post('/sigmu/activo/registrar', static function (): string {
    $controller = new SigmuController();
    return $controller->registrarActivoPost();
});
=======
// Rutas para el CRUD de activos
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

$router->get('/activos/{id}', static function (string $id): string {
    $controller = new ActivoController();
    return $controller->show((int) $id);
});

$router->get('/activos/{id}/edit', static function (string $id): string {
    $controller = new ActivoController();
    return $controller->edit((int) $id);
});

$router->put('/activos/{id}', static function (string $id): string {
    $controller = new ActivoController();
    $controller->update((int) $id);
    return '';
});

$router->delete('/activos/{id}', static function (string $id): string {
    $controller = new ActivoController();
    $controller->destroy((int) $id);
    return '';
});
>>>>>>> Stashed changes
