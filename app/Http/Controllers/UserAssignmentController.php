<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SigmuService;
use App\Services\EspacioService;
use App\Support\Session;
use Throwable;

final class UserAssignmentController
{
    private SigmuService $sigmuService;
    private EspacioService $espacioService;

    public function __construct()
    {
        $this->sigmuService = new SigmuService();
        $this->espacioService = new EspacioService();
    }

    private function requireAdmin(): array
    {
        if (!Session::has('auth_user')) {
            header('Location: /sigmu?error=debes_iniciar_sesion');
            exit;
        }

        $user = Session::get('auth_user');
        if (($user['rol_nombre'] ?? '') !== 'Administrador') {
            header('Location: /sigmu?error=acceso_denegado');
            exit;
        }

        $this->sigmuService->iniciarSesionBd((int)$user['id']);
        return $user;
    }

    /**
     * Vista principal de administración de espacios por usuario
     */
    public function index(): string
    {
        $sessionUser = $this->requireAdmin();

        try {
            $todosUsuarios = $this->sigmuService->obtenerTodosUsuarios();
            
            // FILTRAR: Solo permitir asignar a Responsables de Área
            // (Excluir Personal Mantenimiento y Administradores según requerimiento)
            $usuarios = array_filter($todosUsuarios, function($u) {
                $rol = $u['rol_nombre'] ?? '';
                return $rol !== 'Personal Mantenimiento' && $rol !== 'Administrador';
            });

            $edificiosDisponibles = $this->sigmuService->obtenerEdificiosNoAsignados();
            
            // Obtener todas las asignaciones
            $asignacionesRaw = $this->sigmuService->obtenerTodasAsignaciones();
            
            // Agrupar asignaciones por usuario_id
            $asignaciones = [];
            foreach ($asignacionesRaw as $asig) {
                $asignaciones[(int)$asig['usuario_id']][] = $asig;
            }

            return view('administracion_usuarios.asignacion_espacios', [
                'sessionUser' => $sessionUser,
                'usuarios' => $usuarios,
                'edificios' => $edificiosDisponibles,
                'asignaciones' => $asignaciones
            ]);
        } catch (Throwable $e) {
            return view('administracion_usuarios.asignacion_espacios', [
                'sessionUser' => $sessionUser,
                'error' => $e->getMessage(),
                'usuarios' => [],
                'edificios' => [],
                'asignaciones' => []
            ]);
        }
    }

    /**
     * Obtener edificios disponibles (AJAX)
     */
    public function edificiosDisponibles(): string
    {
        $this->requireAdmin();
        try {
            $edificios = $this->sigmuService->obtenerEdificiosNoAsignados();
            return json_encode($edificios);
        } catch (Throwable $e) {
            http_response_code(500);
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Asignar un edificio a un usuario
     */
    public function asignar(): string
    {
        $this->requireAdmin();

        try {
            $usuarioId = (int)($_POST['usuario_id'] ?? 0);
            $edificioId = (int)($_POST['edificio_id'] ?? 0);

            if ($usuarioId <= 0 || $edificioId <= 0) {
                throw new \RuntimeException("Datos inválidos");
            }

            // VALIDACIÓN EXTRA: No permitir asignar a Personal Mantenimiento o Administradores
            $usuario = $this->sigmuService->obtenerUsuarioPorId($usuarioId);
            if ($usuario) {
                $rol = $usuario['rol_nombre'] ?? '';
                if ($rol === 'Personal Mantenimiento') {
                    throw new \RuntimeException("No se pueden asignar espacios al personal de mantenimiento.");
                }
                if ($rol === 'Administrador') {
                    throw new \RuntimeException("El administrador ya tiene acceso global, no necesita asignaciones.");
                }
            }

            $exito = $this->sigmuService->asignarEdificio($usuarioId, $edificioId);

            return json_encode([
                'success' => $exito,
                'message' => $exito ? 'Espacio asignado correctamente' : 'El espacio ya estaba asignado o hubo un error'
            ]);
        } catch (Throwable $e) {
            http_response_code(400);
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Quitar asignación de un edificio
     */
    public function quitar(): string
    {
        $this->requireAdmin();

        try {
            $usuarioId = (int)($_POST['usuario_id'] ?? 0);
            $edificioId = (int)($_POST['edificio_id'] ?? 0);

            if ($usuarioId <= 0 || $edificioId <= 0) {
                throw new \RuntimeException("Datos inválidos");
            }

            $exito = $this->sigmuService->quitarAsignacionEdificio($usuarioId, $edificioId);

            return json_encode([
                'success' => $exito,
                'message' => $exito ? 'Asignación removida correctamente' : 'Error al remover asignación'
            ]);
        } catch (Throwable $e) {
            http_response_code(400);
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
