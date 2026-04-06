<?php

declare(strict_types=1);

namespace App\Support;

// Router básico para este proyecto.
// Aquí registramos rutas GET/POST y luego resolvemos el handler correcto.
final class Router
{
    /** @var array<string, callable> */
    private array $getRoutes = [];
    /** @var array<string, callable> */
    private array $postRoutes = [];

    public function get(string $path, callable $handler): void
    {
        // Guardamos el handler asociado a la ruta.
        $this->getRoutes[$path] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        // Guardamos el handler asociado a la ruta.
        $this->postRoutes[$path] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        // Normalizamos el path (/sigmu, /sigmu/reset, etc).
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        // Elegimos el listado de rutas según el método HTTP.
        $method = strtoupper($method);
        $handler = null;
        if ($method === 'GET') {
            $handler = $this->getRoutes[$path] ?? null;
        } elseif ($method === 'POST') {
            $handler = $this->postRoutes[$path] ?? null;
        } else {
            http_response_code(405);
            echo 'Method Not Allowed';
            return;
        }

        // Si no existe la ruta, respondemos 404.
        if (!$handler) {
            http_response_code(404);
            echo 'Page Not Found';
            return;
        }

        // Ejecutamos el handler y lo imprimimos como respuesta.
        echo (string) $handler();
    }
}
