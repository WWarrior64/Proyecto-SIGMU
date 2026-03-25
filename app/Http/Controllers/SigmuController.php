<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SigmuService;
use Throwable;

// Este controlador es el "puente" entre el navegador y el sistema.
// Aquí solo recibimos inputs (GET/POST), llamamos al servicio y devolvemos vistas.
final class SigmuController
{
    public function __construct(
        private readonly SigmuService $service = new SigmuService()
    ) {
    }

    public function dashboard(): string
    {
        // Si hay sesión, cargamos el panel. Si no, mostramos login.
        $error = null;
        $sessionUser = $this->getSessionUser();
        $edificios = [];

        if ($sessionUser) {
            try {
                // Sincronizamos la sesión de BD para que las vistas restringidas funcionen.
                $this->syncDatabaseSession();
                $edificios = $this->service->obtenerMisEdificios();
            } catch (Throwable $exception) {
                $error = $exception->getMessage();
            }
        }

        if (!$sessionUser) {
            // Vista: login con identidad UNICAES.
            return view('administracion_usuarios.login', [
                'error' => $error,
            ]);
        }

        // Vista: panel de edificios accesibles para el usuario.
        return view('localizacion_asignacion.panel_edificios', [
            'sessionUser' => $sessionUser,
            'edificios' => $edificios,
            'error' => $error,
        ]);
    }

    public function login(): void
    {
        // Recibimos credenciales. Aceptamos username o email (mismo input).
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            header('Location: /sigmu?error=credenciales_requeridas');
            return;
        }

        try {
            // Validación contra la tabla usuarios + contraseña hash.
            $user = $this->service->autenticar($username, $password);
            $userId = (int) $user['id'];

            // Esto setea @usuario_id_sesion en MySQL, que usan tus vistas/fns.
            $this->service->iniciarSesionBd($userId);

            // Guardamos lo mínimo en la sesión para conocer al usuario y su rol.
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
            // Si algo falla, limpiamos la sesión y volvemos al login con mensaje.
            unset($_SESSION['auth_user']);
            header('Location: /sigmu?error=' . urlencode($exception->getMessage()));
            return;
        }
    }

    public function logout(): void
    {
        try {
            if (isset($_SESSION['auth_user']['id'])) {
                // También limpiamos la "sesión" en MySQL.
                $this->service->cerrarSesionBd();
            }
        } catch (Throwable) {
            // Ignored on logout path
        }

        // Limpiamos completamente la sesión PHP.
        $_SESSION = [];
        session_destroy();
        header('Location: /sigmu');
    }

    public function recuperarPasswordGet(): string
    {
        // Pantalla inicial de recuperación.
        $debugLocal = $this->debugLocal();

        return view('administracion_usuarios.recuperar_password', [
            'message' => '',
            'error' => '',
            'debugToken' => $debugLocal ? '' : '',
        ]);
    }

    public function recuperarPasswordPost(): string
    {
        // Pedimos el usuario/email para generar el token.
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

    public function resetPasswordGet(): string
    {
        // Esta pantalla llega con token por querystring.
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

    public function resetPasswordPost(): string
    {
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

    public function salasPorEdificio(): string
    {
        // Pantalla: lista de salas dentro de un edificio.
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
        // Pantalla: lista de activos dentro de una sala.
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

    private function syncDatabaseSession(): void
    {
        // Sin esto, tus vistas (vista_mis_*) no "saben" qué usuario está navegando.
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
        // Guard simple: si no hay usuario, regresamos al login.
        $user = $this->getSessionUser();
        if (!$user || empty($user['id'])) {
            header('Location: /sigmu?error=debes_iniciar_sesion');
            return false;
        }

        // Si sí hay usuario, sincronizamos la sesión de BD y seguimos.
        $this->syncDatabaseSession();
        return true;
    }

    private function debugLocal(): bool
    {
        // En local mostramos el token en pantalla (para probar sin SMTP).
        $appDebug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL);
        $appEnv = (string) ($_ENV['APP_ENV'] ?? 'local');
        return $appDebug || $appEnv === 'local';
    }
}
