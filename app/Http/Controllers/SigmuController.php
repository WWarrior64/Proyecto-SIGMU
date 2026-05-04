<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SigmuService;
use App\Support\Csrf;
use App\Support\Session;
use Throwable;

// Este controlador es el "puente" entre el navegador y el sistema.
// Aquí solo recibimos inputs (GET/POST), llamamos al servicio y devolvemos vistas.
final class SigmuController
{
    public function __construct(
        private readonly SigmuService $service = new SigmuService()
    ) {
    }
    public function dashboard(): string
    {
        // Si hay sesión, cargamos el panel. Si no, mostramos login.
        $error = null;
        $sessionUser = $this->getSessionUser();
        $edificios = [];

        if ($sessionUser) {
            try {
                // Sincronizamos la sesión de BD para que las vistas restringidas funcionen.
                $this->syncDatabaseSession();
                $edificios = $this->service->obtenerMisEdificios();
            } catch (Throwable $exception) {
                $error = $exception->getMessage();
            }
        }

        if (!$sessionUser) {
            // Vista: login con identidad UNICAES.
            return view('administracion_usuarios.login', [
                'error' => $error,
            ]);
        }

        // ✅ REDIRECCION SEGUN ROL DE USUARIO
        // Si es ADMINISTRADOR mostrar panel exclusivo
        if ($sessionUser['rol_nombre'] === 'Administrador') {
            return view('administracion_usuarios.inicio', [
                'sessionUser' => $sessionUser,
                'error' => $error,
            ]);
        }

        // Si es PERSONAL DE MANTENIMIENTO, redirigir a su panel específico
        if ($sessionUser['rol_nombre'] === 'Personal Mantenimiento') {
            header('Location: /sigmu/mantenimiento');
            return '';
        }

        // Para demas roles mostrar panel normal de edificios
        return view('localizacion_asignacion.panel_edificios', [
            'sessionUser' => $sessionUser,
            'edificios' => $edificios,
            'error' => $error,
        ]);
    }


    private function syncDatabaseSession(): void
    {
        // Sin esto, tus vistas (vista_mis_*) no "saben" qué usuario está navegando.
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

    public function requireAuth(): bool
    {
        // Guard simple: si no hay usuario, regresamos al login.
        $user = $this->getSessionUser();
        if (!$user || empty($user['id'])) {
            header('Location: /sigmu?error=debes_iniciar_sesion');
            return false;
        }

        // Si sí hay usuario, sincronizamos la sesión de BD y seguimos.
        $this->syncDatabaseSession();
        return true;
    }

    public function getSalasAjax(int $edificioId): void
    {
        if (!$this->requireAuth()) {
            http_response_code(401);
            return;
        }

        $salas = $this->service->obtenerMisSalas($edificioId);
        header('Content-Type: application/json');
        echo json_encode($salas);
    }

    public function getActivosAjax(int $salaId): void
    {
        if (!$this->requireAuth()) {
            http_response_code(401);
            return;
        }

        $activos = $this->service->obtenerMisActivos($salaId);
        header('Content-Type: application/json');
        echo json_encode($activos);
    }
}

