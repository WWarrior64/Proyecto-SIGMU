<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SigmuService;
use Throwable;

final class SigmuController
{
    public function __construct(
        private readonly SigmuService $service = new SigmuService()
    ) {
    }

    public function dashboard(): string
    {
        $error = null;
        $sessionUser = $this->getSessionUser();
        $edificios = [];

        if ($sessionUser) {
            try {
                $this->syncDatabaseSession();
                $edificios = $this->service->obtenerMisEdificios();
            } catch (Throwable $exception) {
                $error = $exception->getMessage();
            }
        }

        if (!$sessionUser) {
            return view('administracion_usuarios.login', [
                'error' => $error,
            ]);
        }

        return view('localizacion_asignacion.panel_edificios', [
            'sessionUser' => $sessionUser,
            'edificios' => $edificios,
            'error' => $error,
        ]);
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

    public function salasPorEdificio(): string
    {
        if (!$this->requireAuth()) {
            return '';
        }

        $edificioId = filter_input(INPUT_GET, 'edificio_id', FILTER_VALIDATE_INT);
        if (!$edificioId) {
            return '<h2>edificio_id invalido</h2><p><a href="/sigmu">Volver</a></p>';
        }

        try {
            $salas = $this->service->obtenerMisSalas($edificioId);
            return view('localizacion_asignacion.salas', [
                'edificioId' => $edificioId,
                'salas' => $salas,
            ]);
        } catch (Throwable $exception) {
            return '<h2>Error</h2><p>' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
        }
    }

    public function activosPorSala(): string
    {
        if (!$this->requireAuth()) {
            return '';
        }

        $salaId = filter_input(INPUT_GET, 'sala_id', FILTER_VALIDATE_INT);
        if (!$salaId) {
            return '<h2>sala_id invalido</h2><p><a href="/sigmu">Volver</a></p>';
        }

        try {
            $activos = $this->service->obtenerMisActivos($salaId);
            return view('inventario_catalogacion.listado_activos', [
                'salaId' => $salaId,
                'activos' => $activos,
            ]);
        } catch (Throwable $exception) {
            return '<h2>Error</h2><p>' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
        }
    }

    private function syncDatabaseSession(): void
    {
        $userId = $this->getSessionUser()['id'] ?? null;
        if (is_int($userId) && $userId > 0) {
            $this->service->iniciarSesionBd($userId);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getSessionUser(): ?array
    {
        $user = $_SESSION['auth_user'] ?? null;
        return is_array($user) ? $user : null;
    }

    private function requireAuth(): bool
    {
        $user = $this->getSessionUser();
        if (!$user || empty($user['id'])) {
            header('Location: /sigmu?error=debes_iniciar_sesion');
            return false;
        }

        $this->syncDatabaseSession();
        return true;
    }
}
