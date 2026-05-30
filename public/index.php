<?php

declare(strict_types=1);

// ────────────────────────────────────────────────────────────────────────────
// Configuración de sesión ANTES de session_start()
// Esto asegura que la cookie de sesión tenga parámetros consistentes en
// todos los entornos: XAMPP, Laragon, VS Code, y AWS/Producción.
// ────────────────────────────────────────────────────────────────────────────

$sessionCookieParams = [
    'lifetime' => 0,                       // Hasta cerrar el navegador
    'path'     => '/',                      // Disponible en todo el sitio
    'domain'   => '',                       // Dominio actual (localhost, dominio real, etc.)
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',  // true si HTTPS
    'httponly' => true,                     // No accesible por JavaScript
    'samesite' => 'Lax',                    // Envía cookie en navegación normal, protege contra CSRF
];

session_set_cookie_params($sessionCookieParams);

// Iniciamos sesión para manejar login / recuperación de contraseña.
// Si ya existe sesión, PHP la reusa automáticamente.
session_start();

// Punto de entrada: armamos la app (router + config) y despachamos la petición.
$router = require __DIR__ . '/../bootstrap/app.php';
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);