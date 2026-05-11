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

if (!function_exists('partial')) {
    /**
     * Incluye un partial desde resources/views/partials/{nombre}.php
     *
     * @param array<string, mixed> $data
     */
    function partial(string $name, array $data = []): void
    {
        $path = __DIR__ . '/../resources/views/partials/' . $name . '.php';
        if (!is_file($path)) {
            return;
        }
        extract($data, EXTR_SKIP);
        require $path;
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

        // ✅ AGREGAR AUTOMATICAMENTE DATOS DEL USUARIO A TODAS LAS VISTAS
        if (isset($_SESSION['auth_user'])) {
            $data['authUser'] = $_SESSION['auth_user'];
            
            // Cargar foto de perfil si existe y no está en sesión
            if (!isset($data['authUser']['foto'])) {
                try {
                    $db = \App\Support\Database::connection();
                    $stmt = $db->prepare("SELECT ruta_foto FROM usuario_foto WHERE usuario_id = ? ORDER BY id DESC LIMIT 1");
                    $stmt->execute([$_SESSION['auth_user']['id']]);
                    $foto = $stmt->fetchColumn();
                    if ($foto) {
                        $data['authUser']['foto'] = $foto;
                    }
                } catch (Throwable $e) {
                    // Silenciar error si no existe la tabla o no hay foto
                }
            }
        }

        // Pasamos $data a variables sueltas para usarlas en la vista.
        extract($data, EXTR_SKIP);
        ob_start();
        require $viewPath;
        $output = (string) ob_get_clean();

        // ✅ AUTO-RECARGA POR EXPIRACIÓN (JS)
        // Inyectamos el script que cerrará la sesión automáticamente al cumplirse el tiempo
        if (isset($_SESSION['auth_user']) && isset($_SESSION['ultima_actividad'])) {
            $timeoutMs = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT * 1000 : 900000;
            $elapsedMs = (time() - $_SESSION['ultima_actividad']) * 1000;
            $remainingMs = max(0, $timeoutMs - $elapsedMs);
            
            $userData = json_encode($data['authUser'] ?? []);
            
            $script = "
            <script>
                // Datos del usuario para el menú lateral
                window.authUser = {$userData};
                
                // Lógica de auto-recarga al expirar la sesión
                (function() {
                    const remaining = {$remainingMs};
                    console.log('Sesión expira en: ' + (remaining/1000) + 's');
                    
                    setTimeout(function() {
                        console.log('Sesión expirada. Recargando...');
                        window.location.reload();
                    }, remaining + 1000); // 1 segundo de margen
                })();
            </script>";
            
            $output .= $script;
        }

        return $output;
    }
}

/**
 * ✅ TIMEOUT DE SESION POR INACTIVIDAD (Nativo)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tiempo de inactividad permitido: 15 MINUTOS (900 segundos)
if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 900);
}

if (isset($_SESSION['auth_user'])) {
    if (isset($_SESSION['ultima_actividad'])) {
        $tiempo_transcurrido = time() - $_SESSION['ultima_actividad'];
        
        if ($tiempo_transcurrido > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();
            // Redirigir al dashboard que mostrará el login con el parámetro correcto
            header('Location: /sigmu?timeout=1');
            exit;
        }
    }
    // Actualizar marca de tiempo
    $_SESSION['ultima_actividad'] = time();
}
