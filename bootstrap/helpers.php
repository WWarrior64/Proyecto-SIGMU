<?php

declare(strict_types=1);

// Helpers pequeños para no repetir rutas y para renderizar vistas.

if (!function_exists('config_path')) {
    function config_path(string $file = ''): string
    {
        // Nos devuelve la ruta absoluta a /config o a un archivo dentro.
        $base = __DIR__ . '/../config';
        return $file === '' ? $base : $base . '/' . ltrim($file, '/');
    }
}

if (!function_exists('view')) {
    /**
     * Renderiza una vista PHP desde /resources/views.
     * La idea es mantener el controlador limpio y dejar el HTML en archivos separados.
     *
     * @param array<string, mixed> $data
     */
    function view(string $view, array $data = []): string
    {
        // Convertimos "modulo.archivo" en "modulo/archivo.php"
        $viewPath = __DIR__ . '/../resources/views/' . str_replace('.', '/', $view) . '.php';
        if (!is_file($viewPath)) {
            return 'View not found: ' . $viewPath;
        }

        // Pasamos $data a variables sueltas para usarlas en la vista.
        extract($data, EXTR_SKIP);
        ob_start();
        require $viewPath;
        return (string) ob_get_clean();
    }
}
