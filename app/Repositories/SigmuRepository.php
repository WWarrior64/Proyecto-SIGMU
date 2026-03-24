<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use PDO;
use RuntimeException;

final class SigmuRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function setUsuarioSesion(int $userId): void
    {
        $stmt = $this->db->prepare('CALL set_usuario_sesion(:user_id)');
        $stmt->execute(['user_id' => $userId]);
        $stmt->closeCursor();
    }

    public function limpiarUsuarioSesion(): void
    {
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
        $stmt = $this->db->prepare(
            'SELECT id, codigo, nombre, estado, sala_id, foto_principal
             FROM vista_mis_activos
             WHERE sala_id = :sala_id
             ORDER BY nombre'
        );
        $stmt->execute(['sala_id' => $salaId]);

        return $stmt->fetchAll();
    }
}
