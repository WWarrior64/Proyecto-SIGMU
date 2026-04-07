<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SigmuService;
use App\Support\Csrf;
use Throwable;

final class AuthController
{
    public function __construct(
        private readonly SigmuService $service = new SigmuService()
    ) {
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

    /**
     * Muestra el formulario para recuperar contraseña
     */
    public function recuperarPasswordForm(): string
    {
        $debugLocal = $this->debugLocal();

        return view('administracion_usuarios.recuperar_password', [
            'message' => '',
            'error' => '',
            'debugToken' => $debugLocal ? '' : '',
        ]);
    }

    /**
     * Procesa el formulario de recuperación de contraseña
     */
    public function recuperarPasswordPost(): string
    {
        $login = trim((string) ($_POST['login'] ?? ''));
        $debugLocal = $this->debugLocal();

        try {
            $result = $this->service->solicitarRecuperacionPassword($login, $debugLocal);
            $debugToken = isset($result['debugToken']) && is_string($result['debugToken']) ? $result['debugToken'] : '';
            $success = isset($result['success']) ? (bool) $result['success'] : false;
            $message = (string) ($result['message'] ?? '');

            return view('administracion_usuarios.recuperar_password', [
                'message' => $success ? $message : '',
                'error' => !$success ? $message : '',
                'debugToken' => ($success && $debugLocal) ? $debugToken : '',
            ]);
        } catch (Throwable $exception) {
            return view('administracion_usuarios.recuperar_password', [
                'message' => '',
                'error' => $exception->getMessage(),
                'debugToken' => '',
            ]);
        }
    }

    /**
     * Muestra el formulario para resetear contraseña con token
     */
    public function resetPasswordForm(): string
    {
        $token = (string) ($_GET['token'] ?? '');
        $token = trim($token);

        if ($token === '' || !$this->service->tokenPasswordResetValido($token)) {
            return view('administracion_usuarios.reset_password', [
                'token' => $token,
                'error' => 'El token no es válido o ha expirado.',
            ]);
        }

        return view('administracion_usuarios.reset_password', [
            'token' => $token,
            'error' => '',
        ]);
    }

    /**
     * Procesa el cambio de nueva contraseña
     */
    public function resetPasswordPost(): string
    {
        // Verificar token CSRF antes de procesar
        if (!Csrf::validate()) {
            return view('administracion_usuarios.reset_password', [
                'token' => '',
                'error' => 'Token CSRF inválido. Por favor, recarga la página e intenta de nuevo.',
            ]);
        }
        
        // Guardamos la nueva contraseña si el token es válido.
        $token = (string) ($_POST['token'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');

        try {
            $this->service->resetearPassword($token, $password, $confirmation);
            header('Location: /sigmu?reset_ok=1');
            return '';
        } catch (Throwable $exception) {
            return view('administracion_usuarios.reset_password', [
                'token' => $token,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function debugLocal(): bool
    {
        // En local mostramos el token en pantalla (para probar sin SMTP).
        $appDebug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL);
        $appEnv = (string) ($_ENV['APP_ENV'] ?? 'local');
        return $appDebug || $appEnv === 'local';
    }
}
