<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Protección CSRF (Cross-Site Request Forgery)
 * 
 * Genera y valida tokens CSRF para proteger formularios
 * contra ataques de falsificación de solicitudes entre sitios.
 */
final class Csrf
{
    private const TOKEN_NAME = '_csrf_token';
    private const TOKEN_LENGTH = 32;
    
    /**
     * Genera un token CSRF y lo almacena en la sesión
     */
    public static function generateToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        $_SESSION[self::TOKEN_NAME] = $token;
        
        return $token;
    }
    
    /**
     * Obtiene el token CSRF actual (genera uno nuevo si no existe)
     */
    public static function getToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION[self::TOKEN_NAME])) {
            return self::generateToken();
        }
        
        return $_SESSION[self::TOKEN_NAME];
    }
    
    /**
     * Genera el campo HTML oculto para el token CSRF
     */
    public static function field(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * Valida el token CSRF enviado en la petición
     */
    public static function validate(?string $token = null): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if ($token === null) {
            $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        }
        
        if ($token === null || !isset($_SESSION[self::TOKEN_NAME])) {
            return false;
        }
        
        return hash_equals($_SESSION[self::TOKEN_NAME], $token);
    }
    
    /**
     * Regenera el token CSRF (útil después de login exitoso)
     */
    public static function regenerate(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Eliminar token anterior
        unset($_SESSION[self::TOKEN_NAME]);
        
        // Generar nuevo token
        return self::generateToken();
    }
    
    /**
     * Verifica y rechaza la petición si el token CSRF es inválido
     * 
     * @throws \RuntimeException si el token es inválido
     */
    public static function verify(): void
    {
        if (!self::validate()) {
            http_response_code(403);
            throw new \RuntimeException('CSRF token invalid or missing. Please reload the page and try again.');
        }
    }
}