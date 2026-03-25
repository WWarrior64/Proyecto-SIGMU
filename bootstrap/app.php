<?php

declare(strict_types=1);

use App\Support\Router;

// Cargamos autoload de Composer para poder usar nuestras clases con namespace (App\...).
require_once __DIR__ . '/../vendor/autoload.php';

// Helpers globales: view(), config_path(), etc.
require_once __DIR__ . '/helpers.php';

// Leemos variables del .env (local) y las volcamos a $_ENV.
// Esto es una versión simple (suficiente para el proyecto actual).
$envFile = __DIR__ . '/../.env';
if (is_file($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// Creamos el router y cargamos las rutas web.
$router = new Router();
require __DIR__ . '/../routes/web.php';

return $router;
