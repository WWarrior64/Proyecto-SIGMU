<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Support\Session;
use App\Support\Roles;

/**
 * Middleware de Control de Acceso por Rol
 */
final class AuthorizationMiddleware
{
    /**
     * Matriz de permisos por rol y recurso
     */
    private const ROLE_PERMISSIONS = [
        // Rutas de activos
        'activos.registrar' => [Roles::ADMIN, Roles::RESPONSABLE_AREA],
        'activos.editar' => [Roles::ADMIN, Roles::RESPONSABLE_AREA],
        'activos.eliminar' => [Roles::ADMIN, Roles::RESPONSABLE_AREA],
        'activos.ver' => [Roles::ADMIN, Roles::RESPONSABLE_AREA, Roles::MANTENIMIENTO],
        
        // Rutas de fotos
        'fotos.agregar' => [Roles::ADMIN, Roles::RESPONSABLE_AREA],
        'fotos.eliminar' => [Roles::ADMIN, Roles::RESPONSABLE_AREA],
        'fotos.ver' => [Roles::ADMIN, Roles::RESPONSABLE_AREA, Roles::MANTENIMIENTO],
        
        // Rutas de mantenimientos
        'mantenimientos.registrar' => [Roles::ADMIN, Roles::RESPONSABLE_AREA, Roles::MANTENIMIENTO],
        'mantenimientos.completar' => [Roles::ADMIN, Roles::RESPONSABLE_AREA, Roles::MANTENIMIENTO],
        'mantenimientos.ver' => [Roles::ADMIN, Roles::RESPONSABLE_AREA, Roles::MANTENIMIENTO],
        
        // Rutas de edificios
        'edificios.registrar' => [Roles::ADMIN, Roles::RESPONSABLE_AREA],
        'edificios.editar' => [Roles::ADMIN, Roles::RESPONSABLE_AREA],
        'edificios.eliminar' => [Roles::ADMIN, Roles::RESPONSABLE_AREA],
        'edificios.ver' => [Roles::ADMIN, Roles::RESPONSABLE_AREA, Roles::MANTENIMIENTO],
        
        // Rutas de salas
        'salas.registrar' => [Roles::ADMIN, Roles::RESPONSABLE_AREA],
        'salas.editar' => [Roles::ADMIN, Roles::RESPONSABLE_AREA],
        'salas.eliminar' => [Roles::ADMIN, Roles::RESPONSABLE_AREA],
        'salas.ver' => [Roles::ADMIN, Roles::RESPONSABLE_AREA, Roles::MANTENIMIENTO],
        
        // Rutas de administración (solo Admin)
        'admin.usuarios' => [Roles::ADMIN],
        'admin.tipos_activo' => [Roles::ADMIN],
        'admin.asignaciones' => [Roles::ADMIN],
        'admin.roles' => [Roles::ADMIN],
    ];

    /**
     * Mapeo de rutas a recursos de permisos
     */
    private const ROUTE_RESOURCE_MAP = [
        // Rutas de activos
        '/sigmu/activo/registrar' => 'activos.registrar',
        '/sigmu/activo/editar' => 'activos.editar',
        '/sigmu/activo/eliminar' => 'activos.eliminar',
        '/sigmu/activo/ver' => 'activos.ver',
        '/sigmu/activo/actualizar' => 'activos.editar',
        '/sigmu/activo/dar-baja' => 'activos.eliminar',
        
        // Rutas de mantenimientos
        '/sigmu/mantenimiento' => 'mantenimientos.ver',
        '/sigmu/mantenimiento/agendar' => 'mantenimientos.registrar',
        '/sigmu/mantenimiento/completar' => 'mantenimientos.completar',
        '/sigmu/mantenimiento/reportar' => 'mantenimientos.registrar',
        '/sigmu/reporte-falla' => 'mantenimientos.registrar',
        
        // Rutas de administración
        '/sigmu/admin/usuarios' => 'admin.usuarios',
        '/sigmu/administracion_usuarios/asignacion_espacios' => 'admin.asignaciones',
        '/sigmu/administracion_usuarios/guardar_usuario' => 'admin.usuarios',
        '/sigmu/administracion_usuarios/gestion_roles' => 'admin.roles',
    ];

    /**
     * Rutas públicas que no requieren autenticación
     */
    private const PUBLIC_ROUTES = [
        '/',
        '/sigmu',
        '/sigmu/login',
        '/sigmu/logout',
        '/sigmu/recuperar',
        '/sigmu/reset',
    ];

    public static function handle(string $method, string $path): bool
    {
        if (self::isPublicRoute($path)) return true;
        if (!self::isAuthenticated()) {
            self::denyAccess('Debes iniciar sesión para acceder.');
            return false;
        }
        
        $roleId = self::getUserRoleId();
        if ($roleId === null) {
            self::denyAccess('Error de sesión: Rol no encontrado.');
            return false;
        }
        
        $resource = self::getResourceForRoute($path);
        if ($resource === null) return true;
        
        if (!self::hasPermission($roleId, $resource)) {
            self::denyAccess();
            return false;
        }

        return true;
    }

    private static function isPublicRoute(string $path): bool
    {
        return in_array($path, self::PUBLIC_ROUTES, true);
    }

    private static function isAuthenticated(): bool
    {
        return Session::has('auth_user');
    }

    private static function getUserRoleId(): ?int
    {
        $user = Session::get('auth_user');
        return is_array($user) ? (int)($user['rol_id'] ?? 0) : null;
    }

    private static function getResourceForRoute(string $path): ?string
    {
        if (isset(self::ROUTE_RESOURCE_MAP[$path])) return self::ROUTE_RESOURCE_MAP[$path];
        foreach (self::ROUTE_RESOURCE_MAP as $route => $resource) {
            if (str_starts_with($path, $route)) return $resource;
        }
        return null;
    }

    private static function hasPermission(int $roleId, string $resource): bool
    {
        if (Roles::is($roleId, Roles::ADMIN)) return true;
        if (!isset(self::ROLE_PERMISSIONS[$resource])) return false;
        return Roles::in($roleId, self::ROLE_PERMISSIONS[$resource]);
    }

    public static function denyAccess(string $message = ''): void
    {
        $errorMessage = $message ?: 'Acceso denegado: no tiene permisos para acceder a este recurso.';
        if (!self::isAuthenticated()) {
            Session::destroy();
            header('Location: /sigmu?error=' . urlencode('Debes iniciar sesión para acceder.'));
            exit;
        }
        header('Location: /sigmu?error=' . urlencode($errorMessage));
        exit;
    }

    public static function getUserId(): ?int
    {
        $user = Session::get('auth_user');
        return is_array($user) ? (int)($user['id'] ?? 0) : null;
    }

    public static function isAdmin(): bool
    {
        return Roles::is(self::getUserRoleId(), Roles::ADMIN);
    }

    public static function isResponsableArea(): bool
    {
        return Roles::is(self::getUserRoleId(), Roles::RESPONSABLE_AREA);
    }

    public static function isPersonalMantenimiento(): bool
    {
        return Roles::is(self::getUserRoleId(), Roles::MANTENIMIENTO);
    }

    public static function hasAccessToEdificio(int $edificioId): bool
    {
        if (self::isAdmin()) return true;
        try {
            $db = \App\Support\Database::connection();
            $stmt = $db->prepare("SELECT fn_tiene_acceso_edificio(?, ?) AS tiene_acceso");
            $stmt->execute([self::getUserId(), $edificioId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result && (bool) $result['tiene_acceso'];
        } catch (\Throwable $e) {
            return false;
        }
    }
}
