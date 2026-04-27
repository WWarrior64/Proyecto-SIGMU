<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Activo;
use App\Services\SigmuService;
use App\Services\AssetImportService;
use App\Support\Session;
use App\Support\Csrf;
use Throwable;

final class ActivoController
{
    private readonly Activo $modelo;
    private readonly SigmuService $sigmuService;
    private readonly AssetImportService $importService;

    public function __construct()
    {
        $this->modelo = new Activo();
        $this->sigmuService = new SigmuService();
        $this->importService = new AssetImportService();
    }

    /**
     * Muestra el formulario para importar activos
     */
    public function import(): string
    {
        if (!$this->requireAuth()) {
            return '';
        }

        $salaId = filter_input(INPUT_GET, 'sala_id', FILTER_VALIDATE_INT);
        if (!$salaId) {
            header('Location: /sigmu/edificios?error=sala_no_especificada');
            return '';
        }

        return view('inventario_catalogacion.importar_activos', [
            'salaId' => $salaId,
            'error' => $_GET['error'] ?? '',
            'success' => $_GET['success'] ?? '',
            'results' => Session::get('import_results')
        ]);
    }

    /**
     * Procesa el archivo de importación
     */
    public function processImport(): void
    {
        if (!$this->requireAuth() || !Csrf::validate()) {
            header('Location: /sigmu?error=acceso_denegado');
            return;
        }

        $salaId = (int)($_POST['sala_id'] ?? 0);
        
        if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            header("Location: /sigmu/activo/importar?sala_id={$salaId}&error=" . urlencode("Debes seleccionar un archivo válido"));
            return;
        }

        try {
            $results = $this->importService->importFromFile(
                $_FILES['archivo']['tmp_name'], 
                $_FILES['archivo']['name'],
                $salaId
            );
            
            Session::set('import_results', $results);
            
            $mensaje = "Importación completada: {$results['success']} activos importados.";
            if (!empty($results['errors'])) {
                $mensaje .= " Hubo algunos errores.";
            }
            
            header("Location: /sigmu/activo/importar?sala_id={$salaId}&success=" . urlencode($mensaje));
        } catch (Throwable $e) {
            header("Location: /sigmu/activo/importar?sala_id={$salaId}&error=" . urlencode($e->getMessage()));
        }
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

        // ✅ VALIDACIÓN DE PERMISOS: No permitir ver si el usuario no tiene acceso a la sala
        $user = Session::get('auth_user');
        if ($user['rol_nombre'] !== 'Administrador') {
            // Usamos las salas accesibles para el usuario
            $salasAccesibles = $this->sigmuService->obtenerTodasLasSalas();
            $idsSalas = array_column($salasAccesibles, 'id');
            
            if (!in_array((int)$activo['sala_id'], $idsSalas)) {
                $mensaje = "El activo se ha movido correctamente, pero ya no tienes acceso a él por estar fuera de tu jurisdicción.";
                header('Location: /sigmu/edificios?info=' . urlencode($mensaje));
                return '';
            }
        }

        // Obtener todas las fotos
        $fotos = $this->sigmuService->obtenerFotosActivo($id);
        $activo['fotos'] = $fotos;
        // Mantener compatibilidad con 'imagen' para la foto principal
        $activo['imagen'] = !empty($fotos) ? $fotos[0]['ruta_foto'] : null;

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

        $estado = trim((string)($_POST['estado'] ?? 'disponible'));
        $salaId = (int)($_POST['sala_id'] ?? 0);

        if ($salaId <= 0) {
            header("Location: /sigmu/activo/registrar?error=" . urlencode("Sala no especificada"));
            return;
        }

        // ✅ VALIDACIÓN DE ESTADO
        if (!array_key_exists($estado, Activo::ESTADOS)) {
            header("Location: /sigmu/activo/registrar?sala_id={$salaId}&error=" . urlencode("Estado no válido seleccionado"));
            return;
        }

        $datos = [
            'codigo' => trim((string)($_POST['codigo'] ?? '')),
            'nombre' => trim((string)($_POST['nombre'] ?? '')),
            'tipo_activo_id' => (int)($_POST['tipo_activo_id'] ?? 0),
            'descripcion' => trim((string)($_POST['descripcion'] ?? '')),
            'estado' => $estado,
            'sala_id' => $salaId,
            'cantidad' => (int)($_POST['cantidad'] ?? 1)
        ];

