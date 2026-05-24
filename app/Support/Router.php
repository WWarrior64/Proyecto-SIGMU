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
    /** @var array<string, callable> */
    private array $putRoutes = [];
    /** @var array<string, callable> */
    private array $deleteRoutes = [];
    /** @var array<callable> */
    private array $middlewares = [];

    public function middleware(callable $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

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

    public function put(string $path, callable $handler): void
    {
        $this->putRoutes[$path] = $handler;
    }

    public function delete(string $path, callable $handler): void
    {
        $this->deleteRoutes[$path] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        // Normalizamos el path (/sigmu, /sigmu/reset, etc).
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $method = strtoupper($method);

        // 1. Ejecutar Middlewares (incluyendo Autorización)
        foreach ($this->middlewares as $middleware) {
            if ($middleware($method, $path) === false) {
                return; // El middleware ya manejó la respuesta/redirección
            }
        }

        // 2. Validación CSRF Automática para métodos de escritura
        if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
            // Excluir login del CSRF si es necesario o manejarlo dentro de AuthController
            // Pero por seguridad, mejor que todas las rutas lo tengan.
            if (!\App\Support\Csrf::validate()) {
                http_response_code(403);
                echo "Error de seguridad: Token CSRF inválido. Por favor recargue la página.";
                return;
            }
        }

        // Elegimos el listado de rutas según el método HTTP.
        $handler = null;
        if ($method === 'GET') {
            $handler = $this->getRoutes[$path] ?? null;
        } elseif ($method === 'POST') {
            $handler = $this->postRoutes[$path] ?? null;
        } elseif ($method === 'PUT') {
            $handler = $this->putRoutes[$path] ?? null;
        } elseif ($method === 'DELETE') {
            $handler = $this->deleteRoutes[$path] ?? null;
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
