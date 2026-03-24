<?php

declare(strict_types=1);

namespace App\Support;

final class Router
{
    /** @var array<string, callable> */
    private array $getRoutes = [];
    /** @var array<string, callable> */
    private array $postRoutes = [];

    public function get(string $path, callable $handler): void
    {
        $this->getRoutes[$path] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->postRoutes[$path] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

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

        if (!$handler) {
            http_response_code(404);
            echo 'Page Not Found';
            return;
        }

        echo (string) $handler();
    }
}
