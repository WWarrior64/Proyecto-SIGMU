<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Session;
use App\Support\Roles;
use App\Services\SigmuService;
use App\Support\Database;
use Throwable;

final class RolController
{
    private readonly SigmuService $sigmuService;

    public function __construct()
    {
        $this->sigmuService = new SigmuService();
    }

    public function index(): string
    {
        if (!$this->requireAdmin()) return '';

        $roles = $this->sigmuService->obtenerRoles();
        return view('administracion_usuarios.gestion_roles', [
            'roles' => $roles
        ]);
    }

    public function guardar(): string
    {
        if (!$this->requireAdmin()) return '';

        $id = (int)($_POST['id'] ?? 0);
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $descripcion = trim((string)($_POST['descripcion'] ?? ''));
        $verTodo = isset($_POST['ver_todo']) && $_POST['ver_todo'] === '1';

        try {
            if ($nombre === '') throw new \RuntimeException("El nombre del rol es obligatorio.");

            // Protecciones mínimas de integridad
            if (Roles::is($id, Roles::ADMIN)) {
                // El rol Admin SIEMPRE debe tener verTodo = true para no bloquear el sistema
                $this->sigmuService->getRepository()->guardarRol($id, $nombre, $descripcion, true);
            } else {
                $this->sigmuService->getRepository()->guardarRol($id, $nombre, $descripcion, $verTodo);
            }

            return json_encode(['success' => true]);
        } catch (Throwable $e) {
            http_response_code(400);
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function eliminar(): string
    {
        if (!$this->requireAdmin()) return '';

        $id = (int)($_POST['id'] ?? 0);

        try {
            if (Roles::in($id, [Roles::ADMIN, Roles::RESPONSABLE_AREA, Roles::MANTENIMIENTO])) {
                throw new \RuntimeException("No se pueden eliminar los roles base del sistema.");
            }

            $exito = $this->sigmuService->getRepository()->eliminarRol($id);
            return json_encode(['success' => $exito]);
        } catch (Throwable $e) {
            http_response_code(400);
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function requireAdmin(): bool
    {
        if (!Session::has('auth_user')) {
            header('Location: /sigmu');
            return false;
        }
        $user = Session::get('auth_user');
        
        // Verificación basada en el ID de rol (constante)
        if ((int)$user['rol_id'] !== Roles::ADMIN) {
            header('Location: /sigmu');
            return false;
        }
        
        $this->sigmuService->iniciarSesionBd((int)$user['id']);
        return true;
    }
}
