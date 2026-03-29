<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SigmuService;
use Throwable;

final class AuthController
{
    public function __construct(
        private readonly SigmuService $service = new SigmuService()
    ) {
    }

    public function login(): void
    {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            header('Location: /sigmu?error=credenciales_requeridas');
            return;
        }

        try {
            $user = $this->service->autenticar($username, $password);
            $userId = (int) $user['id'];
            $this->service->iniciarSesionBd($userId);
            $_SESSION['auth_user'] = [
                'id' => $userId,
                'username' => (string) $user['username'],
                'nombre_completo' => (string) $user['nombre_completo'],
                'rol_id' => (int) $user['rol_id'],
                'rol_nombre' => (string) $user['rol_nombre'],
                'ver_todo' => (bool) $user['ver_todo'],
            ];
            header('Location: /sigmu');
            return;
        } catch (Throwable $exception) {
            unset($_SESSION['auth_user']);
            header('Location: /sigmu?error=' . urlencode($exception->getMessage()));
            return;
        }
    }

    public function logout(): void
    {
        try {
            if (isset($_SESSION['auth_user']['id'])) {
                $this->service->cerrarSesionBd();
            }
        } catch (Throwable) {
            // Ignored on logout path
        }

        $_SESSION = [];
        session_destroy();
        header('Location: /sigmu');
    }
}