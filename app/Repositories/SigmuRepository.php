<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use PDO;
use RuntimeException;

// El repository se encarga de hablar con la base de datos.
// Idealmente aquí se queda el SQL, para no ensuciar controladores/vistas.
final class SigmuRepository
{
    private PDO $db;

    public function __construct()
    {
        // Conexión PDO compartida.
        $this->db = Database::connection();
    }

    public function setUsuarioSesion(int $userId): void
    {
        // En tu BD esto setea la variable @usuario_id_sesion (vía stored procedure).
        $stmt = $this->db->prepare('CALL set_usuario_sesion(:user_id)');
        $stmt->execute(['user_id' => $userId]);
        $stmt->closeCursor();
    }

    public function limpiarUsuarioSesion(): void
    {
        // Limpia @usuario_id_sesion para cerrar navegación segura.
        $stmt = $this->db->query('CALL limpiar_usuario_sesion()');
        if ($stmt !== false) {
            $stmt->closeCursor();
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function usuarioParaLogin(string $login): ?array
    {
        // Traemos usuario + rol para decidir permisos.
        $stmt = $this->db->prepare(
            'SELECT
                u.id,
                u.username,
                u.email,
                u.contrasena_hash,
                u.nombre_completo,
                u.rol_id,
                r.nombre AS rol_nombre,
                r.ver_todo,
                u.activo
             FROM usuarios u
             JOIN roles r ON r.id = u.rol_id
             WHERE u.username = :login OR u.email = :login
             LIMIT 1'
        );

        try {
            $stmt->execute(['login' => $login]);
            $user = $stmt->fetch();
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                'No fue posible validar usuario desde tabla usuarios. ' .
                'Verifica permisos SELECT de la cuenta DB actual.'
            );
        }

        return is_array($user) ? $user : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function misEdificios(): array
    {
        // Vista filtrada por usuario en sesión (fn_usuario_sesion / fn_tiene_acceso_edificio).
        $stmt = $this->db->query(
            'SELECT id, nombre, descripcion, cantidad_pisos, total_salas
             FROM vista_mis_edificios
             ORDER BY nombre'
        );

        return $stmt === false ? [] : $stmt->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function misSalasPorEdificio(int $edificioId): array
    {
        // Salas del edificio (solo si el usuario tiene acceso).
        $stmt = $this->db->prepare(
            'SELECT id, edificio_id, nombre, descripcion, numero_piso
             FROM vista_mis_salas
             WHERE edificio_id = :edificio_id
             ORDER BY numero_piso, nombre'
        );
        $stmt->execute(['edificio_id' => $edificioId]);

        return $stmt->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function misActivosPorSala(int $salaId): array
    {
        // Activos de la sala (solo si el usuario tiene acceso al edificio).
        $stmt = $this->db->prepare(
            'SELECT id, codigo, nombre, estado, sala_id, foto_principal
             FROM vista_mis_activos
             WHERE sala_id = :sala_id
             ORDER BY nombre'
        );
        $stmt->execute(['sala_id' => $salaId]);

        return $stmt->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function usuarioIdPorLogin(string $login): ?array
    {
        // Consulta rápida (solo id/activo) para recuperación de contraseña.
        $stmt = $this->db->prepare(
            'SELECT id, activo
             FROM usuarios
             WHERE username = :login OR email = :login
             LIMIT 1'
        );
        $stmt->execute(['login' => $login]);
        $user = $stmt->fetch();
        return is_array($user) ? $user : null;
    }

    /**
     * @return string token plano (solo para mostrar en modo debug)
     */
    public function crearTokenPasswordReset(int $usuarioId, int $expiresMinutes): string
    {
        // Generamos token plano y guardamos solo el hash (sha256) en BD.
        // Así, si alguien ve la tabla, no puede usar los tokens directamente.
        $tokenPlain = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $tokenPlain);

        $expiresAt = (new \DateTimeImmutable('now'))
            ->modify('+' . $expiresMinutes . ' minutes')
            ->format('Y-m-d H:i:s');

        // Insertamos el token con expiración.
        $stmt = $this->db->prepare(
            'INSERT INTO password_reset_tokens (usuario_id, token_hash, expires_at)
             VALUES (:usuario_id, :token_hash, :expires_at)'
        );
        $stmt->execute([
            'usuario_id' => $usuarioId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);

        return $tokenPlain;
    }

    public function tokenPasswordResetEsValido(string $tokenPlain): bool
    {
        // Validamos: existe, no usado y no expirado.
        $tokenHash = hash('sha256', $tokenPlain);
        $stmt = $this->db->prepare(
            'SELECT 1
             FROM password_reset_tokens
             WHERE token_hash = :token_hash
               AND used_at IS NULL
               AND expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute(['token_hash' => $tokenHash]);
        return (bool) $stmt->fetchColumn();
    }

    public function resetearContrasenaPorToken(string $tokenPlain, string $newPasswordHash): bool
    {
        // Actualizamos la contraseña y marcamos el token como usado en una sola query.
        $tokenHash = hash('sha256', $tokenPlain);

        $stmt = $this->db->prepare(
            'UPDATE usuarios u
             JOIN password_reset_tokens prt ON prt.usuario_id = u.id
             SET u.contrasena_hash = :new_hash,
                 prt.used_at = NOW()
             WHERE prt.token_hash = :token_hash
               AND prt.used_at IS NULL
               AND prt.expires_at > NOW()'
        );

        $stmt->execute([
            'new_hash' => $newPasswordHash,
            'token_hash' => $tokenHash,
        ]);

        return $stmt->rowCount() > 0;
    }
}
