<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SigmuService;
use App\Support\Csrf;
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

    /**
     * Muestra el formulario para registrar un nuevo activo
     */
    public function registrarActivoGet(): string
    {
        if (!$this->requireAuth()) {
            return '';
        }

        try {
            $tiposActivo = $this->service->obtenerTiposActivo();
            
            // Generar código automático
            $codigoGenerado = $this->service->generarCodigoActivo();
            
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
            $resultado = $this->service->registrarActivo(
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
            $tiposActivo = $this->service->obtenerTiposActivo();
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
     * Procesa la subida de foto del activo
     */
    private function procesarFoto(array $file): string
    {
        $uploadDir = __DIR__ . '/../../../storage/uploads/activos/';
        
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

        return '/storage/uploads/activos/' . $fileName;
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
