<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\SigmuRepository;
use RuntimeException;

final class SigmuService
{
    public function __construct(
        private readonly SigmuRepository $repository = new SigmuRepository()
    ) {
    }

    public function iniciarSesionBd(int $userId): void
    {
        $this->repository->setUsuarioSesion($userId);
    }

    public function cerrarSesionBd(): void
    {
        $this->repository->limpiarUsuarioSesion();
    }

    /**
     * @return array<string, mixed>
     */
    public function autenticar(string $login, string $password): array
    {
        $user = $this->repository->usuarioParaLogin($login);
        if (!$user) {
            throw new RuntimeException('Usuario o contraseña inválidos.');
        }

        if (!(bool) $user['activo']) {
            throw new RuntimeException('El usuario está inactivo.');
        }

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
            // Fallback local: permite contrasena en texto plano si aun no fue migrada.
            $isValidPassword = hash_equals($passwordHash, $password);
        }

        if (!$isValidPassword) {
            throw new RuntimeException('Usuario/email o contraseña inválidos.');
        }

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
        return $this->repository->misEdificios();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function obtenerMisSalas(int $edificioId): array
    {
        return $this->repository->misSalasPorEdificio($edificioId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function obtenerMisActivos(int $salaId): array
    {
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
     * @return array{success: bool, message: string, activo_id?: int}
     */
    public function registrarActivo(
        string $codigo,
        string $nombre,
        int $tipoActivoId,
        string $descripcion,
        string $estado,
        int $salaId,
        ?string $fotoPath = null
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
                $salaId
            );

            // If a photo was provided, add it
            if ($fotoPath !== null && $activoId > 0) {
                $this->repository->agregarFotoActivo($activoId, $fotoPath, 'Foto principal', true);
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
}
