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

    public function updatePhoto(): void
    {
        if (!$this->requireAuth()) {
            header('Location: /sigmu?error=acceso_denegado');
            return;
        }

        $edificioId = (int)($_POST['edificio_id'] ?? 0);
        
        try {
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                // Necesitamos procesarFotoEdificio que está en ActivoController.
                // Lo moveré a un trait o helper si fuera necesario, pero por ahora lo implementaré aquí o usaré una instancia de ActivoController.
                // Mejor lo implemento aquí para no complicar.
                $fotoPath = $this->procesarFotoEdificio($_FILES['foto']);
                $this->service->agregarFotoEdificio($edificioId, $fotoPath, 'Foto del edificio');
                header("Location: /sigmu/edificios?success=foto_actualizada");
            } else {
                header("Location: /sigmu/edificios?error=error_al_subir_foto");
            }
        } catch (Throwable $e) {
            header("Location: /sigmu/edificios?error=" . urlencode($e->getMessage()));
        }
    }

    private function procesarFotoEdificio(array $file): string
    {
        $uploadDir = __DIR__ . '/../../../public/uploads/edificios/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = uniqid('edificio_', true) . '.' . $extension;
        
        if (move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
            return 'uploads/edificios/' . $fileName;
        }
        throw new \RuntimeException('Error al subir archivo de edificio');
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