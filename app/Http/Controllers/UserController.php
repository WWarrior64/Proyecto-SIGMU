<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SigmuService;
use App\Support\Session;
use App\Support\Csrf;
use Throwable;

final class UserController
{
    private readonly SigmuService $service;

    public function __construct()
    {
        $this->service = new SigmuService();
    }

    /**
     * Muestra el perfil del usuario
     */
    public function perfil(): string
    {
        if (!$this->requireAuth()) {
            return '';
        }

        $userId = Session::get('auth_user')['id'];
        $usuario = $this->service->obtenerUsuarioPorId($userId);
        
        // Obtener foto de perfil
        $foto = $this->service->obtenerFotoUsuario($userId);
        if ($foto) {
            $usuario['foto'] = $foto['ruta_foto'];
        }

        return view('administracion_usuarios.perfil', [
            'usuario' => $usuario,
            'success' => $_GET['success'] ?? '',
            'error' => $_GET['error'] ?? ''
        ]);
    }

    /**
     * Procesa la actualización del perfil
     */
    public function actualizarPerfil(): void
    {
        if (!$this->requireAuth()) {
            return;
        }

        if (!Csrf::validate()) {
            header('Location: /sigmu/perfil?error=' . urlencode('Token CSRF inválido'));
            return;
        }

        $userId = Session::get('auth_user')['id'];
        $nombreCompleto = trim((string)($_POST['nombre_completo'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));

        try {
            if (empty($nombreCompleto) || empty($email)) {
                throw new \Exception('Todos los campos son obligatorios');
            }

            // Actualizar en BD
            $this->service->editarPerfil($userId, $email, $nombreCompleto);

            // Actualizar sesión
            $authUser = Session::get('auth_user');
            $authUser['nombre_completo'] = $nombreCompleto;
            $authUser['email'] = $email;
            Session::set('auth_user', $authUser);

            // Manejar subida de foto si existe
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $this->procesarFotoPerfil($userId);
            }

            header('Location: /sigmu/perfil?success=perfil_actualizado');
        } catch (Throwable $e) {
            header('Location: /sigmu/perfil?error=' . urlencode($e->getMessage()));
        }
    }

    private function procesarFotoPerfil(int $userId): void
    {
        $file = $_FILES['foto'];
        $uploadDir = __DIR__ . '/../../../public/img/usuarios/';
        
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($extension, $allowed)) {
            throw new \Exception('Formato de imagen no permitido');
        }

        $fileName = 'usuario_' . $userId . '_' . time() . '.' . $extension;
        $rutaCompleta = $uploadDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $rutaCompleta)) {
            $rutaDb = '/img/usuarios/' . $fileName;
            $this->service->agregarFotoUsuario($userId, $rutaDb, 'Foto de perfil');
            
            // Actualizar foto en sesión
            $authUser = Session::get('auth_user');
            $authUser['foto'] = $rutaDb;
            Session::set('auth_user', $authUser);
        }
    }

    private function requireAuth(): bool
    {
        if (!Session::has('auth_user')) {
            header('Location: /sigmu?error=debes_iniciar_sesion');
            return false;
        }
        return true;
    }
}
