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

/**
 * ✅ TIMEOUT DE SESION POR INACTIVIDAD
 * 100% nativo PHP, sin dependencias, sin composer, se ejecuta AUTOMATICAMENTE en TODAS las paginas
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Tiempo de inactividad permitido: 15 MINUTOS (900 segundos)
define('SESSION_TIMEOUT', 900);

// Si hay sesion iniciada
if (isset($_SESSION['auth_user'])) {

    // Si ya tenemos tiempo de ultima actividad
    if (isset($_SESSION['ultima_actividad'])) {
        
        // Calcular tiempo transcurrido
        $tiempo_transcurrido = time() - $_SESSION['ultima_actividad'];
        
        // Si paso mas tiempo del permitido: CERRAR SESION
        if ($tiempo_transcurrido > SESSION_TIMEOUT) {
            
            // Destruir sesion completamente
            session_unset();
            session_destroy();
            
            // Redirigir al login con mensaje
            header('Location: /sigmu/login?timeout=1');
            exit;
        }
    }
    
    // Actualizar tiempo de ultima actividad EN CADA CARGA DE PAGINA
    $_SESSION['ultima_actividad'] = time();
}
