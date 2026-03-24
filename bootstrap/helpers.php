<?php

declare(strict_types=1);

if (!function_exists('config_path')) {
    function config_path(string $file = ''): string
    {
        $base = __DIR__ . '/../config';
        return $file === '' ? $base : $base . '/' . ltrim($file, '/');
    }
}

if (!function_exists('view')) {
    /**
     * Render a PHP view file from resources/views.
     *
     * @param array<string, mixed> $data
     */
    function view(string $view, array $data = []): string
    {
        $viewPath = __DIR__ . '/../resources/views/' . str_replace('.', '/', $view) . '.php';
        if (!is_file($viewPath)) {
            return 'View not found: ' . $viewPath;
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $viewPath;
        return (string) ob_get_clean();
    }
}
