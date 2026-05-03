<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\MantenimientoRepository;

final class MantenimientoService
{
    private readonly MantenimientoRepository $repository;

    public function __construct(?MantenimientoRepository $repository = null)
    {
        $this->repository = $repository ?? new MantenimientoRepository();
    }

    public function obtenerDatosDashboard(): array
    {
        $mesActual = (int) date('m');
        $anioActual = (int) date('Y');

        return [
            'calendario' => $this->repository->obtenerMantenimientosCalendario($mesActual, $anioActual),
            'pendientes' => $this->repository->obtenerMantenimientosPendientes(),
            'tecnicos' => $this->repository->obtenerTecnicosDisponibles(),
            'stats' => $this->repository->obtenerEstadisticas(),
            'mes' => $mesActual,
            'anio' => $anioActual
        ];
    }

    public function agendarReparacion(int $mantenimientoId, int $tecnicoId, string $fecha, string $notas): bool
    {
        return $this->repository->agendarMantenimiento($mantenimientoId, $tecnicoId, $fecha, $notas);
    }
}
