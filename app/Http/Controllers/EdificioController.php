<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\EspacioService;
use App\Services\SigmuService;
use App\Support\Session;
use Throwable;

final class EdificioController
{
    private EspacioService $espacioService;
    private SigmuService $sigmuService;

    public function __construct()
    {
        $this->espacioService = new EspacioService();
        $this->sigmuService = new SigmuService();
    }

    private function requireAuth(): ?array
    {
        if (!Session::has('auth_user')) {
            header('Location: /sigmu?error=debes_iniciar_sesion');
            exit;
        }

        $user = Session::get('auth_user');
        $this->sigmuService->iniciarSesionBd((int)$user['id']);
        return $user;
    }

    /**
     * Vista principal de edificios (Panel de Espacios)
     */
    public function dashboard(): string
    {
        $user = $this->requireAuth();
        
        try {
            $edificios = $this->espacioService->listarEdificios();
            $responsables = [];
            
            // Si es administrador, obtener lista de responsables de area
            if (\App\Support\Roles::is($user['rol_id'], \App\Support\Roles::ADMIN)) {
                $responsables = $this->sigmuService->obtenerResponsablesArea();
                
                // Para cada edificio, obtener quien es el responsable actual
                foreach ($edificios as &$e) {
                    $asignados = $this->sigmuService->obtenerUsuariosAsignadosAEdificio((int)$e['id']);
                    $e['responsable_id'] = !empty($asignados) ? $asignados[0]['id'] : null;
                    $e['responsable_nombre'] = !empty($asignados) ? $asignados[0]['nombre_completo'] : 'Sin asignar';
                }
            }

            return view('localizacion_asignacion.panel_edificios', [
                'sessionUser' => $user,
                'edificios' => $edificios,
                'responsables' => $responsables
            ]);
        } catch (Throwable $e) {
            return view('localizacion_asignacion.panel_edificios', [
                'sessionUser' => $user,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Vista de salas de un edificio
     */
    public function salasPorEdificio(): string
    {
        $user = $this->requireAuth();
        $edificioId = (int)($_GET['edificio_id'] ?? 0);
        
        try {
            $edificio = $this->espacioService->obtenerEdificio($edificioId);
            $salas = $this->espacioService->listarSalas($edificioId);
            
            return view('localizacion_asignacion.salas', [
                'sessionUser' => $user,
                'edificio' => $edificio,
                'salas' => $salas,
                'edificioId' => $edificioId
            ]);
        } catch (Throwable $e) {
            header('Location: /sigmu/edificios?error=' . urlencode($e->getMessage()));
            return '';
        }
    }

    /**
     * Guardar/Editar edificio
     */
    public function guardar(): string
    {
        $user = $this->requireAuth();

        try {
            $data = $_POST;
            $edificioId = $this->espacioService->guardarEdificio($data);

            // Gestionar responsable de area (solo si el que guarda es administrador)
            if (\App\Support\Roles::is($user['rol_id'], \App\Support\Roles::ADMIN) && isset($data['responsable_id'])) {
                $nuevoResponsableId = (int)$data['responsable_id'];
                
                // Obtener asignaciones actuales
                $actuales = $this->sigmuService->obtenerUsuariosAsignadosAEdificio($edificioId);
                $actualId = !empty($actuales) ? (int)$actuales[0]['id'] : 0;

                if ($nuevoResponsableId > 0 && $nuevoResponsableId !== $actualId) {
                    // Si ya habia uno, quitarlo (asumimos uno por edificio para esta HU)
                    if ($actualId > 0) {
                        $this->sigmuService->quitarAsignacionEdificio($actualId, $edificioId);
                    }
                    $this->sigmuService->asignarEdificio($nuevoResponsableId, $edificioId);
                } elseif ($nuevoResponsableId === 0 && $actualId > 0) {
                    // Si se seleccionó "Sin asignar"
                    $this->sigmuService->quitarAsignacionEdificio($actualId, $edificioId);
                }
            }

            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $fotoPath = $this->procesarFotoEdificio($_FILES['foto']);
                $this->sigmuService->agregarFotoEdificio($edificioId, $fotoPath, 'Foto del edificio');
            }

            header('Location: /sigmu/edificios?success=Edificio guardado correctamente');
        } catch (Throwable $e) {
            header('Location: /sigmu/edificios?error=' . urlencode($e->getMessage()));
        }
        return '';
    }

    /**
     * Actualizar solo la foto de un edificio
     */
    public function updatePhoto(): string
    {
        $this->requireAuth();
        
        $edificioId = (int)($_POST['edificio_id'] ?? 0);
        try {
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $fotoPath = $this->procesarFotoEdificio($_FILES['foto']);
                $this->sigmuService->agregarFotoEdificio($edificioId, $fotoPath, 'Foto del edificio');
                header("Location: /sigmu/edificios?success=Foto actualizada correctamente");
            } else {
                header("Location: /sigmu/edificios?error=No se pudo subir la foto");
            }
        } catch (Throwable $e) {
            header("Location: /sigmu/edificios?error=" . urlencode($e->getMessage()));
        }
        return '';
    }

    /**
     * Guardar/Editar sala
     */
    public function guardarSala(): string
    {
        $this->requireAuth();
        
        try {
            $data = $_POST;
            $this->espacioService->guardarSala($data);
            $edificioId = (int)($data['edificio_id'] ?? 0);
            header('Location: /sigmu/edificio?edificio_id=' . $edificioId . '&success=Sala guardada correctamente');
        } catch (Throwable $e) {
            $edificioId = (int)($_POST['edificio_id'] ?? 0);
            header('Location: /sigmu/edificio?edificio_id=' . $edificioId . '&error=' . urlencode($e->getMessage()));
        }
        return '';
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
        $user = Session::get('auth_user');
        $userId = $user['id'] ?? null;
        if (is_numeric($userId) && (int)$userId > 0) {
            $this->sigmuService->iniciarSesionBd((int)$userId);
        }
    }

    /**
     * Eliminar edificio con validación de contraseña
     */
    public function eliminar(): string
    {
        $user = $this->requireAuth();
        $id = (int)($_POST['id'] ?? 0);
        $password = (string)($_POST['password'] ?? '');

        try {
            $this->espacioService->eliminarEdificio($id, (int)$user['id'], $password);
            header('Location: /sigmu/edificios?success=Edificio eliminado correctamente');
        } catch (Throwable $e) {
            $mensaje = $e->getMessage();
            
            // Detectar error de integridad (edificio con salas o activos)
            if (str_contains($mensaje, '1451') || str_contains($mensaje, 'Integrity constraint violation')) {
                $mensaje = 'No se puede eliminar el edificio porque aún contiene salas con activos asignados. Por favor, vacíe el edificio antes de intentar borrarlo.';
            }

            header('Location: /sigmu/edificios?error=' . urlencode($mensaje));
        }
        return '';
    }

    /**
     * Eliminar sala con validación de contraseña
     */
    public function eliminarSala(): string
    {
        $user = $this->requireAuth();
        $id = (int)($_POST['id'] ?? 0);
        $edificioId = (int)($_POST['edificio_id'] ?? 0);
        $password = (string)($_POST['password'] ?? '');

        try {
            $this->espacioService->eliminarSala($id, (int)$user['id'], $password);
            header('Location: /sigmu/edificio?edificio_id=' . $edificioId . '&success=Sala eliminada correctamente');
        } catch (Throwable $e) {
            $mensaje = $e->getMessage();
            
            // Detectar error de integridad (sala con activos)
            if (str_contains($mensaje, '1451') || str_contains($mensaje, 'Integrity constraint violation')) {
                $mensaje = 'No se puede eliminar la sala porque aún tiene activos asignados. Por favor, mueva o elimine los activos antes de intentar borrarla.';
            }

            header('Location: /sigmu/edificio?edificio_id=' . $edificioId . '&error=' . urlencode($mensaje));
        }
        return '';
    }
}
