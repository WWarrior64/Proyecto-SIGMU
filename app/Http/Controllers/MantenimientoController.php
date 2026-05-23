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

        $sessionUser = Session::get('auth_user');

        // Redirección si es técnico
        if (\App\Support\Roles::is($sessionUser['rol_id'], \App\Support\Roles::MANTENIMIENTO)) {
            return $this->dashboardTecnico();
        }

        try {
            $mes = (int) ($_GET['mes'] ?? 0);
            $anio = (int) ($_GET['anio'] ?? 0);
            $data = $this->mantenimientoService->obtenerDatosDashboard($mes, $anio);

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

    public function dashboardTecnico(): string
    {
        if (!$this->requireAuth()) {
            return '';
        }

        try {
            $sessionUser = Session::get('auth_user');
            $mes = (int) ($_GET['mes'] ?? 0);
            $anio = (int) ($_GET['anio'] ?? 0);
            $data = $this->mantenimientoService->obtenerDatosDashboardTecnico((int) $sessionUser['id'], $mes, $anio);

            return view('mantenimiento.dashboard_tecnico', [
                'sessionUser' => $sessionUser,
                'asignados' => $data['asignados'],
                'calendario' => $data['calendario'],
                'mes' => $data['mes'],
                'anio' => $data['anio']
            ]);
        } catch (Throwable $e) {
            return "Error: " . $e->getMessage();
        }
    }

    public function reportarFallaForm(): string
    {
        if (!$this->requireAuth()) {
            return '';
        }

        $sessionUser = Session::get('auth_user');
        $edificios = $this->sigmuService->obtenerEdificiosParaUbicacion($sessionUser);

        return view('mantenimiento.reportar_falla', [
            'sessionUser' => $sessionUser,
            'edificios' => $edificios
        ]);
    }

    public function registrarFalla(): string
    {
        if (!$this->requireAuth()) {
            return json_encode(['success' => false, 'message' => 'No autorizado']);
        }

        try {
            $activoId = (int) ($_POST['activo_id'] ?? 0);
            $tipoFalla = $_POST['tipo_falla'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $fecha = $_POST['fecha_deteccion'] ?? date('Y-m-d');
            $usuarioId = (int) Session::get('auth_user')['id'];

            if ($activoId <= 0 || empty($tipoFalla) || empty($descripcion)) {
                return json_encode(['success' => false, 'message' => 'Datos incompletos']);
            }

            $mantenimientoId = $this->mantenimientoService->registrarFalla($activoId, $usuarioId, $tipoFalla, $descripcion, $fecha);

            return json_encode(['success' => true, 'mantenimiento_id' => $mantenimientoId]);
        } catch (Throwable $e) {
            return json_encode(['success' => false, 'message' => 'No se pudo registrar la falla: ' . $e->getMessage()]);
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

            // Validar que la fecha no sea pasada
            if (strtotime($fecha) < strtotime(date('Y-m-d'))) {
                return json_encode(['success' => false, 'message' => 'La fecha seleccionada no puede ser pasada']);
            }

            $success = $this->mantenimientoService->agendarReparacion($mantenimientoId, $tecnicoId, $fecha, $notas);

            return json_encode(['success' => $success]);
        } catch (Throwable $e) {
            return json_encode(['success' => false, 'message' => 'Error al agendar: ' . $e->getMessage()]);
        }
    }

    public function listado(): string
    {
        if (!$this->requireAuth()) {
            return '';
        }

        try {
            $sessionUser = Session::get('auth_user');
            $mantenimientos = $this->mantenimientoService->obtenerListadoParaUsuario($sessionUser);

            return view('mantenimiento.listado_mantenimientos', [
                'sessionUser' => $sessionUser,
                'mantenimientos' => $mantenimientos
            ]);
        } catch (Throwable $e) {
            return "Ocurrió un error al cargar el listado: " . $e->getMessage();
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
                return json_encode(['success' => false, 'message' => 'Identificador no válido']);
            }

            $success = $this->mantenimientoService->finalizarMantenimiento($id, $notas, $fechaReal, $resultado, $observaciones);

            return json_encode(['success' => $success]);
        } catch (Throwable $e) {
            return json_encode(['success' => false, 'message' => 'Error al finalizar mantenimiento: ' . $e->getMessage()]);
        }
    }

    /**
     * Muestra el detalle de un activo para el personal de mantenimiento
     */
    public function verActivo(int $id): string
    {
        if (!$this->requireAuth()) {
            return '';
        }

        $sessionUser = Session::get('auth_user');
        
        // El personal de mantenimiento puede ver si tiene un mantenimiento asignado
        // O si es administrador.
        $esAdmin = \App\Support\Roles::is($sessionUser['rol_id'], \App\Support\Roles::ADMIN);
        $esMantenimiento = \App\Support\Roles::is($sessionUser['rol_id'], \App\Support\Roles::MANTENIMIENTO);

        if (!$esAdmin && !$esMantenimiento) {
            header('Location: /sigmu?error=acceso_denegado');
            return '';
        }

        $activo = (new \App\Models\Activo())->obtenerPorId($id);
        
        if (!$activo) {
            header('Location: /sigmu/mantenimiento?error=activo_no_encontrado');
            return '';
        }

        // Obtener todas las fotos
        $fotos = $this->sigmuService->obtenerFotosActivo($id);
        $activo['fotos'] = $fotos;
        $activo['imagen'] = !empty($fotos) ? $fotos[0]['ruta_foto'] : null;

        return view('mantenimiento.ver_activo', [
            'activo' => $activo,
            'sessionUser' => $sessionUser
        ]);
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