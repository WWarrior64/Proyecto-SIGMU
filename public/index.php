<?php

declare(strict_types=1);

// Iniciamos sesión para manejar login / recuperación de contraseña.
// Si ya existe sesión, PHP la reusa automáticamente.
session_start();

// Punto de entrada: armamos la app (router + config) y despachamos la petición.
$router = require __DIR__ . '/../bootstrap/app.php';
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
