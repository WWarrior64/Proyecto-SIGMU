<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Session;
use App\Services\SigmuService;

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

    private function requireAdmin(): bool
    {
        if (!Session::has('auth_user')) {
            header('Location: /sigmu');
            return false;
        }
        $user = Session::get('auth_user');
        if ($user['rol_nombre'] !== 'Administrador') {
            header('Location: /sigmu');
            return false;
        }
        $this->sigmuService->iniciarSesionBd((int)$user['id']);
        return true;
    }
}
