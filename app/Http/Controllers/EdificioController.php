<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SigmuService;
use Throwable;

final class EdificioController
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