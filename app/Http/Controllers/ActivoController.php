<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Activo;
use App\Services\SigmuService;
use App\Support\Session;
use App\Support\Csrf;
use Throwable;

final class ActivoController
{
    private readonly Activo $modelo;
    private readonly SigmuService $sigmuService;

    public function __construct()
    {
        $this->modelo = new Activo();
        $this->sigmuService = new SigmuService();
    }

    /**
     * Muestra el detalle de un activo
     */
    public function show(int $id): string
    {
        if (!$this->requireAuth()) {
            return '';
        }

        $activo = $this->modelo->obtenerPorId($id);
        
        if (!$activo) {
            header('Location: /sigmu/edificios?error=activo_no_encontrado');
            return '';
        }

        // Obtener foto principal (delegado al modelo/service en el futuro)
        $db = \App\Support\Database::connection();
        $stmt = $db->prepare("SELECT ruta_foto FROM activo_foto WHERE activo_id = ? ORDER BY es_principal DESC, id DESC LIMIT 1");
        $stmt->execute([$id]);
        $activo['imagen'] = $stmt->fetchColumn();

        return view('inventario_catalogacion.ver_activo', [
            'activo' => $activo
        ]);
    }

    /**
     * Muestra el formulario para registrar un nuevo activo
     */
    public function create(): string
    {
        if (!$this->requireAuth()) {
            return '';
        }

        $salaId = filter_input(INPUT_GET, 'sala_id', FILTER_VALIDATE_INT);
        if (!$salaId) {
            header('Location: /sigmu/edificios?error=sala_no_especificada');
            return '';
        }

        try {
            $tiposActivo = $this->sigmuService->obtenerTiposActivo();
            $codigoGenerado = $this->sigmuService->generarCodigoActivo();
            
            return view('inventario_catalogacion.registrar_activo', [
                'tiposActivo' => $tiposActivo,
                'salaId' => $salaId,
                'formData' => ['codigo' => $codigoGenerado],
                'error' => $_GET['error'] ?? '',
                'success' => $_GET['success'] ?? '',
            ]);
        } catch (Throwable $e) {
            return "Error al cargar formulario: " . $e->getMessage();
        }
    }

