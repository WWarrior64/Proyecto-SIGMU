<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Support\Session;

/**
 * Middleware de Control de Acceso por Rol
 * 
 * Intercepta cada solicitud y verifica si el rol del usuario
 * autenticado tiene permiso para acceder al recurso solicitado.
 * 
 * NOTA: Este middleware está listo para implementación futura.
 * Por ahora solo está el login y acceso básico a edificios.
 * Cuando el proyecto esté más completo, se integrará en el Router.
 * 
 * Roles del sistema:
 * - Administrador (rol_id = 1): Acceso total
 * - Responsable de Area (rol_id = 2): Solo sus edificios asignados
 * - Personal Mantenimiento (rol_id = 3): Solo mantenimientos
 */
final class AuthorizationMiddleware
{
    /**
     * Matriz de permisos por rol y recurso
     * 
     * Estructura:
     * - 'recurso' => ['rol1', 'rol2', ...]
     * 
     * Roles:
     * - 'Administrador' = Acceso total
     * - 'Responsable de Area' = Acceso limitado
     * - 'Personal Mantenimiento' = Solo mantenimientos
     */
    private const ROLE_PERMISSIONS = [
        // Rutas de activos
        'activos.registrar' => ['Administrador', 'Responsable de Area'],
        'activos.editar' => ['Administrador', 'Responsable de Area'],
        'activos.eliminar' => ['Administrador', 'Responsable de Area'],
        'activos.ver' => ['Administrador', 'Responsable de Area', 'Personal Mantenimiento'],
        
        // Rutas de fotos
        'fotos.agregar' => ['Administrador', 'Responsable de Area'],
        'fotos.eliminar' => ['Administrador', 'Responsable de Area'],
        'fotos.ver' => ['Administrador', 'Responsable de Area', 'Personal Mantenimiento'],
        
        // Rutas de mantenimientos
        'mantenimientos.registrar' => ['Administrador', 'Responsable de Area', 'Personal Mantenimiento'],
        'mantenimientos.completar' => ['Administrador', 'Responsable de Area', 'Personal Mantenimiento'],
        'mantenimientos.ver' => ['Administrador', 'Responsable de Area', 'Personal Mantenimiento'],
        
        // Rutas de edificios
        'edificios.registrar' => ['Administrador', 'Responsable de Area'],
        'edificios.editar' => ['Administrador', 'Responsable de Area'],
        'edificios.eliminar' => ['Administrador', 'Responsable de Area'],
        'edificios.ver' => ['Administrador', 'Responsable de Area', 'Personal Mantenimiento'],
        
        // Rutas de salas
        'salas.registrar' => ['Administrador', 'Responsable de Area'],
        'salas.editar' => ['Administrador', 'Responsable de Area'],
        'salas.eliminar' => ['Administrador', 'Responsable de Area'],
        'salas.ver' => ['Administrador', 'Responsable de Area', 'Personal Mantenimiento'],
        
        // Rutas de administración (solo Admin)
        'admin.usuarios' => ['Administrador'],
        'admin.tipos_activo' => ['Administrador'],
        'admin.asignaciones' => ['Administrador'],
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

    /**
     * Verifica si el usuario tiene permiso para acceder a la ruta
     * 
     * @param string $method Método HTTP (GET, POST, etc.)
     * @param string $uri URI de la solicitud
     * @return bool True si tiene permiso, false si no (y maneja la respuesta)
     */
    public static function handle(string $method, string $path): bool
    {
        // Verificar si es ruta pública
        if (self::isPublicRoute($path)) {
            return true;
        }
        
        // Verificar si el usuario está autenticado
        if (!self::isAuthenticated()) {
            self::denyAccess('Debes iniciar sesión para acceder.');
            return false;
        }
        
        // Obtener el rol del usuario
        $userRole = self::getUserRole();
        if ($userRole === null) {
            self::denyAccess('Error de sesión: Rol no encontrado.');
            return false;
        }
        
        // Verificar si la ruta está mapeada a un recurso
        $resource = self::getResourceForRoute($path);
        if ($resource === null) {
            return true;
        }
        
        // Verificar si el rol tiene permiso para el recurso
        if (!self::hasPermission($userRole, $resource)) {
            self::denyAccess();
            return false;
        }

        return true;
    }

    /**
     * Verifica si la ruta es pública
     */
    private static function isPublicRoute(string $path): bool
    {
        return in_array($path, self::PUBLIC_ROUTES, true);
    }

    /**
     * Verifica si el usuario está autenticado
     */
    private static function isAuthenticated(): bool
    {
        return Session::has('auth_user');
    }

    /**
     * Obtiene el rol del usuario autenticado
     * 
     * @return string|null Nombre del rol o null si no está autenticado
     */
    private static function getUserRole(): ?string
    {
        $user = Session::get('auth_user');
        
        if (!is_array($user) || !isset($user['rol_nombre'])) {
            return null;
        }
        
        return $user['rol_nombre'];
    }

    /**
     * Obtiene el recurso de permisos para una ruta
     * 
     * @param string $path Ruta a verificar
     * @return string|null Recurso de permisos o null si no está mapeada
     */
    private static function getResourceForRoute(string $path): ?string
    {
        // Buscar coincidencia exacta
        if (isset(self::ROUTE_RESOURCE_MAP[$path])) {
            return self::ROUTE_RESOURCE_MAP[$path];
        }
        
        // Buscar coincidencia por prefijo (para rutas con parámetros)
        foreach (self::ROUTE_RESOURCE_MAP as $route => $resource) {
            if (str_starts_with($path, $route)) {
                return $resource;
            }
        }
        
        return null;
    }

    /**
     * Verifica si un rol tiene permiso para un recurso
     * 
     * @param string $role Nombre del rol
     * @param string $resource Recurso de permisos
     * @return bool True si tiene permiso, false si no
     */
    private static function hasPermission(string $role, string $resource): bool
    {
        // El administrador tiene acceso total
        if ($role === 'Administrador') {
            return true;
        }
        
        // Verificar si el recurso existe en la matriz de permisos
        if (!isset(self::ROLE_PERMISSIONS[$resource])) {
            // Si el recurso no está definido, denegar acceso por seguridad
            return false;
        }
        
        // Verificar si el rol está en la lista de roles permitidos
        return in_array($role, self::ROLE_PERMISSIONS[$resource], true);
    }

    /**
     * Redirige al usuario a la página de acceso denegado
     * 
     * @param string $message Mensaje de error opcional
     */
    public static function denyAccess(string $message = ''): void
    {
        $errorMessage = $message ?: 'Acceso denegado: no tiene permisos para acceder a este recurso.';
        
        // Destruir la sesión si el usuario no está autenticado
        if (!self::isAuthenticated()) {
            Session::destroy();
            header('Location: /sigmu?error=' . urlencode('Debes iniciar sesión para acceder.'));
            exit;
        }
        
        // Redirigir al dashboard con mensaje de error
        header('Location: /sigmu?error=' . urlencode($errorMessage));
        exit;
    }

    /**
     * Obtiene el ID del usuario autenticado
     * 
     * @return int|null ID del usuario o null si no está autenticado
     */
    public static function getUserId(): ?int
    {
        $user = Session::get('auth_user');
        
        if (!is_array($user) || !isset($user['id'])) {
            return null;
        }
        
        return $user['id'];
    }

    /**
     * Verifica si el usuario es administrador
     * 
     * @return bool True si es administrador, false si no
     */
    public static function isAdmin(): bool
    {
        return self::getUserRole() === 'Administrador';
    }

    /**
     * Verifica si el usuario es responsable de área
     * 
     * @return bool True si es responsable de área, false si no
     */
    public static function isResponsableArea(): bool
    {
        return self::getUserRole() === 'Responsable de Area';
    }

    /**
     * Verifica si el usuario es personal de mantenimiento
     * 
     * @return bool True si es personal de mantenimiento, false si no
     */
    public static function isPersonalMantenimiento(): bool
    {
        return self::getUserRole() === 'Personal Mantenimiento';
    }

    /**
     * Verifica si el usuario tiene acceso total (Administrador)
     * 
     * @return bool True si tiene acceso total, false si no
     */
    public static function hasFullAccess(): bool
    {
        return self::isAdmin();
    }

    /**
     * Verifica si el usuario tiene acceso a un edificio específico
     * 
     * @param int $edificioId ID del edificio
     * @return bool True si tiene acceso, false si no
     */
    public static function hasAccessToEdificio(int $edificioId): bool
    {
        // El administrador tiene acceso a todos los edificios
        if (self::isAdmin()) {
            return true;
        }
        
        // Verificar en base de datos usando la función fn_tiene_acceso_edificio
        try {
            $db = \App\Support\Database::connection();
            $stmt = $db->prepare("SELECT fn_tiene_acceso_edificio(?, ?) AS tiene_acceso");
            $stmt->execute([self::getUserId(), $edificioId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result && (bool) $result['tiene_acceso'];
        } catch (\Throwable $e) {
            // Si la función no existe, denegar acceso por seguridad
            error_log("Error verificando acceso a edificio: " . $e->getMessage());
            return false;
        }
    }
}
