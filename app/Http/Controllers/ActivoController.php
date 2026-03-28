<?php

namespace App\Http\Controllers;

use App\Models\Activo;
use App\Services\SigmuService;
use Throwable;

class ActivoController extends Controller
{
    private Activo $modelo;
    private SigmuService $sigmuService;

    public function __construct()
    {
        $this->modelo = new Activo();
        $this->sigmuService = new SigmuService();
    }

    /**
     * Mostrar listado de activos
     */
    public function index()
    {
        $pagina = (int) ($_GET['pagina'] ?? 1);
        $busqueda = trim((string) ($_GET['busqueda'] ?? ''));
        $porPagina = 10;

        $activos = $this->modelo->listar($pagina, $porPagina, $busqueda);
        $total = $this->modelo->contar($busqueda);
        $totalPaginas = ceil($total / $porPagina);

        return view('activos.index', [
            'activos' => $activos,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'busqueda' => $busqueda,
            'total' => $total
        ]);
    }

    /**
     * Mostrar formulario para crear un nuevo activo
     */
    public function create()
    {
        $habitaciones = $this->modelo->obtenerHabitaciones();
        
        return view('activos.create', [
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
            $tipo = trim((string) ($_POST['tipo'] ?? ''));
            $estado = trim((string) ($_POST['estado'] ?? ''));
            $codigo = trim((string) ($_POST['codigo'] ?? ''));
            $habitacion_id = (int) ($_POST['habitacion_id'] ?? 0);

            if (empty($nombre) || empty($tipo) || empty($estado) || empty($codigo) || $habitacion_id <= 0) {
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
                'tipo' => $tipo,
                'estado' => $estado,
                'codigo' => $codigo,
                'habitacion_id' => $habitacion_id,
                'creado_por' => $sessionUser['id'],
                'fecha_creacion' => date('Y-m-d H:i:s'),
                'imagen' => $imagen
            ];

            $this->modelo->crear($datos);

            header('Location: /activos?success=activo_creado');
            exit;

        } catch (Throwable $e) {
            header('Location: /activos/create?error=' . urlencode($e->getMessage()));
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
            header('Location: /activos?error=activo_no_encontrado');
            exit;
        }

        return view('activos.show', [
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
        
        if (!$activo) {
            header('Location: /activos?error=activo_no_encontrado');
            exit;
        }

        return view('activos.edit', [
            'activo' => $activo,
            'habitaciones' => $habitaciones
        ]);
    }

    /**
     * Actualizar un activo existente
     */
    public function update(int $id)
    {
        try {
            $activo = $this->modelo->obtenerPorId($id);
            if (!$activo) {
                throw new \Exception('Activo no encontrado');
            }

            $nombre = trim((string) ($_POST['nombre'] ?? ''));
            $descripcion = trim((string) ($_POST['descripcion'] ?? ''));
            $tipo = trim((string) ($_POST['tipo'] ?? ''));
            $estado = trim((string) ($_POST['estado'] ?? ''));
            $codigo = trim((string) ($_POST['codigo'] ?? ''));
            $habitacion_id = (int) ($_POST['habitacion_id'] ?? 0);

            if (empty($nombre) || empty($tipo) || empty($estado) || empty($codigo) || $habitacion_id <= 0) {
                throw new \Exception('Todos los campos obligatorios deben ser completados');
            }

            // Manejo de la imagen
            $imagen = $activo['imagen']; // Mantener la imagen existente por defecto
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                // Eliminar imagen anterior si existe
                if ($imagen && file_exists('storage/uploads/' . $imagen)) {
                    unlink('storage/uploads/' . $imagen);
                }
                $imagen = $this->subirImagen($_FILES['imagen']);
            }

            $datos = [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'tipo' => $tipo,
                'estado' => $estado,
                'codigo' => $codigo,
                'habitacion_id' => $habitacion_id,
                'imagen' => $imagen
            ];

            $this->modelo->actualizar($id, $datos);

            header('Location: /activos/' . $id . '?success=activo_actualizado');
            exit;

        } catch (Throwable $e) {
            header('Location: /activos/' . $id . '/edit?error=' . urlencode($e->getMessage()));
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

            // Eliminar imagen si existe
            if ($activo['imagen'] && file_exists('storage/uploads/' . $activo['imagen'])) {
                unlink('storage/uploads/' . $activo['imagen']);
            }

            $this->modelo->eliminar($id);

            header('Location: /activos?success=activo_eliminado');
            exit;

        } catch (Throwable $e) {
            header('Location: /activos?error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Subir imagen al servidor
     */
    private function subirImagen(array $file): ?string
    {
        $directorio = 'storage/uploads/';
        
        // Crear directorio si no existe
        if (!file_exists($directorio)) {
            mkdir($directorio, 0777, true);
        }

        $nombreArchivo = uniqid() . '_' . basename($file['name']);
        $rutaDestino = $directorio . $nombreArchivo;
        
        // Validar tipo de archivo
        $tiposPermitidos = ['jpg', 'jpeg', 'png', 'gif'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $tiposPermitidos)) {
            throw new \Exception('Tipo de archivo no permitido. Solo se permiten JPG, JPEG, PNG y GIF');
        }

        if (!move_uploaded_file($file['tmp_name'], $rutaDestino)) {
            throw new \Exception('Error al subir la imagen');
        }

        return $nombreArchivo;
    }
}