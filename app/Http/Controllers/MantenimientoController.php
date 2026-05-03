<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\MantenimientoService;
use App\Services\SigmuService;
use App\Support\Session;
use Throwable;

final class MantenimientoController
{
    private readonly MantenimientoService $mantenimientoService;
    private readonly SigmuService $sigmuService;

    public function __construct()
    {
        $this->mantenimientoService = new MantenimientoService();
        $this->sigmuService = new SigmuService();
    }

    public function index(): string
    {
        if (!$this->requireAuth()) {
            return '';
        }

        try {
            $data = $this->mantenimientoService->obtenerDatosDashboard();
            $sessionUser = Session::get('auth_user');

            return view('mantenimiento.mantenimiento', [
                'sessionUser' => $sessionUser,
                'calendario' => $data['calendario'],
                'pendientes' => $data['pendientes'],
                'tecnicos' => $data['tecnicos'],
                'stats' => $data['stats'],
                'mes' => $data['mes'],
                'anio' => $data['anio']
            ]);
        } catch (Throwable $e) {
            return "Error: " . $e->getMessage();
        }
    }

    public function agendar(): string
    {
        if (!$this->requireAuth()) {
            return json_encode(['success' => false, 'message' => 'No autorizado']);
        }

        try {
            $mantenimientoId = (int) ($_POST['mantenimiento_id'] ?? 0);
            $tecnicoId = (int) ($_POST['tecnico_id'] ?? 0);
            $fecha = $_POST['fecha'] ?? '';
            $notas = $_POST['notas'] ?? '';

            if ($mantenimientoId <= 0 || $tecnicoId <= 0 || empty($fecha)) {
                return json_encode(['success' => false, 'message' => 'Datos incompletos']);
            }

            $success = $this->mantenimientoService->agendarReparacion($mantenimientoId, $tecnicoId, $fecha, $notas);

            return json_encode(['success' => $success]);
        } catch (Throwable $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function listado(): string
    {
        if (!$this->requireAuth()) {
            return '';
        }

        try {
            $mantenimientos = $this->mantenimientoService->obtenerListadoCompleto();
            $sessionUser = Session::get('auth_user');

            return view('mantenimiento.listado_mantenimientos', [
                'sessionUser' => $sessionUser,
                'mantenimientos' => $mantenimientos
            ]);
        } catch (Throwable $e) {
            return "Error: " . $e->getMessage();
        }
    }

    public function completar(): string
    {
        if (!$this->requireAuth()) {
            return json_encode(['success' => false, 'message' => 'No autorizado']);
        }

        try {
            $id = (int) ($_POST['mantenimiento_id'] ?? 0);
            $notas = $_POST['notas'] ?? '';
            $fechaReal = $_POST['fecha_real'] ?? date('Y-m-d');
            $resultado = $_POST['resultado'] ?? 'resuelto';
            $observaciones = $_POST['observaciones'] ?? '';

            if ($id <= 0) {
                return json_encode(['success' => false, 'message' => 'ID inválido']);
            }

            $success = $this->mantenimientoService->finalizarMantenimiento($id, $notas, $fechaReal, $resultado, $observaciones);

            return json_encode(['success' => $success]);
        } catch (Throwable $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function requireAuth(): bool
    {
        $user = Session::get('auth_user');
        if (!$user) {
            header('Location: /sigmu?error=debes_iniciar_sesion');
            return false;
        }

        $this->sigmuService->iniciarSesionBd((int) $user['id']);
        return true;
    }
}
