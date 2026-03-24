<?php

declare(strict_types=1);

use App\Support\Router;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/helpers.php';

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

$router = new Router();
require __DIR__ . '/../routes/web.php';

return $router;
