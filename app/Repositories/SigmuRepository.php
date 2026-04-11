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
             FROM usuario u
             JOIN rol r ON r.id = u.rol_id
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
            'SELECT a.id, a.codigo, a.nombre, a.estado, a.sala_id, a.foto_principal,
                    COALESCE(ta.nombre, "Sin tipo") as tipo,
                    COALESCE(s.nombre, "Sin sala") as sala_nombre,
                    COALESCE(e.nombre, "Sin edificio") as edificio_nombre
             FROM vista_mis_activos a
             LEFT JOIN tipo_activo ta ON a.tipo_activo_id = ta.id
             LEFT JOIN sala s ON a.sala_id = s.id
             LEFT JOIN edificio e ON s.edificio_id = e.id
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
             JOIN edificio e ON e.id = s.edificio_id
             ORDER BY e.nombre, s.numero_piso, s.nombre'
        );

        return $stmt === false ? [] : $stmt->fetchAll();
    }

    /**
     * Genera un código automático para un nuevo activo basado en su nombre
     * Ejemplo: "Pupitre" -> "PPT-001", "Mesa" -> "MSA-001"
     */
    public function generarCodigoActivo(string $nombreActivo = ''): string
    {
        if (empty($nombreActivo)) {
            // Fallback: código genérico si no hay nombre
            $stmt = $this->db->query(
                'SELECT MAX(CAST(SUBSTRING(codigo, 5) AS UNSIGNED)) as ultimo_num 
                 FROM activo 
                 WHERE codigo LIKE "ACT-%"'
            );
            $result = $stmt->fetch();
            $ultimoNumero = $result ? (int) $result['ultimo_num'] : 0;
            $siguienteNumero = $ultimoNumero + 1;
            return 'ACT-' . str_pad((string) $siguienteNumero, 3, '0', STR_PAD_LEFT);
        }

        // Generar prefijo basado en el nombre del activo
        $prefijo = $this->generarPrefijoDesdeNombre($nombreActivo);
        
        // Buscar el último código con este prefijo
        $stmt = $this->db->prepare(
            'SELECT MAX(CAST(SUBSTRING(codigo, :longitud_prefijo + 2) AS UNSIGNED)) as ultimo_num 
             FROM activo 
             WHERE codigo LIKE :patron'
        );
        $stmt->execute([
            'longitud_prefijo' => strlen($prefijo),
            'patron' => $prefijo . '-%'
        ]);
        
        $result = $stmt->fetch();
        $ultimoNumero = $result ? (int) ($result['ultimo_num'] ?? 0) : 0;
        
        // Generar siguiente código
        $siguienteNumero = $ultimoNumero + 1;
        return $prefijo . '-' . str_pad((string) $siguienteNumero, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Genera un prefijo de 3 letras basado en el nombre del activo
     * Ejemplos: "Pupitre" -> "PPT", "Mesa" -> "MSA", "Silla de oficina" -> "SDO"
     */
    private function generarPrefijoDesdeNombre(string $nombre): string
    {
        // Limpiar y normalizar el nombre
        $nombre = trim($nombre);
        $nombre = strtoupper($nombre);
        
        // Remover acentos y caracteres especiales
        $nombre = $this->removerAcentos($nombre);
        
        // Si el nombre tiene una sola palabra, tomar las primeras 3 letras
        $palabras = preg_split('/\s+/', $nombre, -1, PREG_SPLIT_NO_EMPTY);
        
        if (count($palabras) === 1) {
            // Una sola palabra: tomar primeras 3 letras
            return substr($palabras[0], 0, 3);
        } elseif (count($palabras) === 2) {
            // Dos palabras: primera letra de cada palabra + segunda letra de la primera
            return substr($palabras[0], 0, 2) . substr($palabras[1], 0, 1);
        } else {
            // Tres o más palabras: primera letra de las primeras 3 palabras
            return substr($palabras[0], 0, 1) . substr($palabras[1], 0, 1) . substr($palabras[2], 0, 1);
        }
    }

    /**
     * Remueve acentos de una cadena
     */
    private function removerAcentos(string $texto): string
    {
        $acentos = [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Ñ' => 'N', 'ñ' => 'n',
        ];
        return strtr($texto, $acentos);
    }

    /**
     * Verifica si ya existe un activo con el código dado
     */
    public function existeCodigoActivo(string $codigo): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM activo WHERE codigo = :codigo'
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
            'CALL sp_agregar_foto_activo(:activo_id, :ruta, :descripcion, :es_principal)'
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

    /**
     * @return array<string, mixed>|null
     */
    public function usuarioIdPorLogin(string $login): ?array
    {
        // Consulta rápida (solo id/activo) para recuperación de contraseña.
        $stmt = $this->db->prepare(
            'SELECT id, activo
             FROM usuario
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
            'INSERT INTO password_reset_token (usuario_id, token_hash, expires_at)
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
             FROM password_reset_token
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
            'UPDATE usuario u
             JOIN password_reset_token prt ON prt.usuario_id = u.id
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
