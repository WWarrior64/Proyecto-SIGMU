<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\FallaService;
use App\Services\SigmuService;
use App\Support\Session;
use Throwable;

final class FallaController
{
    private readonly FallaService $fallaService;
    private readonly SigmuService $sigmuService;

    public function __construct()
    {
        $this->fallaService = new FallaService();
        $this->sigmuService = new SigmuService();
    }

    public function index(): string
    {
        if (!$this->requireAuth()) {
            return '';
        }

        $activoId = (int) ($_GET['activo_id'] ?? 0);
        if ($activoId <= 0) {
            header('Location: /sigmu/edificios');
            return '';
        }

        try {
            $activo = $this->fallaService->obtenerDatosActivo($activoId);
            $sessionUser = Session::get('auth_user');

            return view('reporte_falla.reporte_falla', [
                'sessionUser' => $sessionUser,
                'activo' => $activo
            ]);
        } catch (Throwable $e) {
            return "Error: " . $e->getMessage();
        }
    }

    public function guardar(): string
    {
        if (!$this->requireAuth()) {
            return json_encode(['success' => false, 'message' => 'No autorizado']);
        }

        try {
            $activoId = (int) ($_POST['activo_id'] ?? 0);
            $tipoFalla = $_POST['tipo_falla'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $sessionUser = Session::get('auth_user');

            $success = $this->fallaService->reportarFalla(
                $activoId, 
                (int)$sessionUser['id'], 
                $tipoFalla, 
                $descripcion
            );

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