        try {
            $fotoPaths = [];
            if (isset($_FILES['fotos'])) {
                $fotoPaths = $this->procesarMultiplesFotos($_FILES['fotos']);
            }

            if ($datos['cantidad'] > 1) {
                $res = $this->sigmuService->registrarMultiplesActivos(
                    $datos['cantidad'], $datos['nombre'], $datos['tipo_activo_id'],
                    $datos['descripcion'], $datos['estado'], $datos['sala_id'], $fotoPaths
                );
            } else {
                $res = $this->sigmuService->registrarActivo(
                    $datos['codigo'], $datos['nombre'], $datos['tipo_activo_id'],
                    $datos['descripcion'], $datos['estado'], $datos['sala_id'], $fotoPaths
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

        $estado = trim((string)($_POST['estado'] ?? ''));

        // ✅ VALIDACIÓN DE ESTADO
        if (!array_key_exists($estado, Activo::ESTADOS)) {
            header("Location: /sigmu/activo/editar?id={$id}&error=" . urlencode("Estado no válido seleccionado"));
            return;
        }

        $datos = [
            'nombre' => trim((string)($_POST['nombre'] ?? '')),
            'descripcion' => trim((string)($_POST['descripcion'] ?? '')),
            'tipo_activo_id' => (int)($_POST['tipo_activo_id'] ?? 0),
            'estado' => $estado,
            'codigo' => trim((string)($_POST['codigo'] ?? '')),
            'sala_id' => (int)($_POST['sala_id'] ?? 0),
            'fecha_actualizado' => date('Y-m-d H:i:s')
        ];

        try {
            // Verificar si el activo ya tiene fotos antes de procesar las nuevas
            $fotosExistentes = $this->sigmuService->obtenerFotosActivo($id);
            $tieneFotosPrevias = !empty($fotosExistentes);

            if (isset($_FILES['fotos'])) {
                $fotoPaths = $this->procesarMultiplesFotos($_FILES['fotos']);
                foreach ($fotoPaths as $index => $path) {
                    // Solo será principal si NO tiene fotos previas Y es la primera del nuevo lote
                    $esPrincipal = (!$tieneFotosPrevias && $index === 0);
                    $this->sigmuService->agregarFotoActivo($id, $path, 'Foto ' . ($index + 1), $esPrincipal);
                    
                    // Si acabamos de agregar una que es principal, el resto ya no lo serán
                    if ($esPrincipal) {
                        $tieneFotosPrevias = true;
                    }
                }
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

    public function setPrincipalPhoto(): void
    {
        if (!$this->requireAuth() || !Csrf::validate()) {
            return;
        }

        $fotoId = (int)($_POST['foto_id'] ?? 0);
        $activoId = (int)($_POST['activo_id'] ?? 0);

        if ($this->sigmuService->establecerPrincipalFotoActivo($fotoId)) {
            header("Location: /sigmu/activo/editar?id={$activoId}&success=foto_principal_actualizada");
        } else {
            header("Location: /sigmu/activo/editar?id={$activoId}&error=error_al_actualizar_foto");
        }
    }

    public function deletePhoto(): void
    {
        if (!$this->requireAuth() || !Csrf::validate()) {
            return;
        }

        $fotoId = (int)($_POST['foto_id'] ?? 0);
        $activoId = (int)($_POST['activo_id'] ?? 0);

        if ($this->sigmuService->eliminarFotoActivo($fotoId)) {
            header("Location: /sigmu/activo/editar?id={$activoId}&success=foto_eliminada");
        } else {
            header("Location: /sigmu/activo/editar?id={$activoId}&error=error_al_eliminar_foto");
        }
    }

    private function procesarMultiplesFotos(array $files): array
    {
        $paths = [];
        
        // Si no es un array de nombres, es que solo se subió uno o el formato es simple
        if (!isset($files['name']) || !is_array($files['name'])) {
            if (isset($files['error']) && $files['error'] === UPLOAD_ERR_OK) {
                $paths[] = $this->procesarFoto($files);
            }
            return $paths;
        }

        // Estructura de PHP para múltiples archivos: $_FILES['campo']['name'][0], $_FILES['campo']['name'][1]...
        $fileCount = count($files['name']);
        for ($i = 0; $i < $fileCount; $i++) {
            // Verificar que realmente se haya subido un archivo en esta posición
            if (isset($files['error'][$i]) && $files['error'][$i] === UPLOAD_ERR_OK) {
                $fileData = [
                    'name'     => $files['name'][$i],
                    'type'     => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error'    => $files['error'][$i],
                    'size'     => $files['size'][$i]
                ];
                $paths[] = $this->procesarFoto($fileData);
            }
        }
        
        return $paths;
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
