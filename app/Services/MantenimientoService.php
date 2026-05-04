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
            'stats' => $this->obtenerEstadisticas(),
            'mes' => $mesActual,
            'anio' => $anioActual
        ];
    }

    public function obtenerDatosDashboardTecnico(int $tecnicoId): array
    {
        $mesActual = (int) date('m');
        $anioActual = (int) date('Y');

        return [
            'asignados' => $this->repository->obtenerMantenimientosPorTecnico($tecnicoId),
            'calendario' => $this->repository->obtenerCalendarioPorTecnico($tecnicoId, $mesActual, $anioActual),
            'mes' => $mesActual,
            'anio' => $anioActual
        ];
    }

    public function registrarFalla(int $activoId, int $usuarioId, string $tipoFalla, string $descripcion, string $fecha): int
    {
        $descripcionCompleta = "[$tipoFalla] $descripcion";
        return $this->repository->registrarFalla($activoId, $usuarioId, $descripcionCompleta, $fecha);
    }

    public function obtenerEstadisticas(): array
    {
        $pendientes = $this->repository->obtenerMantenimientosPendientes();
        $tecnicos = $this->repository->obtenerTecnicosDisponibles();
        
        return [
            'programados' => count($this->repository->obtenerMantenimientosCalendario((int)date('m'), (int)date('Y'))),
            'tecnicos' => count($tecnicos),
            'total_pendientes' => count($pendientes)
        ];
    }

    public function agendarReparacion(int $mantenimientoId, int $tecnicoId, string $fecha, string $notas): bool
    {
        // 1. Persistir en base de datos
        $success = $this->repository->agendarMantenimiento($mantenimientoId, $tecnicoId, $fecha, $notas);
        
        if ($success) {
            // 2. Obtener datos para el correo
            $mantenimiento = $this->repository->obtenerMantenimientoPorId($mantenimientoId);
            
            if ($mantenimiento && !empty($mantenimiento['email_tecnico'])) {
                $mailService = new MailService();
                $mailService->enviarNotificacionMantenimiento([
                    'email_tecnico' => $mantenimiento['email_tecnico'],
                    'activo_codigo' => $mantenimiento['activo_codigo'],
                    'activo_nombre' => $mantenimiento['activo_nombre'],
                    'fecha_agendada' => $fecha,
                    'descripcion_problema' => $mantenimiento['descripcion_problema'],
                    'notas' => $notas
                ]);
            }
        }

        return $success;
    }

    public function obtenerListadoCompleto(): array
    {
        return $this->repository->obtenerListadoMantenimientos();
    }

    /**
     * @param array<string, mixed> $sessionUser
     * @return array<int, array<string, mixed>>
     */
    public function obtenerListadoParaUsuario(array $sessionUser): array
    {
        if (($sessionUser['rol_nombre'] ?? '') === 'Personal Mantenimiento') {
            return $this->repository->obtenerListadoMantenimientosPorTecnico((int) ($sessionUser['id'] ?? 0));
        }

        return $this->repository->obtenerListadoMantenimientos();
    }

    public function finalizarMantenimiento(int $id, string $notas = '', string $fechaReal = '', string $resultado = 'resuelto', string $observaciones = ''): bool
    {
        return $this->repository->completarMantenimiento($id, $notas, $fechaReal, $resultado, $observaciones);
    }
}
