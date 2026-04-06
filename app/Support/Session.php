<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Gestión de Sesiones con Expiración Automática
 * 
 * Controla el ciclo de vida de las sesiones PHP,
 * incluyendo timeout por inactividad y regeneración de ID.
 */
final class Session
{
    private const DEFAULT_LIFETIME = 120; // 2 minutos en segundos
    private const LAST_ACTIVITY_KEY = '_last_activity';
    private const SESSION_TOKEN_KEY = '_session_token';
    
    /**
     * Inicia la sesión con configuración segura
     */
    public static function start(int $lifetime = self::DEFAULT_LIFETIME): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        
        // Configuración segura de cookies de sesión
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => '/',
            'domain' => '',
            'secure' => false, // Cambiar a true en producción con HTTPS
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        
        session_start();
        
        // Verificar si la sesión ha expirado por inactividad
        if (self::isExpired($lifetime)) {
            self::destroy();
            session_start();
        }
        
        // Actualizar timestamp de última actividad
        $_SESSION[self::LAST_ACTIVITY_KEY] = time();
        
        // Generar token de sesión si no existe (para prevenir fixation)
        if (!isset($_SESSION[self::SESSION_TOKEN_KEY])) {
            $_SESSION[self::SESSION_TOKEN_KEY] = bin2hex(random_bytes(32));
        }
    }
    
    /**
     * Verifica si la sesión ha expirado por inactividad
     */
    public static function isExpired(int $lifetime = self::DEFAULT_LIFETIME): bool
    {
        if (!isset($_SESSION[self::LAST_ACTIVITY_KEY])) {
            return false;
        }
        
        $elapsed = time() - $_SESSION[self::LAST_ACTIVITY_KEY];
        return $elapsed > $lifetime;
    }
    
    /**
     * Obtiene el tiempo restante de la sesión en segundos
     */
    public static function getTimeRemaining(int $lifetime = self::DEFAULT_LIFETIME): int
    {
        if (!isset($_SESSION[self::LAST_ACTIVITY_KEY])) {
            return $lifetime;
        }
        
        $elapsed = time() - $_SESSION[self::LAST_ACTIVITY_KEY];
        $remaining = $lifetime - $elapsed;
        
        return max(0, $remaining);
    }
    
    /**
     * Verifica si la sesión está activa y no ha expirado
     */
    public static function isActive(int $lifetime = self::DEFAULT_LIFETIME): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        
        return !self::isExpired($lifetime);
    }
    
    /**
     * Regenera el ID de sesión (prevenir session fixation)
     */
    public static function regenerate(bool $deleteOldSession = true): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id($deleteOldSession);
        }
    }
    
    /**
     * Establece un valor en la sesión
     */
    public static function set(string $key, mixed $value): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            self::start();
        }
        
        $_SESSION[$key] = $value;
    }
    
    /**
     * Obtiene un valor de la sesión
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return $default;
        }
        
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Verifica si existe una clave en la sesión
     */
    public static function has(string $key): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        
        return isset($_SESSION[$key]);
    }
    
    /**
     * Elimina una clave de la sesión
     */
    public static function remove(string $key): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION[$key]);
        }
    }
    
    /**
     * Obtiene el token de sesión actual
     */
    public static function getToken(): ?string
    {
        return self::get(self::SESSION_TOKEN_KEY);
    }
    
    /**
     * Valida el token de sesión (para peticiones AJAX)
     */
    public static function validateToken(?string $token): bool
    {
        $sessionToken = self::getToken();
        
        if ($sessionToken === null || $token === null) {
            return false;
        }
        
        return hash_equals($sessionToken, $token);
    }
    
    /**
     * Destruye completamente la sesión
     */
    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Limpiar variables de sesión
            $_SESSION = [];
            
            // Eliminar cookie de sesión
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
            
            // Destruir sesión
            session_destroy();
        }
    }
    
    /**
     * Limpia sesiones expiradas del servidor (para ejecutar periódicamente)
     */
    public static function cleanupExpired(int $maxLifetime = 3600): int
    {
        $cleaned = 0;
        $sessionPath = session_save_path();
        
        if ($sessionPath === '' || !is_dir($sessionPath)) {
            return 0;
        }
        
        $files = glob($sessionPath . '/sess_*');
        
        if ($files === false) {
            return 0;
        }
        
        foreach ($files as $file) {
            if (filemtime($file) + $maxLifetime < time()) {
                if (unlink($file)) {
                    $cleaned++;
                }
            }
        }
        
        return $cleaned;
    }
}