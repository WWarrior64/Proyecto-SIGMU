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
            'SELECT a.id, a.codigo, a.nombre, a.estado, a.sala_id, a.foto_principal,
                    COALESCE(ta.nombre, "Sin tipo") as tipo,
                    COALESCE(s.nombre, "Sin sala") as sala_nombre,
                    COALESCE(e.nombre, "Sin edificio") as edificio_nombre
             FROM vista_mis_activos a
             LEFT JOIN tipos_activo ta ON a.tipo_activo_id = ta.id
             LEFT JOIN salas s ON a.sala_id = s.id
             LEFT JOIN edificios e ON s.edificio_id = e.id
             WHERE a.sala_id = :sala_id
             ORDER BY a.nombre'
        );
        $stmt->execute(['sala_id' => $salaId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene todos los tipos de activo disponibles
     * @return array<int, array<string, mixed>>
     */
    public function typesActive(): array
    {
        $stmt = $this->db->query(
            'SELECT id, nombre FROM vista_tipos_activo ORDER BY nombre'
        );

        return $stmt === false ? [] : $stmt->fetchAll();
    }

    /**
     * Obtiene todas las salas accesibles para el usuario actual
     * @return array<int, array<string, mixed>>
     */
    public function todasLasSalas(): array
    {
        $stmt = $this->db->query(
            'SELECT s.id, s.nombre, s.descripcion, s.numero_piso, 
                    e.nombre AS edificio_nombre, s.edificio_id
             FROM vista_mis_salas s
             JOIN edificios e ON e.id = s.edificio_id
             ORDER BY e.nombre, s.numero_piso, s.nombre'
        );

        return $stmt === false ? [] : $stmt->fetchAll();
    }

    /**
     * Genera un código automático para un nuevo activo
     */
    public function generarCodigoActivo(): string
    {
        // Obtener el último número de código
        $stmt = $this->db->query(
            'SELECT MAX(CAST(SUBSTRING(codigo, 5) AS UNSIGNED)) as ultimo_num 
             FROM activos 
             WHERE codigo LIKE "ACT-%"'
        );
        
        $result = $stmt->fetch();
        $ultimoNumero = $result ? (int) $result['ultimo_num'] : 0;
        
        // Generar siguiente código
        $siguienteNumero = $ultimoNumero + 1;
        return 'ACT-' . str_pad((string) $siguienteNumero, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Verifica si ya existe un activo con el código dado
     */
    public function existeCodigoActivo(string $codigo): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM activos WHERE codigo = :codigo'
        );
        $stmt->execute(['codigo' => $codigo]);
        
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Registra un nuevo activo usando el procedimiento almacenado
     * @return int ID del activo registrado
     */
    public function registrarActivo(
        string $codigo,
        string $nombre,
        int $tipoActivoId,
        string $descripcion,
        string $estado,
        int $salaId
    ): int {
        $stmt = $this->db->prepare(
            'CALL sp_registrar_activo(:codigo, :nombre, :tipo_id, :descripcion, :estado, :sala_id)'
        );
        
        $stmt->execute([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'tipo_id' => $tipoActivoId,
            'descripcion' => $descripcion,
            'estado' => $estado,
            'sala_id' => $salaId,
        ]);

        $result = $stmt->fetch();
        $stmt->closeCursor();

        if (!$result || !isset($result['nuevo_activo_id'])) {
            throw new RuntimeException('No se pudo obtener el ID del activo registrado.');
        }

        return (int) $result['nuevo_activo_id'];
    }

    /**
     * Agrega una foto a un activo
     */
    public function agregarFotoActivo(
        int $activoId,
        string $rutaFoto,
        string $descripcion = '',
        bool $esPrincipal = false
    ): int {
        $stmt = $this->db->prepare(
            'CALL sp_agregar_foto(:activo_id, :ruta, :descripcion, :es_principal)'
        );
        
        $stmt->execute([
            'activo_id' => $activoId,
            'ruta' => $rutaFoto,
            'descripcion' => $descripcion,
            'es_principal' => $esPrincipal ? 1 : 0,
        ]);

        $result = $stmt->fetch();
        $stmt->closeCursor();

        if (!$result || !isset($result['nueva_foto_id'])) {
            throw new RuntimeException('Could not get logged photo ID.');
        }

        return (int) $result['nueva_foto_id'];
    }
}
