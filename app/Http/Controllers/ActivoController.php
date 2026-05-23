<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Activo;
use App\Services\SigmuService;
use App\Services\AssetImportService;
use App\Support\Logger;
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
            Logger::warning('Intento de importación sin archivo válido');
            header("Location: /sigmu/activo/importar?sala_id={$salaId}&error=" . urlencode("Debe seleccionar un archivo válido"));
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
            $salaId = Session::get('ultima_sala_id');
            $url = $salaId ? "/sigmu/sala?sala_id={$salaId}" : "/sigmu/edificios";
            header('Location: ' . $url . (str_contains($url, '?') ? '&' : '?') . 'error=' . urlencode('El activo solicitado no existe o ha sido eliminado del sistema.'));
            exit;
        }

        // ✅ VALIDACIÓN DE PERMISOS: No permitir ver si el usuario no tiene acceso a la sala
        $user = Session::get('auth_user');
        if (!\App\Support\Roles::is($user['rol_id'], \App\Support\Roles::ADMIN)) {
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
            'valor_adquisicion' => isset($_POST['valor_adquisicion']) && $_POST['valor_adquisicion'] !== '' ? (float)$_POST['valor_adquisicion'] : null,
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
                    $datos['cantidad'],
                    $datos['nombre'],
                    $datos['tipo_activo_id'],
                    $datos['descripcion'],
                    $datos['valor_adquisicion'],
                    $datos['estado'],
                    $datos['sala_id'],
                    $fotoPaths
                );
            } else {
                $res = $this->sigmuService->registrarActivo(
                    $datos['codigo'],
                    $datos['nombre'],
                    $datos['tipo_activo_id'],
                    $datos['descripcion'],
                    $datos['valor_adquisicion'],
                    $datos['estado'],
                    $datos['sala_id'],
                    $fotoPaths
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
        $habitaciones = $this->modelo->obtenerSalas();
        $tiposActivo = $this->modelo->obtenerTiposActivo();
        $edificios = $this->modelo->obtenerEdificios();

        if (!$activo) {
            $salaId = Session::get('ultima_sala_id');
            $url = $salaId ? "/sigmu/sala?sala_id={$salaId}" : "/sigmu/edificios";
            header('Location: ' . $url . (str_contains($url, '?') ? '&' : '?') . 'error=' . urlencode('El activo que intenta editar no existe o ha sido eliminado.'));
            exit;
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
            'habitaciones' => $this->modelo->obtenerSalas(),
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
            'valor_adquisicion' => isset($_POST['valor_adquisicion']) && $_POST['valor_adquisicion'] !== '' ? (float)$_POST['valor_adquisicion'] : null,
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

            $activoAnterior = $this->modelo->obtenerPorId($id);
            $salaAnteriorId = (int)$activoAnterior['sala_id'];
            $salaNuevaId = (int)$datos['sala_id'];

            $this->modelo->actualizar($id, $datos);

            // Determinar mensaje de éxito
            $mensaje = "El activo fue actualizado correctamente.";
            if ($salaAnteriorId !== $salaNuevaId) {
                $mensaje = "El activo fue trasladado con éxito.";
                
                // Verificar acceso del usuario a la nueva sala
                $user = Session::get('auth_user');
                if (!\App\Support\Roles::is($user['rol_id'], \App\Support\Roles::ADMIN)) {
                    $salasAccesibles = $this->sigmuService->obtenerTodasLasSalas();
                    $idsSalas = array_column($salasAccesibles, 'id');
                    
                    if (!in_array($salaNuevaId, $idsSalas)) {
                        $mensaje .= " Sin embargo, el activo ha sido movido a una ubicación a la que no tienes permisos de acceso.";
                    }
                }
            }

            header("Location: /sigmu/activo/ver?id={$id}&success=" . urlencode($mensaje));
        } catch (Throwable $e) {
            header("Location: /sigmu/activo/editar?id={$id}&error=" . urlencode("No fue posible actualizar el activo: " . $e->getMessage()));
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
                header("Location: /sigmu/sala?sala_id={$activo['sala_id']}&success=Activo descartado correctamente");
            } else {
                header("Location: /sigmu/activo/ver?id={$id}&error=No fue posible descartar el activo");
            }
        } catch (Throwable $e) {
            header("Location: /sigmu/activo/ver?id={$id}&error=" . urlencode($e->getMessage()));
        }
    }

    /**
     * Eliminar (borrado físico)
     */
    public function destroy(): void
    {
        if (!$this->requireAuth()) return;

        $id = (int)($_POST['id'] ?? 0);
        $password = (string)($_POST['password'] ?? '');
        $userSession = Session::get('auth_user');

        try {
            // 1. Validar contraseña
            $user = $this->sigmuService->obtenerUsuarioPorId((int)$userSession['id']);
            if (!$user || !password_verify($password, (string)$user['contrasena_hash'])) {
                header("Location: /sigmu/edificios?error=Contraseña incorrecta");
                return;
            }

            // 2. Eliminar
            $activo = $this->modelo->obtenerPorId($id);
            if (!$activo) {
                header("Location: /sigmu/edificios?error=Activo no encontrado");
                return;
            }

            $salaId = (int)$activo['sala_id'];
            
            if ($this->modelo->eliminar($id)) {
                header("Location: /sigmu/sala?sala_id={$salaId}&success=Activo eliminado permanentemente");
            } else {
                header("Location: /sigmu/sala?sala_id={$salaId}&error=No fue posible eliminar el activo");
            }
        } catch (Throwable $e) {
            header("Location: /sigmu/edificios?error=" . urlencode($e->getMessage()));
        }
    }

    public function historial(int $id): string
    {
        if (!$this->requireAuth()) return '';

        $activo = $this->modelo->obtenerPorId($id);
        if (!$activo) return 'Activo no encontrado';

        $pagina = (int) ($_GET['pagina'] ?? 1);
        $porPagina = 50;
        $busqueda = trim((string)($_GET['busqueda'] ?? ''));
        $accion = trim((string)($_GET['accion'] ?? ''));
        $estado = trim((string)($_GET['estado'] ?? ''));
        
        $ordenarPor = trim((string) ($_GET['ordenar_por'] ?? 'fecha'));
        $ordenDireccion = strtoupper((string) ($_GET['orden_direccion'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $historial = $this->modelo->obtenerHistorial($id, $busqueda, $accion, $estado, $pagina, $porPagina, $ordenarPor, $ordenDireccion);
        $total = $this->modelo->contarHistorial($id, $busqueda, $accion, $estado);
        $totalPaginas = (int) ceil($total / $porPagina);

        return view('inventario_catalogacion.historial_activo', [
            'activo' => $activo,
            'historial' => $historial,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'total' => $total,
            'busqueda' => $busqueda,
            'filtroAccion' => $accion,
            'filtroEstado' => $estado,
            'ordenarPor' => $ordenarPor,
            'ordenDireccion' => $ordenDireccion
        ]);
    }

    /**
     * Endpoint AJAX para generar código (legacy, solo con nombre)
     */
    public function generarCodigo(): void
    {
        header('Content-Type: application/json');
        $nombre = trim((string)($_GET['nombre'] ?? ''));
        echo json_encode(['success' => true, 'codigo' => $this->sigmuService->generarCodigoActivo($nombre)]);
    }

    /**
     * Endpoint AJAX para generar código completo con el nuevo formato
     * GET /sigmu/activo/generar-codigo-completo?nombre=Escritorio&tipo_id=1&codigo_cuenta=
     * Si codigo_cuenta está vacío, se autogenera: [NOMBRE_ABREV]-[TIPO_ABREV]
     * Luego se completa: [CODIGO_CUENTA]-[CORRELATIVO(3)]-[AÑO(2)]
     */
    public function generarCodigoCompleto(): void
    {
        header('Content-Type: application/json');
        
        $nombre = trim((string)($_GET['nombre'] ?? ''));
        $tipoId = (int)($_GET['tipo_id'] ?? 0);
        $codigoCuenta = trim((string)($_GET['codigo_cuenta'] ?? ''));
        
        try {
            // Si no hay código de cuenta manual, autogenerarlo
            if (empty($codigoCuenta)) {
                if (empty($nombre) || $tipoId <= 0) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Se requiere nombre y tipo de activo para generar el código',
                        'codigo_completo' => '',
                        'codigo_cuenta' => '',
                        'abreviatura_nombre' => '',
                        'abreviatura_tipo' => ''
                    ]);
                    return;
                }
                
                $abrevNombre = $this->sigmuService->generarAbreviaturaNombre($nombre);
                $tipoNombre = $this->sigmuService->obtenerNombreTipoActivo($tipoId);
                $abrevTipo = $this->sigmuService->generarAbreviaturaTipo($tipoNombre);
                $codigoCuenta = $abrevNombre . '-' . $abrevTipo;
            }
            
            // Generar código completo con correlativo
            $resultado = $this->sigmuService->generarCodigoCompleto($codigoCuenta);
            
            echo json_encode([
                'success' => true,
                'codigo_completo' => '*' . $resultado['codigo_completo'] . '*',
                'codigo_cuenta' => $codigoCuenta,
                'correlativo' => $resultado['correlativo'],
                'year' => $resultado['year'],
            ]);
        } catch (\Throwable $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al generar código: ' . $e->getMessage(),
            ]);
        }
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
