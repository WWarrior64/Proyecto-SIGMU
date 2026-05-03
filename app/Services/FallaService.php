<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\FallaRepository;
use RuntimeException;

final class FallaService
{
    private readonly FallaRepository $repository;

    public function __construct(?FallaRepository $repository = null)
    {
        $this->repository = $repository ?? new FallaRepository();
    }

    public function obtenerDatosActivo(int $activoId): array
    {
        $activo = $this->repository->obtenerDatosActivo($activoId);
        if (!$activo) {
            throw new RuntimeException("Activo no encontrado.");
        }
        return $activo;
    }

    public function reportarFalla(int $activoId, int $usuarioId, string $tipoFalla, string $descripcion): bool
    {
        if (empty($tipoFalla) || empty($descripcion)) {
            throw new RuntimeException("Todos los campos son obligatorios.");
        }

        return $this->repository->registrarFalla($activoId, $usuarioId, $tipoFalla, $descripcion);
    }
}
