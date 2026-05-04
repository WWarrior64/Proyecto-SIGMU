<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\SigmuRepository;
use RuntimeException;

// In the service we put simple business logic and validations.
// La idea es que el controlador solo reciba/mande datos, y aquí se decida qué hacer.
final class SigmuService
{
    private readonly SigmuRepository $repository;

    public function __construct(?SigmuRepository $repository = null)
    {
        $this->repository = $repository ?? new SigmuRepository();
    }

    public function iniciarSesionBd(int $userId): void
    {
        // Esto ejecuta el SP set_usuario_sesion y deja el usuario "activo" para las vistas restringidas.
        $this->repository->setUsuarioSesion($userId);
    }

    public function cerrarSesionBd(): void
    {
        // Limpia @usuario_id_sesion del lado de MySQL.
        $this->repository->limpiarUsuarioSesion();
    }

    /**
     * @return array<string, mixed>
     */
    public function autenticar(string $login, string $password): array
    {
        // Buscamos el usuario por username o email.
        $user = $this->repository->usuarioParaLogin($login);
        if (!$user) {
            throw new RuntimeException('Usuario o contraseña inválidos.');
        }

        // No dejamos entrar usuarios desactivados.
        if (!(bool) $user['activo']) {
            throw new RuntimeException('El usuario está inactivo.');
        }

        // Validación de contraseña (hash bcrypt).
        $passwordHash = (string) $user['contrasena_hash'];
        if (str_contains($passwordHash, 'REEMPLAZAR')) {
            throw new RuntimeException(
                'La cuenta no tiene contrasena configurada. Actualiza contrasena_hash en la tabla usuarios.'
            );
        }

        $hashInfo = password_get_info($passwordHash);
        $isValidPassword = false;

        if (($hashInfo['algo'] ?? null) !== null) {
            $isValidPassword = password_verify($password, $passwordHash);
        } else {
            // Fallback local: esto fue útil al inicio para pruebas, pero lo ideal es usar hash siempre.
            $isValidPassword = hash_equals($passwordHash, $password);
        }

        if (!$isValidPassword) {
            throw new RuntimeException('Usuario/email o contraseña inválidos.');
        }

        // Validación extra: debe existir rol relacionado.
        if (empty($user['rol_nombre'])) {
            throw new RuntimeException('El usuario no tiene rol válido.');
        }

        return $user;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function obtenerMisEdificios(): array
    {
        // Se apoya en vista_mis_edificios (filtrada por fn_tiene_acceso_edificio).
        return $this->repository->misEdificios();
    }

    /**
     * Edificios para flujos de localización (reporte de falla, etc.).
     * Personal Mantenimiento suele no tener filas en usuario_edificio: usar catálogo completo.
     *
     * @param array<string, mixed> $sessionUser
     * @return array<int, array<string, mixed>>
     */
    public function obtenerEdificiosParaUbicacion(array $sessionUser): array
    {
        if (($sessionUser['rol_nombre'] ?? '') === 'Personal Mantenimiento') {
            return $this->repository->catalogoEdificios();
        }

        return $this->repository->misEdificios();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function obtenerMisSalas(int $edificioId): array
    {
        // Se apoya en vista_mis_salas (filtrada por edificio accesible).
        return $this->repository->misSalasPorEdificio($edificioId);
    }

    /**
     * Salas para selección en formularios (respeta rol mantenimiento como en obtenerEdificiosParaUbicacion).
     *
     * @param array<string, mixed> $sessionUser
     * @return array<int, array<string, mixed>>
     */
    public function obtenerSalasParaUbicacion(int $edificioId, array $sessionUser): array
    {
        if (($sessionUser['rol_nombre'] ?? '') === 'Personal Mantenimiento') {
            return $this->repository->catalogoSalasPorEdificio($edificioId);
        }

        return $this->repository->misSalasPorEdificio($edificioId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function obtenerMisActivos(int $salaId): array
    {
        // Se apoya en vista_mis_activos (ya incluye foto principal si existe).
        return $this->repository->misActivosPorSala($salaId);
    }

    /**
     * Activos para selección en formularios (misma lógica que salas/edificios).
     *
     * @param array<string, mixed> $sessionUser
     * @return array<int, array<string, mixed>>
     */
    public function obtenerActivosParaUbicacion(int $salaId, array $sessionUser, ?int $edificioId = null): array
    {
        if (($sessionUser['rol_nombre'] ?? '') === 'Personal Mantenimiento') {
            return $this->repository->catalogoActivosPorSala($salaId, $edificioId);
        }

        return $this->repository->misActivosPorSala($salaId);
    }

    /**
     * Gets all available asset types
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTiposActivo(): array
    {
        return $this->repository->typesActive();
    }

    /**
     * Obtiene todas las salas accesibles para el usuario actual
     return array(
     */
    public function obtenerTodasLasSalas(): array
    {
        return $this->repository->todasLasSalas();
    }

    /**
     * Genera un código automático para un nuevo activo basado en su nombre
     */
    public function generarCodigoActivo(string $nombreActivo = ''): string
    {
        return $this->repository->generarCodigoActivo($nombreActivo);
    }

    /**
     * Registra un nuevo activo en el sistema
     * @param array<string> $fotoPaths
     * @return array{success: bool, message: string, activo_id?: int}
     */
    public function registrarActivo(
        string $codigo,
        string $nombre,
        int $tipoActivoId,
        string $descripcion,
        string $estado,
        int $salaId,
        array $fotoPaths = [],
        ?string $fechaCreado = null
    ): array {
        try {
            // Verificar que el código no exista
            if ($this->repository->existeCodigoActivo($codigo)) {
                return [
                    'success' => false,
                    'message' => 'Ya existe un activo con el código: ' . $codigo,
                ];
            }

            // Registrar el activo usando el procedimiento almacenado
            $activoId = $this->repository->registrarActivo(
                $codigo,
                $nombre,
                $tipoActivoId,
                $descripcion,
                $estado,
                $salaId,
                $fechaCreado
            );

            // If photos were provided, add them
            if (!empty($fotoPaths) && $activoId > 0) {
                foreach ($fotoPaths as $index => $path) {
                    if (empty($path) || !is_string($path)) {
                        continue;
                    }
                    // La primera foto es la principal
                    $esPrincipal = ($index === 0);
                    $this->repository->agregarFotoActivo($activoId, $path, 'Foto ' . ($index + 1), $esPrincipal);
                }
            }

            return [
                'success' => true,
                'message' => 'Activo registrado exitosamente.',
                'activo_id' => $activoId,
            ];
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'message' => 'Error al registrar el activo: ' . $exception->getMessage(),
            ];
        }
    }

    /**
     * Registrar multiples activos iguales con codigos automaticos diferentes
     * @param array<string> $fotoPaths
     */
    public function registrarMultiplesActivos(int $cantidad, string $nombre, int $tipoActivoId, string $descripcion, string $estado, int $salaId, array $fotoPaths = []): array
    {
        try {
            $creados = 0;
            $errores = 0;
            $ultimoError = '';

            for ($i = 0; $i < $cantidad; $i++) {
                // Generar codigo unico automatico para CADA activo
                $codigo = $this->generarCodigoActivo($nombre);

                $resultado = $this->registrarActivo($codigo, $nombre, $tipoActivoId, $descripcion, $estado, $salaId, $fotoPaths);

                if ($resultado['success']) {
                    $creados++;
                } else {
                    $errores++;
                    $ultimoError = $resultado['message'];
                }
            }

            return [
                'success' => $creados > 0,
                'creados' => $creados,
                'errores' => $errores,
                'message' => "Se registraron $creados activos correctamente" . ($errores > 0 ? ". Errores: $errores. Ultimo error: $ultimoError" : "")
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al registrar activos multiples: ' . $e->getMessage()
            ];
        }
    }

    public function obtenerFotosActivo(int $activoId): array
    {
        return $this->repository->obtenerFotosActivo($activoId);
    }

    public function eliminarFotoActivo(int $fotoId): bool
    {
        return $this->repository->eliminarFotoActivo($fotoId);
    }

    public function establecerPrincipalFotoActivo(int $fotoId): bool
    {
        return $this->repository->establecerPrincipalFotoActivo($fotoId);
    }

    public function agregarFotoEdificio(int $edificioId, string $rutaFoto, string $descripcion): int
    {
        return $this->repository->agregarFotoEdificio($edificioId, $rutaFoto, $descripcion);
    }

    public function obtenerFotoEdificio(int $edificioId): ?array
    {
        return $this->repository->obtenerFotoEdificio($edificioId);
    }

    /**
     * @return array{success: bool, message: string, debugToken?: string}
     */
    public function solicitarRecuperacionPassword(string $login, bool $debugLocal = false): array
    {
        // Aquí cuidamos no revelar si el usuario existe o no (mensaje genérico).
        $login = trim($login);
        if ($login === '') {
            return [
                'success' => false,
                'message' => 'Ingresa tu usuario o email.',
            ];
        }

        $user = $this->repository->usuarioIdPorLogin($login);
        if (!is_array($user) || empty($user['id']) || !(bool) $user['activo']) {
            // No revelamos si el usuario existe o está activo.
            return [
                'success' => true,
                'message' => 'Si la cuenta existe, recibirás instrucciones para crear una nueva contraseña.',
            ];
        }

        $usuarioId = (int) $user['id'];
        $expiresMinutes = 60;

        // Guardamos el token (hash) en BD. El token plano solo lo mostramos en local para debug.
        $token = $this->repository->crearTokenPasswordReset($usuarioId, $expiresMinutes);

        return [
            'success' => true,
            'message' => 'Si la cuenta existe, recibirás instrucciones para crear una nueva contraseña.',
            'debugToken' => $debugLocal ? $token : null,
        ];
    }

    public function tokenPasswordResetValido(string $tokenPlain): bool
    {
        $tokenPlain = trim($tokenPlain);
        if ($tokenPlain === '') {
            return false;
        }

        return $this->repository->tokenPasswordResetEsValido($tokenPlain);
    }

    public function resetearPassword(string $tokenPlain, string $password, string $passwordConfirmation): void
    {
        // Validaciones simples antes de tocar BD.
        if (trim($tokenPlain) === '') {
            throw new RuntimeException('Invalid token.');
        }

        if (strlen($password) < 8) {
            throw new RuntimeException('La contraseña debe tener al menos 8 caracteres.');
        }

        if ($password !== $passwordConfirmation) {
            throw new RuntimeException('La confirmación no coincide.');
        }

        // Validamos token (existente, no usado y no expirado).
        if (!$this->tokenPasswordResetValido($tokenPlain)) {
            throw new RuntimeException('El token ya no es válido o ha expirado.');
        }

        // Guardamos contraseña nueva hasheada.
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $ok = $this->repository->resetearContrasenaPorToken($tokenPlain, $hash);

        if (!$ok) {
            throw new RuntimeException('No se pudo completar el restablecimiento.');
        }
    }

    /**
     * Obtiene todos los usuarios del sistema (solo Administrador)
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodosUsuarios(): array
    {
        return $this->repository->obtenerTodosUsuarios();
    }

    public function registrarUsuario(string $username, string $email, string $password, string $nombreCompleto, int $rolId): int
    {
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        return $this->repository->registrarUsuario($username, $email, $passwordHash, $nombreCompleto, $rolId);
    }

    public function editarUsuario(int $usuarioId, string $email, string $nombreCompleto, int $rolId, bool $activo): bool
    {
        return $this->repository->editarUsuario($usuarioId, $email, $nombreCompleto, $rolId, $activo);
    }

    public function desactivarUsuario(int $usuarioId): bool
    {
        return $this->repository->cambiarEstadoUsuario($usuarioId, false);
    }

    public function activarUsuario(int $usuarioId): bool
    {
        return $this->repository->cambiarEstadoUsuario($usuarioId, true);
    }

    /**
     * Obtiene un usuario por su ID
     * @return array<string, mixed>|null
     */
    public function obtenerUsuarioPorId(int $usuarioId): ?array
    {
        return $this->repository->obtenerUsuarioPorId($usuarioId);
    }

    /**
     * Obtiene todos los roles del sistema
     * @return array<int, array<string, mixed>>
     */
    public function obtenerRoles(): array
    {
        return $this->repository->obtenerRoles();
    }

    /**
     * Cambia la contraseña de un usuario (solo administrador)
     */
    public function cambiarContrasena(int $usuarioId, string $nuevaContrasena): bool
    {
        $passwordHash = password_hash($nuevaContrasena, PASSWORD_BCRYPT);
        return $this->repository->cambiarContrasena($usuarioId, $passwordHash);
    }

    public function editarPerfil(int $usuarioId, string $email, string $nombreCompleto): bool
    {
        return $this->repository->editarPerfil($usuarioId, $email, $nombreCompleto);
    }

    public function obtenerFotoUsuario(int $usuarioId): ?array
    {
        return $this->repository->obtenerFotoUsuario($usuarioId);
    }

    public function agregarFotoUsuario(int $usuarioId, string $rutaFoto, string $descripcion): int
    {
        return $this->repository->agregarFotoUsuario($usuarioId, $rutaFoto, $descripcion);
    }

    public function agregarFotoActivo(int $activoId, string $rutaFoto, string $descripcion = '', bool $esPrincipal = true): int
    {
        return $this->repository->agregarFotoActivo($activoId, $rutaFoto, $descripcion, $esPrincipal);
    }
}
