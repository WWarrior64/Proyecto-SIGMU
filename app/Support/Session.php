<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Gestión de Sesiones
 * 
 * Clase de utilidad para interactuar con la sesión de PHP
 * de forma limpia y orientada a objetos.
 */
final class Session
{
    /**
     * Inicia la sesión si no está iniciada
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Establece un valor en la sesión
     */
    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Obtiene un valor de la sesión
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Verifica si existe una clave en la sesión
     */
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }
    
    /**
     * Elimina una clave de la sesión
     */
    public static function remove(string $key): void
    {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    /**
     * Destruye completamente la sesión
     */
    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httonly"]
                );
            }
            session_destroy();
        }
    }
}