    /**
     * Procesa el guardado de un nuevo activo
     */
    public function store(): void
    {
        if (!$this->requireAuth() || !Csrf::validate()) {
            header('Location: /sigmu?error=acceso_denegado');
            return;
        }

        $salaId = (int)($_POST['sala_id'] ?? 0);
        $datos = [
            'codigo' => trim((string)($_POST['codigo'] ?? '')),
            'nombre' => trim((string)($_POST['nombre'] ?? '')),
            'tipo_activo_id' => (int)($_POST['tipo_activo_id'] ?? 0),
            'descripcion' => trim((string)($_POST['descripcion'] ?? '')),
            'estado' => trim((string)($_POST['estado'] ?? 'disponible')),
            'sala_id' => $salaId,
            'cantidad' => (int)($_POST['cantidad'] ?? 1)
        ];

        try {
            $fotoPath = null;
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $fotoPath = $this->procesarFoto($_FILES['foto']);
            }

            if ($datos['cantidad'] > 1) {
                $res = $this->sigmuService->registrarMultiplesActivos(
                    $datos['cantidad'], $datos['nombre'], $datos['tipo_activo_id'],
                    $datos['descripcion'], $datos['estado'], $datos['sala_id'], $fotoPath
                );
            } else {
                $res = $this->sigmuService->registrarActivo(
                    $datos['codigo'], $datos['nombre'], $datos['tipo_activo_id'],
                    $datos['descripcion'], $datos['estado'], $datos['sala_id'], $fotoPath
                );
            }

            if ($res['success']) {
                header("Location: /sigmu/sala?sala_id={$salaId}&success=" . urlencode($res['message']));
            } else {
                header("Location: /sigmu/activo/registrar?sala_id={$salaId}&error=" . urlencode($res['message']));
            }
        } catch (Throwable $e) {
            header("Location: /sigmu/activo/registrar?sala_id={$salaId}&error=" . urlencode($e->getMessage()));
        }
    }

    /**
     * Formulario de edición
     */
    public function edit(int $id): string
    {
        if (!$this->requireAuth()) {
            return '';
        }

        $activo = $this->modelo->obtenerPorId($id);
        $habitaciones = $this->modelo->obtenerHabitaciones();
        $tiposActivo = $this->modelo->obtenerTiposActivo();
        $edificios = $this->modelo->obtenerEdificios();
        
        if (!$activo) {
            header('Location: /sigmu/edificios?error=activo_no_encontrado');
            return '';
        }

        // Obtener el edificio_id de la sala actual para pre-seleccionarlo
        $edificioActualId = 0;
        foreach ($habitaciones as $h) {
            if ($h['id'] == ($activo['sala_id'] ?? 0)) {
                $edificioActualId = $h['edificio_id'];
                break;
            }
        }

        return view('inventario_catalogacion.editar_activo', [
            'activo' => $activo,
            'habitaciones' => $this->modelo->obtenerHabitaciones(),
            'tiposActivo' => $this->sigmuService->obtenerTiposActivo(),
            'edificios' => $edificios,
            'edificioActualId' => $edificioActualId,
            'error' => $_GET['error'] ?? ''
        ]);
    }

    /**
     * Actualizar activo
     */
    public function update(int $id): void
    {
        if (!$this->requireAuth() || !Csrf::validate()) {
            return;
        }

        $datos = [
            'nombre' => trim((string)($_POST['nombre'] ?? '')),
            'descripcion' => trim((string)($_POST['descripcion'] ?? '')),
            'tipo_activo_id' => (int)($_POST['tipo_activo_id'] ?? 0),
            'estado' => trim((string)($_POST['estado'] ?? '')),
            'codigo' => trim((string)($_POST['codigo'] ?? '')),
            'sala_id' => (int)($_POST['sala_id'] ?? 0),
            'fecha_actualizado' => date('Y-m-d H:i:s')
        ];

        try {
            // Manejo de imagen (esto debería ir al Service idealmente)
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $fotoPath = $this->procesarFoto($_FILES['foto']);
                $this->sigmuService->agregarFotoUsuario($id, $fotoPath, 'Foto principal'); // Nota: el service usa agregarFotoUsuario pero sp_agregar_foto_activo es el que toca. 
                // Corregiré esto en el Service luego.
            }

            $this->modelo->actualizar($id, $datos);
            header("Location: /sigmu/activo/ver?id={$id}&success=activo_actualizado");
        } catch (Throwable $e) {
            header("Location: /sigmu/activo/editar?id={$id}&error=" . urlencode($e->getMessage()));
        }
    }

    /**
     * Dar de baja (borrado lógico)
     */
    public function darDeBaja(int $id): void
    {
        if (!$this->requireAuth()) return;

        try {
            $user = Session::get('auth_user');
            $activo = $this->modelo->obtenerPorId($id);
            
            if ($this->modelo->darDeBaja($id, (int)$user['id'])) {
                header("Location: /sigmu/sala?sala_id={$activo['sala_id']}&success=activo_descartado");
            } else {
                header("Location: /sigmu/activo/ver?id={$id}&error=error_al_descartar");
            }
        } catch (Throwable $e) {
            header("Location: /sigmu/activo/ver?id={$id}&error=" . urlencode($e->getMessage()));
        }
    }

    /**
     * Eliminar (borrado físico)
     */
    public function destroy(int $id): void
    {
        if (!$this->requireAuth()) return;

        try {
            $activo = $this->modelo->obtenerPorId($id);
            $salaId = $activo['sala_id'];
            
            if ($this->modelo->eliminar($id)) {
                header("Location: /sigmu/sala?sala_id={$salaId}&success=activo_eliminado");
            } else {
                header("Location: /sigmu/sala?sala_id={$salaId}&error=error_al_eliminar");
            }
        } catch (Throwable $e) {
            header("Location: /sigmu/edificios?error=" . urlencode($e->getMessage()));
        }
    }

    /**
     * Historial de un activo
     */
    public function historial(int $id): string
    {
        if (!$this->requireAuth()) return '';

        $activo = $this->modelo->obtenerPorId($id);
        if (!$activo) return 'Activo no encontrado';

        $historial = $this->modelo->obtenerHistorial(
            $id, 
            trim((string)($_GET['busqueda'] ?? '')),
            trim((string)($_GET['accion'] ?? '')),
            trim((string)($_GET['estado'] ?? ''))
        );

        return view('inventario_catalogacion.historial_activo', [
            'activo' => $activo,
            'historial' => $historial
        ]);
    }

    /**
     * Endpoint AJAX para generar código
     */
    public function generarCodigo(): void
    {
        header('Content-Type: application/json');
        $nombre = trim((string)($_GET['nombre'] ?? ''));
        echo json_encode(['success' => true, 'codigo' => $this->sigmuService->generarCodigoActivo($nombre)]);
    }

    private function procesarFoto(array $file): string
    {
        $uploadDir = __DIR__ . '/../../../public/uploads/activos/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = uniqid('activo_', true) . '.' . $extension;
        
        if (move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
            return 'uploads/activos/' . $fileName;
        }
        throw new \RuntimeException('Error al subir archivo');
    }

    private function requireAuth(): bool
    {
        if (!Session::has('auth_user')) {
            header('Location: /sigmu?error=debes_iniciar_sesion');
            return false;
        }
        $this->sigmuService->iniciarSesionBd((int)Session::get('auth_user')['id']);
        return true;
    }
}
