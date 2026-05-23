<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\EspacioRepository;
use App\Support\Cache;
use RuntimeException;

final class EspacioService
{
    private EspacioRepository $repository;

    public function __construct(?EspacioRepository $repository = null)
    {
        $this->repository = $repository ?? new EspacioRepository();
    }

    public function listarEdificios(): array
    {
        return Cache::remember('edificios.todos', function () {
            return $this->repository->obtenerEdificiosConConteo();
        }, 300); // 5 minutos de caché
    }

    public function listarSalas(int $edificioId): array
    {
        return Cache::remember("salas.edificio.{$edificioId}", function () use ($edificioId) {
            return $this->repository->obtenerSalasConConteo($edificioId);
        }, 300); // 5 minutos de caché
    }

    public function obtenerEdificio(int $id): array
    {
        $edificio = $this->repository->obtenerEdificioPorId($id);
        if (!$edificio) {
            throw new RuntimeException("Edificio no encontrado");
        }
        return $edificio;
    }

    public function obtenerSala(int $id): array
    {
        $sala = $this->repository->obtenerSalaPorId($id);
        if (!$sala) {
            throw new RuntimeException("Sala no encontrada");
        }
        return $sala;
    }

    public function guardarEdificio(array $data): int
    {
        // Invalidar caché de edificios al modificar
        Cache::forget('edificios.todos');
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        $nombre = trim($data['nombre'] ?? '');
        $descripcion = $data['descripcion'] ?? '';
        $pisos = (int)($data['cantidad_pisos'] ?? 1);

        if (empty($nombre)) {
            throw new RuntimeException("El nombre es obligatorio");
        }

        // Validar que no exista otro edificio con el mismo nombre
        if ($this->repository->existeEdificioConNombre($nombre, $id > 0 ? $id : null)) {
            throw new RuntimeException("Ya existe un edificio con el nombre '$nombre'");
        }

        if ($id > 0) {
            $this->repository->actualizarEdificio($id, $nombre, $descripcion, $pisos);
            return $id;
        } else {
            return $this->repository->crearEdificio($nombre, $descripcion, $pisos);
        }
    }

    public function guardarSala(array $data): int
    {
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        $edificioId = (int)($data['edificio_id'] ?? 0);
        // Invalidar caché de salas al modificar
        Cache::forget("salas.edificio.{$edificioId}");
        $nombre = trim($data['nombre'] ?? '');
        $descripcion = $data['descripcion'] ?? '';
        $piso = (int)($data['numero_piso'] ?? 1);

        if (empty($nombre)) {
            throw new RuntimeException("El nombre es obligatorio");
        }

        if ($edificioId <= 0) {
            throw new RuntimeException("Edificio no válido");
        }

        // Validar que no exista otra sala con el mismo nombre en el mismo edificio
        if ($this->repository->existeSalaConNombreEnEdificio($nombre, $edificioId, $id > 0 ? $id : null)) {
            throw new RuntimeException("Ya existe una sala con el nombre '$nombre' en este edificio");
        }

        if ($id > 0) {
            $this->repository->actualizarSala($id, $nombre, $descripcion, $piso);
            return $id;
        } else {
            return $this->repository->crearSala($edificioId, $nombre, $descripcion, $piso);
        }
    }

    public function verificarNombreEdificio(string $nombre, ?int $excluirId = null): bool
    {
        return $this->repository->existeEdificioConNombre($nombre, $excluirId);
    }

    public function verificarNombreSala(string $nombre, int $edificioId, ?int $excluirId = null): bool
    {
        return $this->repository->existeSalaConNombreEnEdificio($nombre, $edificioId, $excluirId);
    }

    public function eliminarEdificio(int $id, int $userId, string $password): array
    {
        $this->verificarPassword($userId, $password);
        
        // Antes de borrar de la BD, obtenemos la foto para borrar el archivo físico
        $sigmuService = new SigmuService();
        $foto = $sigmuService->obtenerFotoEdificio($id);
        
        if ($foto && !empty($foto['ruta_foto'])) {
            $rutaCompleta = __DIR__ . '/../../public/' . ltrim($foto['ruta_foto'], '/');
            if (file_exists($rutaCompleta)) {
                unlink($rutaCompleta);
            }
        }

        return $this->repository->eliminarEdificio($id);
    }

    public function eliminarSala(int $id, int $userId, string $password): array
    {
        $this->verificarPassword($userId, $password);
        return $this->repository->eliminarSala($id);
    }

    private function verificarPassword(int $userId, string $password): void
    {
        $sigmuService = new SigmuService();
        $user = $sigmuService->obtenerUsuarioPorId($userId);
        
        if (!$user || !password_verify($password, $user['contrasena_hash'])) {
            throw new RuntimeException("Contraseña incorrecta. No se puede realizar la eliminación.");
        }
    }

    private function procesarFoto(array $file, string $carpeta): string
    {
        $uploadDir = __DIR__ . '/../../public/uploads/' . $carpeta . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = uniqid($carpeta . '_', true) . '.' . $extension;
        
        if (move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
            return 'uploads/' . $carpeta . '/' . $fileName;
        }
        throw new RuntimeException('Error al subir archivo');
    }
}
