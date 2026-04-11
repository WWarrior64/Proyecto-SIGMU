<?php

namespace App\Http\Controllers;

use App\Models\Activo;
use App\Services\SigmuService;
use App\Support\Csrf;
use PDO;
use Throwable;

class ActivoController
{
    private Activo $modelo;
    private SigmuService $sigmuService;
    private $db;

    public function __construct()
    {
        $this->modelo = new Activo();
        $this->sigmuService = new SigmuService();
        $this->db = \App\Support\Database::connection();
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
            // Obtener parametros de ordenamiento
            $ordenarPor = trim((string) ($_GET['ordenar_por'] ?? 'id'));
            $ordenDireccion = trim((string) ($_GET['orden_direccion'] ?? 'DESC'));
            
            // Validar campos permitidos
            $camposPermitidos = ['id', 'codigo', 'nombre', 'tipo', 'estado'];
            $ordenarPor = in_array($ordenarPor, $camposPermitidos) ? $ordenarPor : 'id';
            $ordenDireccion = strtoupper($ordenDireccion) === 'ASC' ? 'ASC' : 'DESC';
            
            // Obtener activos ya ordenados directamente desde el modelo
            $activos = $this->modelo->listar(1, 100, '', [], [], $ordenarPor, $ordenDireccion);
            // Filtrar solo los de esta sala
            $activos = array_filter($activos, function($a) use ($salaId) {
                return (int)$a['sala_id'] === (int)$salaId;
            });
            // Reindexar array
            $activos = array_values($activos);
            
            // Obtener información de la sala y edificio
            $sala = null;
            $edificio = null;
            if (!empty($activos)) {
                $primerActivo = $activos[0];
                $sala = $primerActivo['sala_nombre'] ?? null;
                $edificio = $primerActivo['edificio_nombre'] ?? null;
            }
            
            return view('inventario_catalogacion.listado_activos', [
                'salaId' => $salaId,
                'activos' => $activos,
                'sala' => $sala,
                'edificio' => $edificio,
                'ordenarPor' => $ordenarPor,
                'ordenDireccion' => $ordenDireccion
            ]);
        } catch (Throwable $exception) {
            return '<h2>Error</h2><p>' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
        }
    }

    /**
     * Muestra el formulario para registrar un nuevo activo
     */
    public function registrarActivoGet(): string
    {
        if (!$this->requireAuth()) {
            return '';
        }

        try {
            $tiposActivo = $this->sigmuService->obtenerTiposActivo();
            
            // Generar código automático
            $codigoGenerado = $this->sigmuService->generarCodigoActivo();
            
            // Sala actual desde la URL o sesión
            $salaId = filter_input(INPUT_GET, 'sala_id', FILTER_VALIDATE_INT);
            
            if (!$salaId) {
                return '<h2>Error: No se especificó una sala</h2><p><a href="/sigmu">Volver</a></p>';
            }
            
            return view('inventario_catalogacion.registrar_activo', [
                'tiposActivo' => $tiposActivo,
                'salaId' => $salaId,
                'formData' => [
                    'codigo' => $codigoGenerado,
                ],
                'error' => '',
                'success' => '',
            ]);
        } catch (Throwable $exception) {
            return view('inventario_catalogacion.registrar_activo', [
                'tiposActivo' => [],
                'salaId' => 0,
                'formData' => [],
                'error' => $exception->getMessage(),
                'success' => '',
            ]);
        }
    }

    /**
     * Procesa el registro de un nuevo activo
     */
    public function registrarActivoPost(): string
    {
        if (!$this->requireAuth()) {
            return '';
        }

        // Verificar token CSRF
        if (!Csrf::validate()) {
            return $this->registrarActivoGetWithError('Token CSRF inválido. Por favor, recarga la página e intenta de nuevo.');
        }

        // Obtener y validar datos del formulario
        $codigo = trim((string) ($_POST['codigo'] ?? ''));
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $tipoActivoId = filter_input(INPUT_POST, 'tipo_activo_id', FILTER_VALIDATE_INT);
        $descripcion = trim((string) ($_POST['descripcion'] ?? ''));
        $estado = trim((string) ($_POST['estado'] ?? ''));
        $salaId = filter_input(INPUT_POST, 'sala_id', FILTER_VALIDATE_INT);

        // Validar campos obligatorios
        $errors = [];
        if ($codigo === '') {
            $errors[] = 'El código es obligatorio.';
        }
        if ($nombre === '') {
            $errors[] = 'El nombre es obligatorio.';
        }
        if (!$tipoActivoId) {
            $errors[] = 'El tipo de activo es obligatorio.';
        }
        if ($estado === '') {
            $errors[] = 'El estado es obligatorio.';
        }
        if (!$salaId) {
            $errors[] = 'La sala es obligatoria.';
        }

        // Validar formato del código
        if ($codigo !== '' && !preg_match('/^[A-Za-z0-9\-]+$/', $codigo)) {
            $errors[] = 'El código solo puede contener letras, números y guiones.';
        }

        // Validar longitud del nombre
        if ($nombre !== '' && strlen($nombre) > 100) {
            $errors[] = 'El nombre no puede exceder 100 caracteres.';
        }

        // Validar estado
        $estadosValidos = ['disponible', 'en_uso', 'reparacion', 'descartado'];
        if ($estado !== '' && !in_array($estado, $estadosValidos, true)) {
            $errors[] = 'El estado seleccionado no es válido.';
        }

        // Si hay errores, volver al formulario con los datos
        if (!empty($errors)) {
            return $this->registrarActivoGetWithError(implode(' ', $errors), [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'tipo_activo_id' => $tipoActivoId,
                'descripcion' => $descripcion,
                'estado' => $estado,
                'sala_id' => $salaId,
            ]);
        }

        try {
            // Procesar foto si se subió
            $fotoPath = null;
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $fotoPath = $this->procesarFoto($_FILES['foto']);
            }

            // Registrar el activo
            $resultado = $this->sigmuService->registrarActivo(
                $codigo,
                $nombre,
                $tipoActivoId,
                $descripcion,
                $estado,
                $salaId,
                $fotoPath
            );

            if ($resultado['success']) {
                // Redirigir al listado de activos de la sala con mensaje de éxito
                header('Location: /sigmu/sala?sala_id=' . $salaId . '&success=' . urlencode('Activo registrado exitosamente con ID: ' . $resultado['activo_id']));
                return '';
            } else {
                return $this->registrarActivoGetWithError($resultado['message'], [
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                    'tipo_activo_id' => $tipoActivoId,
                    'descripcion' => $descripcion,
                    'estado' => $estado,
                    'sala_id' => $salaId,
                ]);
            }
        } catch (Throwable $exception) {
            return $this->registrarActivoGetWithError($exception->getMessage(), [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'tipo_activo_id' => $tipoActivoId,
                'descripcion' => $descripcion,
                'estado' => $estado,
                'sala_id' => $salaId,
            ]);
        }
    }

    /**
     * Helper para mostrar el formulario con error
     */
    private function registrarActivoGetWithError(string $error, array $formData = []): string
    {
        try {
            $tiposActivo = $this->sigmuService->obtenerTiposActivo();
            $salaId = $formData['sala_id'] ?? 0;
            
            return view('inventario_catalogacion.registrar_activo', [
                'tiposActivo' => $tiposActivo,
                'salaId' => $salaId,
                'formData' => $formData,
                'error' => $error,
                'success' => '',
            ]);
        } catch (Throwable $exception) {
            return view('inventario_catalogacion.registrar_activo', [
                'tiposActivo' => [],
                'salaId' => 0,
                'formData' => $formData,
                'error' => $error . ' (Error adicional: ' . $exception->getMessage() . ')',
                'success' => '',
            ]);
        }
    }

    /**
     * Genera código de activo basado en el nombre (endpoint AJAX)
     */
    public function generarCodigo(): void
    {
        header('Content-Type: application/json');
        
        try {
            $nombre = trim((string) ($_GET['nombre'] ?? ''));
            
            if (empty($nombre)) {
                echo json_encode(['success' => false, 'message' => 'Nombre no proporcionado']);
                return;
            }
            
            $codigo = $this->sigmuService->generarCodigoActivo($nombre);
            
            echo json_encode(['success' => true, 'codigo' => $codigo]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Obtener todos los tipos de activo para el filtro frontend
     */
    public function obtenerTiposActivo(): void
    {
        header('Content-Type: application/json');
        
        try {
            $tipos = $this->modelo->obtenerTiposActivo();
            echo json_encode($tipos);
        } catch (\Throwable $e) {
            echo json_encode([]);
        }
    }

    /**
     * Procesa la subida de foto del activo
     */
    private function procesarFoto(array $file): string
    {
        $uploadDir = __DIR__ . '/../../../public/uploads/activos/';
        
        // Crear directorio si no existe
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Validar tipo de archivo
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($file['tmp_name']);
        if (!in_array($fileType, $allowedTypes, true)) {
            throw new \RuntimeException('Tipo de archivo no permitido. Solo se aceptan JPG, PNG y GIF.');
        }

        // Validar tamaño (5MB máximo)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new \RuntimeException('El archivo es demasiado grande. Tamaño máximo: 5MB.');
        }

        // Generar nombre único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = uniqid('activo_', true) . '.' . $extension;
        $filePath = $uploadDir . $fileName;

        // Mover archivo
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new \RuntimeException('Error al subir el archivo.');
        }

        return 'uploads/activos/' . $fileName;
    }

    private function syncDatabaseSession(): void
    {
        $userId = $this->getSessionUser()['id'] ?? null;
        if (is_int($userId) && $userId > 0) {
            $this->sigmuService->iniciarSesionBd($userId);
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

    /**
     * Mostrar listado de activos
     */
    public function index()
    {
        $pagina = (int) ($_GET['pagina'] ?? 1);
        $busqueda = trim((string) ($_GET['busqueda'] ?? ''));
        $salaId = (int) ($_GET['sala_id'] ?? 0);
        $porPagina = 10;
        $ordenarPor = trim((string) ($_GET['ordenar_por'] ?? 'id'));
        $ordenDireccion = trim((string) ($_GET['orden_direccion'] ?? 'DESC'));
        
        // Obtener filtros desde la peticion
        $estados = $_GET['estados'] ?? [];
        $tipos = $_GET['tipos'] ?? [];
        
        // Normalizar y sanitizar filtros
        if (!is_array($estados)) $estados = [];
        if (!is_array($tipos)) $tipos = [];
        
        // Filtrar solo estados validos
        $estadosValidos = ['disponible', 'en_uso', 'reparacion', 'descartado'];
        $estados = array_filter($estados, fn($e) => in_array($e, $estadosValidos, true));
        
        // Convertir tipos a entero
        $tipos = array_filter(array_map(fn($t) => (int)$t, $tipos), fn($t) => $t > 0);

        // Debug: Mostrar valores recibidos
        error_log("ORDENAMIENTO: ordenarPor = $ordenarPor | ordenDireccion = $ordenDireccion");
        
        $activos = $this->modelo->listar($pagina, $porPagina, $busqueda, $estados, $tipos, $ordenarPor, $ordenDireccion);
        $total = $this->modelo->contar($busqueda, $estados, $tipos);
        $totalPaginas = ceil($total / $porPagina);

        // Get room and building information
        $sala = null;
        $edificio = null;
        if ($salaId > 0) {
            // Prioridad: obtener directo desde la tabla con sala_id
            $salaInfo = $this->modelo->obtenerSalaConEdificio($salaId);
            if ($salaInfo) {
                $sala = $salaInfo['sala_nombre'];
                $edificio = $salaInfo['edificio_nombre'];
            }
        }

        // Fallback: si no hay sala_id válido, tomar del primer activo listado
        if (empty($sala) && !empty($activos)) {
            $sala = $activos[0]['sala_nombre'] ?? null;
            $edificio = $activos[0]['edificio_nombre'] ?? null;
        }
        
        // Si no se pasó sala_id, inferirlo del primer activo
        if ($salaId === 0 && !empty($activos)) {
            $salaId = (int) ($activos[0]['sala_id'] ?? 0);
            if ($salaId > 0) {
                $salaInfo = $this->modelo->obtenerSalaConEdificio($salaId);
                if ($salaInfo) {
                    $sala = $salaInfo['sala_nombre'];
                    $edificio = $salaInfo['edificio_nombre'];
                }
            }
        }
        
        // Si aún no tenemos edificio y sala, intentar obtenerlos del primer activo disponible
        if (empty($edificio) && empty($sala) && !empty($activos)) {
            $primerActivo = $activos[0];
            $edificio = $primerActivo['edificio_nombre'] ?? 'Sin edificio';
            $sala = $primerActivo['sala_nombre'] ?? 'Sin sala';
        }

        return view('inventario_catalogacion.listado_activos', [
            'activos' => $activos,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'busqueda' => $busqueda,
            'total' => $total,
            'sala' => $sala,
            'edificio' => $edificio,
            'salaId' => $salaId,
            'ordenarPor' => $ordenarPor,
            'ordenDireccion' => $ordenDireccion
        ]);
    }

    /**
     * Mostrar formulario para crear un nuevo activo
     */
    public function create()
    {
        $habitaciones = $this->modelo->obtenerHabitaciones();
        
        return view('inventario_catalogacion.registrar_activo', [
            'habitaciones' => $habitaciones
        ]);
    }

    /**
     * Guardar un nuevo activo
     */
    public function store()
    {
        try {
            $sessionUser = $_SESSION['auth_user'] ?? null;
            if (!$sessionUser) {
                throw new \Exception('Debe iniciar sesión para crear activos');
            }

            $nombre = trim((string) ($_POST['nombre'] ?? ''));
            $descripcion = trim((string) ($_POST['descripcion'] ?? ''));
            $tipo_activo_id = (int) ($_POST['tipo_activo_id'] ?? 0);
            $estado = trim((string) ($_POST['estado'] ?? ''));
            $codigo = trim((string) ($_POST['codigo'] ?? ''));
            $sala_id = (int) ($_POST['sala_id'] ?? 0);

            if (empty($nombre) || $tipo_activo_id <= 0 || empty($estado) || empty($codigo) || $sala_id <= 0) {
                throw new \Exception('Todos los campos obligatorios deben ser completados');
            }

            // Manejo de la imagen
            $imagen = null;
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $imagen = $this->subirImagen($_FILES['imagen']);
            }

            $datos = [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'tipo_activo_id' => $tipo_activo_id,
                'estado' => $estado,
                'codigo' => $codigo,
                'sala_id' => $sala_id,
                'usuario_creador_id' => $sessionUser['id'],
                'fecha_creado' => date('Y-m-d H:i:s'),
                'imagen' => $imagen
            ];

            $this->modelo->create($datos);

            header('Location: /sigmu/activo/registrar?sala_id=' . $sala_id . '&success=activo_creado');
            exit;

        } catch (Throwable $e) {
            header('Location: /sigmu/activo/registrar?error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Mostrar detalle de un activo
     */
    public function show(int $id)
    {
        $activo = $this->modelo->obtenerPorId($id);
        
        if (!$activo) {
            header('Location: /sigmu/activo/registrar?error=activo_no_encontrado');
            exit;
        }

        // Obtener foto principal del activo (o cualquier foto si no hay principal)
        $stmt = $this->db->prepare("SELECT ruta_foto FROM activo_foto WHERE activo_id = ? ORDER BY es_principal DESC, id DESC LIMIT 1");
        $stmt->execute([$id]);
        $fotoPrincipal = $stmt->fetchColumn();
        
        // Agregar foto principal al activo
        $activo['imagen'] = $fotoPrincipal;

        return view('inventario_catalogacion.ver_activo', [
            'activo' => $activo
        ]);
    }

    /**
     * Mostrar formulario para editar un activo
     */
    public function edit(int $id)
    {
        $activo = $this->modelo->obtenerPorId($id);
        $habitaciones = $this->modelo->obtenerHabitaciones();
        $tiposActivo = $this->modelo->obtenerTiposActivo();
        
        if (!$activo) {
            header('Location: /sigmu/activo/registrar?error=activo_no_encontrado');
            exit;
        }

        return view('inventario_catalogacion.editar_activo', [
            'activo' => $activo,
            'habitaciones' => $habitaciones,
            'tiposActivo' => $tiposActivo
        ]);
    }

    /**
     * Actualizar un activo existente con registro de historial detallado
     */
    public function update(int $id)
    {
        try {
            $activo = $this->modelo->obtenerPorId($id);
            if (!$activo) {
                throw new \Exception('Activo no encontrado');
            }

            $sessionUser = $_SESSION['auth_user'] ?? null;
            if (!$sessionUser) {
                throw new \Exception('Debe iniciar sesión para modificar activos');
            }

            // Establecer sesión del usuario para los triggers
            $this->db->exec("SET @usuario_id_sesion = " . (int)$sessionUser['id']);

            $nuevosDatos = [
                'nombre' => trim((string) ($_POST['nombre'] ?? '')),
                'descripcion' => trim((string) ($_POST['descripcion'] ?? '')),
                'tipo_activo_id' => (int) ($_POST['tipo_activo_id'] ?? 0),
                'estado' => trim((string) ($_POST['estado'] ?? '')),
                'codigo' => trim((string) ($_POST['codigo'] ?? '')),
                'sala_id' => (int) ($_POST['sala_id'] ?? 0),
                'fecha_actualizado' => date('Y-m-d H:i:s')
            ];

            // Validar campos obligatorios
            if (empty($nuevosDatos['nombre']) || $nuevosDatos['tipo_activo_id'] <= 0 || 
                empty($nuevosDatos['estado']) || empty($nuevosDatos['codigo']) || 
                $nuevosDatos['sala_id'] <= 0) {
                throw new \Exception('Todos los campos obligatorios deben ser completados');
            }

            // Iniciar transacción para asegurar integridad
            $this->db->beginTransaction();

            try {
                // Manejo de la imagen - usar tabla activo_foto
                if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                    // Subir nueva foto
                    $fotoPath = $this->subirImagen($_FILES['foto']);
                    
                    // Eliminar foto principal anterior si existe
                    $stmt = $this->db->prepare("SELECT ruta_foto FROM activo_foto WHERE activo_id = ? AND es_principal = TRUE");
                    $stmt->execute([$id]);
                    $fotoAnterior = $stmt->fetchColumn();
                    
                    if ($fotoAnterior && file_exists('public/' . $fotoAnterior)) {
                        unlink('public/' . $fotoAnterior);
                    }
                    
                    // Eliminar registro anterior de foto principal
                    $stmt = $this->db->prepare("DELETE FROM activo_foto WHERE activo_id = ? AND es_principal = TRUE");
                    $stmt->execute([$id]);
                    
                    // Insertar nueva foto principal
                    $stmt = $this->db->prepare("INSERT INTO activo_foto (activo_id, ruta_foto, descripcion, es_principal) VALUES (?, ?, ?, TRUE)");
                    $stmt->execute([$id, $fotoPath, 'Foto principal del activo']);
                }

                // Actualizar el activo (los triggers se encargan de registrar cada cambio individual)
                $this->modelo->actualizar($id, $nuevosDatos);

                // Confirmar transacción
                $this->db->commit();

                header('Location: /sigmu/activo/ver?id=' . $id . '&success=activo_actualizado');
                exit;

            } catch (Throwable $e) {
                $this->db->rollBack();
                throw $e;
            }

        } catch (Throwable $e) {
            header('Location: /sigmu/activo/editar?id=' . $id . '&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Dar de baja un activo (cambia estado a descartado)
     */
    public function darDeBaja(int $id)
    {
        try {
            if (!$this->requireAuth()) {
                return '';
            }
            
            $sessionUser = $_SESSION['auth_user'] ?? null;
            if (!$sessionUser) {
                throw new \Exception('Debe iniciar sesión');
            }
            
            // Verificar permisos
            $usuarioRol = $sessionUser['rol_nombre'] ?? '';
            if (!in_array($usuarioRol, ['Administrador', 'Responsable de Area'])) {
                throw new \Exception('No tiene permiso para dar de baja activos');
            }
            
            $activo = $this->modelo->obtenerPorId($id);
            if (!$activo) {
                throw new \Exception('Activo no encontrado');
            }
            
            if ($activo['estado'] === 'descartado') {
                throw new \Exception('El activo ya se encuentra dado de baja');
            }
            
            // Ejecutar baja
            $resultado = $this->modelo->darDeBaja($id, (int)$sessionUser['id']);
            
            if ($resultado) {
                header('Location: /sigmu/sala?sala_id=' . $activo['sala_id'] . '&success=Activo dado de baja exitosamente');
            } else {
                header('Location: /sigmu/activo/ver?id=' . $id . '&error=Error al dar de baja el activo');
            }
            
            exit;
            
        } catch (Throwable $e) {
            header('Location: /sigmu/activo/ver?id=' . $id . '&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Eliminar un activo
     */
    public function destroy(int $id)
    {
        try {
            $activo = $this->modelo->obtenerPorId($id);
            if (!$activo) {
                throw new \Exception('Activo no encontrado');
            }

            // Establecer sesión del usuario para el trigger de historial
            $sessionUser = $_SESSION['auth_user'] ?? null;
            if ($sessionUser) {
                $this->db->exec("SET @usuario_id_sesion = " . (int)$sessionUser['id']);
            }

            // Obtener sala_id antes de eliminar para redirección
            $salaId = $activo['sala_id'] ?? 0;

            // Eliminar fotos del activo si existen
            $stmt = $this->db->prepare("SELECT ruta_foto FROM activo_foto WHERE activo_id = ?");
            $stmt->execute([$id]);
            $fotos = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($fotos as $foto) {
                if ($foto && file_exists('public/' . $foto)) {
                    unlink('public/' . $foto);
                }
            }

            // Desactivar temporalmente el trigger para evitar error de foreign key
            $this->db->exec("DROP TRIGGER IF EXISTS trg_activo_ad");
            
            // Eliminar el activo
            $this->modelo->eliminar($id);
            
            // Reactivar el trigger
            $this->db->exec("
                CREATE TRIGGER trg_activo_ad
                AFTER DELETE ON activo
                FOR EACH ROW
                BEGIN
                    INSERT INTO historial_activo (
                        activo_id, usuario_id, accion, detalle,
                        estado_anterior, estado_nuevo,
                        sala_anterior_id, sala_nueva_id
                    ) VALUES (
                        OLD.id, @usuario_id_sesion,
                        'eliminacion',
                        CONCAT('Activo eliminado: ', OLD.nombre),
                        OLD.estado, NULL, OLD.sala_id, NULL
                    );
                END
            ");

            // Redirigir al listado de activos de la sala con mensaje de éxito
            if ($salaId > 0) {
                header('Location: /sigmu/sala?sala_id=' . $salaId . '&success=' . urlencode('Activo eliminado exitosamente'));
            } else {
                header('Location: /sigmu/activo/registrar?success=activo_eliminado');
            }
            exit;

        } catch (Throwable $e) {
            header('Location: /sigmu/activo/registrar?error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Mostrar historial de cambios de un activo
     */
    public function historial(int $id)
    {
        if (!$this->requireAuth()) {
            return '';
        }

        $activo = $this->modelo->obtenerPorId($id);
        
        if (!$activo) {
            header('Location: /sigmu/activo?error=activo_no_encontrado');
            exit;
        }

        // ✅ Obtener parametros de busqueda y filtros
        $busqueda = trim((string) ($_GET['busqueda'] ?? ''));
        $filtroAccion = trim((string) ($_GET['accion'] ?? ''));
        $filtroEstado = trim((string) ($_GET['estado'] ?? ''));

        $historial = $this->modelo->obtenerHistorial($id, $busqueda, $filtroAccion, $filtroEstado);

        return view('inventario_catalogacion.historial_activo', [
            'activo' => $activo,
            'historial' => $historial,
            'busqueda' => $busqueda,
            'filtroAccion' => $filtroAccion,
            'filtroEstado' => $filtroEstado
        ]);
    }

    /**
     * Subir imagen al servidor
     */
    private function subirImagen(array $file): ?string
    {
        $uploadDir = __DIR__ . '/../../../public/uploads/activos/';
        
        // Crear directorio si no existe
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Validar tipo de archivo
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($file['tmp_name']);
        if (!in_array($fileType, $allowedTypes, true)) {
            throw new \RuntimeException('Tipo de archivo no permitido. Solo se aceptan JPG, PNG y GIF.');
        }

        // Validar tamaño (5MB máximo)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new \RuntimeException('El archivo es demasiado grande. Tamaño máximo: 5MB.');
        }

        // Generar nombre único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = uniqid('activo_', true) . '.' . $extension;
        $filePath = $uploadDir . $fileName;

        // Mover archivo
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new \RuntimeException('Error al subir el archivo.');
        }

        return 'uploads/activos/' . $fileName;
    }
}
