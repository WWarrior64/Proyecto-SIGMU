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
        try {
            $stmt = $this->db->prepare('CALL set_usuario_sesion(:user_id)');
            $stmt->execute(['user_id' => $userId]);
            $stmt->closeCursor();
        } catch (\Throwable $e) {
            // No romper toda la pagina si falla la sesion BD
            error_log('Error al iniciar sesion BD: ' . $e->getMessage());
        }
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
        // Usar vista_fotos_edificio en lugar de edificio_foto (acceso restringido)
        $stmt = $this->db->query(
            "SELECT vme.*, ef.ruta_foto as foto,
                    (SELECT COUNT(*) FROM activo a JOIN sala s ON s.id = a.sala_id WHERE s.edificio_id = vme.id AND a.estado != 'descartado') as total_activos,
                    (SELECT u.nombre_completo FROM usuario_edificio ue JOIN usuario u ON u.id = ue.usuario_id WHERE ue.edificio_id = vme.id LIMIT 1) as responsable_nombre
             FROM vista_mis_edificios vme
             LEFT JOIN vista_fotos_edificio ef ON ef.edificio_id = vme.id
             ORDER BY vme.nombre"
        );

        return $stmt === false ? [] : $stmt->fetchAll();
    }

    /**
     * Catálogo completo de edificios (sin filtro usuario_edificio).
     * Necesario para Personal Mantenimiento al reportar fallas: suele no tener edificios asignados.
     *
     * @return array<int, array<string, mixed>>
     */
    public function catalogoEdificios(): array
    {
        $stmt = $this->db->query(
            'SELECT e.*, ef.ruta_foto as foto,
                    (SELECT COUNT(*) FROM activo a JOIN sala s ON s.id = a.sala_id WHERE s.edificio_id = e.id AND a.estado != "descartado") as total_activos,
                    (SELECT u.nombre_completo FROM usuario_edificio ue JOIN usuario u ON u.id = ue.usuario_id WHERE ue.edificio_id = e.id LIMIT 1) as responsable_nombre
             FROM edificio e
             LEFT JOIN vista_fotos_edificio ef ON ef.edificio_id = e.id
             ORDER BY e.nombre'
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
     * Salas de un edificio sin filtrar por asignación de usuario.
     *
     * @return array<int, array<string, mixed>>
     */
    public function catalogoSalasPorEdificio(int $edificioId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, edificio_id, nombre, descripcion, numero_piso
             FROM sala
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
        // Activos de la sala. Usar vistas en lugar de tablas base.
        $stmt = $this->db->prepare(
            'SELECT a.id, a.codigo, a.nombre, a.valor_adquisicion, a.estado, a.sala_id, a.foto_principal,
                    COALESCE(ta.nombre, "Sin tipo") as tipo,
                    COALESCE(s.nombre, "Sin sala") as sala_nombre,
                    COALESCE(e.nombre, "Sin edificio") as edificio_nombre
             FROM vista_mis_activos a
             LEFT JOIN vista_tipos_activo ta ON a.tipo_activo_id = ta.id
             LEFT JOIN vista_mis_salas s ON a.sala_id = s.id
             LEFT JOIN vista_mis_edificios e ON s.edificio_id = e.id
             WHERE a.sala_id = :sala_id
             ORDER BY a.nombre'
        );
        $stmt->execute(['sala_id' => $salaId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Activos de una sala sin filtrar por asignación de usuario.
     *
     * @return array<int, array<string, mixed>>
     */
    public function catalogoActivosPorSala(int $salaId, ?int $edificioId = null): array
    {
        // Nota: en BD v2, foto_principal no existe en tabla activo (solo en vista_mis_activos).
        // El formulario de reporte solo necesita id, codigo, nombre, estado.
        $sql = 'SELECT a.id, a.codigo, a.nombre, a.estado, a.sala_id,
                    COALESCE(ta.nombre, \'Sin tipo\') as tipo,
                    COALESCE(s.nombre, \'Sin sala\') as sala_nombre,
                    COALESCE(e.nombre, \'Sin edificio\') as edificio_nombre
             FROM activo a
             INNER JOIN sala s ON s.id = a.sala_id
             LEFT JOIN tipo_activo ta ON a.tipo_activo_id = ta.id
             LEFT JOIN edificio e ON s.edificio_id = e.id
             WHERE a.sala_id = :sala_id';

        $params = ['sala_id' => $salaId];

        if ($edificioId !== null && $edificioId > 0) {
            $sql .= ' AND s.edificio_id = :edificio_id';
            $params['edificio_id'] = $edificioId;
        }

        $sql .= ' ORDER BY a.nombre';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

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
     * @deprecated Usar generarCodigoCompleto() en su lugar
     */
    public function generarCodigoActivo(string $nombreActivo = ''): string
    {
        if (empty($nombreActivo)) {
            // Fallback: código genérico si no hay nombre
            $stmt = $this->db->query(
                'SELECT MAX(CAST(SUBSTRING(codigo, LOCATE("-", codigo) + 1) AS UNSIGNED)) as ultimo_num 
                 FROM activo 
                 WHERE codigo LIKE "ACT-%"'
            );
            $result = $stmt->fetch();
            $ultimoNumero = $result ? (int) $result['ultimo_num'] : 0;
            $siguienteNumero = $ultimoNumero + 1;
            $year = date('y');
            return 'ACTI-TIP-' . str_pad((string) $siguienteNumero, 3, '0', STR_PAD_LEFT) . '-' . $year;
        }

        // Generar prefijo basado en el nombre del activo
        $prefijo = $this->generarPrefijoDesdeNombre($nombreActivo, 3);
        
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
        $year = date('y');
        return $prefijo . '-' . str_pad((string) $siguienteNumero, 3, '0', STR_PAD_LEFT) . '-' . $year;
    }

    /**
     * Genera abreviatura de 4 caracteres desde el nombre del activo
     * Ejemplos: "Escritorio" -> "ESCT", "Computadora" -> "COMP", "Silla Ejecutiva" -> "SILL", "Estante Dexión" -> "ESTA"
     */
    public function generarAbreviaturaNombre(string $nombre): string
    {
        return $this->generarPrefijoDesdeNombre($nombre, 4);
    }

    /**
     * Genera abreviatura de 3 caracteres desde el tipo de activo
     * Ejemplos: "Mobiliario" -> "MOB", "Tecnología" -> "TEC", "Audio/Video" -> "AUD", "Equipo" -> "EQU"
     */
    public function generarAbreviaturaTipo(string $tipoNombre): string
    {
        return $this->generarPrefijoDesdeNombre($tipoNombre, 3);
    }

    /**
     * Obtiene el siguiente correlativo para un código de cuenta dado y genera el código completo
     * Formato: [CODIGO_CUENTA]-[CORRELATIVO(3)]-[AÑO(2)]
     * 
     * @return array{correlativo: string, year: string, codigo_completo: string}
     */
    public function generarCodigoCompleto(string $codigoCuenta): array
    {
        $year = date('y');
        // Buscar códigos que coincidan con el formato *[CUENTA]-[XXX]-[YY]*
        // Los asteriscos en la BD se escapan con LIKE: [ = escape para literal
        $patron = '*%' . $codigoCuenta . '-%-' . $year . '*';
        
        $stmt = $this->db->prepare(
            'SELECT codigo FROM activo WHERE codigo LIKE :patron ORDER BY codigo DESC LIMIT 1'
        );
        $stmt->execute(['patron' => $patron]);
        $ultimo = $stmt->fetchColumn();
        
        $ultimoNumero = 0;
        if ($ultimo) {
            // Extraer el correlativo: formato *[CUENTA]-[XXX]-[YY]*
            // Quitar asteriscos externos
            $codigoLimpio = trim($ultimo, '*');
            $partes = explode('-', $codigoLimpio);
            if (count($partes) >= 2) {
                // El penúltimo elemento es el correlativo
                $ultimoNumero = (int)($partes[count($partes) - 2] ?? 0);
            }
        }
        
        $siguienteNumero = $ultimoNumero + 1;
        $correlativo = str_pad((string) $siguienteNumero, 3, '0', STR_PAD_LEFT);
        $codigoCompleto = $codigoCuenta . '-' . $correlativo . '-' . $year;
        
        return [
            'correlativo' => $correlativo,
            'year' => $year,
            'codigo_completo' => $codigoCompleto,
        ];
    }

    /**
     * Genera un prefijo de N letras combinando todas las palabras del nombre.
     * Distribuye las letras entre las palabras según la cantidad de palabras:
     * - 1 palabra: 4 letras de esa palabra   (Ej: "Escritorio" -> "ESCR")
     * - 2 palabras: 2 de 1ra + 2 de 2da     (Ej: "Silla Ejecutiva" -> "SIEJ")
     * - 3 palabras: 1 + 1 + 2               (Ej: "Laptop HP Core" -> "LHC O") (1+1+2) -> "LHCO"
     * - 4+ palabras: 1 de cada una           (Ej: "Mesa de Centro" -> "MDCE" si 4 palabras)
     * Para length=3 (tipo):
     * - 1 palabra: 3 letras
     * - 2 palabras: 2 + 1
     * - 3+ palabras: 1 + 1 + 1
     * @param int $length Largo del prefijo (3 o 4 normalmente)
     */
    private function generarPrefijoDesdeNombre(string $nombre, int $length = 4): string
    {
        // Limpiar y normalizar el nombre
        $nombre = trim($nombre);
        $nombre = strtoupper($nombre);
        
        // Remover acentos y caracteres especiales
        $nombre = $this->removerAcentos($nombre);
        
        $palabras = preg_split('/\s+/', $nombre, -1, PREG_SPLIT_NO_EMPTY);
        $cantidad = count($palabras);
        
        if ($cantidad === 0) return '';
        
        if ($cantidad === 1) {
            // 1 palabra: primeras N letras
            return substr($palabras[0], 0, $length);
        }
        
        if ($cantidad === 2) {
            // 2 palabras: distribuir equitativamente
            // length=4 → 2+2, length=3 → 2+1
            $mitad = (int)ceil($length / 2);
            $resultado = substr($palabras[0], 0, $mitad);
            $restante = $length - strlen($resultado);
            $resultado .= substr($palabras[1], 0, $restante);
            return $resultado;
        }
        
        // 3 o más palabras: 1 letra de cada una, el resto va a la última palabra
        $resultado = '';
        for ($i = 0; $i < $cantidad && $i < $length; $i++) {
            $resultado .= substr($palabras[$i], 0, 1);
        }
        
        // Si faltan letras (porque length > cantPalabras), completar con la última palabra
        $faltantes = $length - strlen($resultado);
        if ($faltantes > 0 && $cantidad > 0) {
            $yaUsadas = 1; // ya usamos 1 letra de cada palabra
            $resultado .= substr($palabras[$cantidad - 1], $yaUsadas, $faltantes);
        }
        
        return $resultado;
    }

    /**
     * Obtiene el nombre de un tipo de activo por su ID
     */
    public function obtenerNombreTipoActivo(int $tipoId): string
    {
        $stmt = $this->db->prepare('SELECT nombre FROM tipo_activo WHERE id = :id');
        $stmt->execute(['id' => $tipoId]);
        $nombre = $stmt->fetchColumn();
        return $nombre !== false ? (string) $nombre : '';
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
        ?float $valorAdquisicion,
        string $estado,
        int $salaId,
        ?string $fechaCreado = null
    ): int {
        $stmt = $this->db->prepare(
            'CALL sp_registrar_activo(:codigo, :nombre, :tipo_id, :descripcion, :valor_adquisicion, :estado, :sala_id, :fecha_creado)'
        );
        
        $stmt->execute([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'tipo_id' => $tipoActivoId,
            'descripcion' => $descripcion,
            'valor_adquisicion' => $valorAdquisicion,
            'estado' => $estado,
            'sala_id' => $salaId,
            'fecha_creado' => $fechaCreado
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
    public function obtenerFotoActivoPrincipal(int $activoId): ?array
    {
        $stmt = $this->db->prepare("SELECT id, ruta_foto FROM activo_foto WHERE activo_id = ? AND es_principal = 1 ORDER BY id DESC LIMIT 1");
        $stmt->execute([$activoId]);
        
        $foto = $stmt->fetch();
        return is_array($foto) ? $foto : null;
    }

    public function eliminarFotoActivo(int $fotoId): bool
    {
        // 1. Obtener la ruta y el activo antes de borrar
        $stmt = $this->db->prepare("SELECT activo_id, ruta_foto FROM activo_foto WHERE id = ?");
        $stmt->execute([$fotoId]);
        $fotoInfo = $stmt->fetch();
        
        if (!$fotoInfo) return false;
        
        $activoId = (int)$fotoInfo['activo_id'];
        $rutaFoto = $fotoInfo['ruta_foto'];

        // 2. Ejecutar el procedimiento almacenado (borra de DB y reasigna principal si aplica)
        $stmt = $this->db->prepare("CALL sp_eliminar_foto_activo(:foto_id)");
        $stmt->execute(['foto_id' => $fotoId]);
        $result = $stmt->fetch();
        $stmt->closeCursor();

        // 3. Registrar en Historial
        $this->registrarHistorialInterno($activoId, 'modificacion', "Se eliminó la foto: " . basename($rutaFoto));

        // 4. Eliminar archivo físico
        if ($rutaFoto) {
            $rutaCompleta = __DIR__ . '/../../public/' . ltrim($rutaFoto, '/');
            if (file_exists($rutaCompleta)) {
                unlink($rutaCompleta);
            }
        }

        return isset($result['filas_eliminadas']) && $result['filas_eliminadas'] > 0;
    }

    /**
     * Obtiene todas las fotos de un activo
     */
    public function obtenerFotosActivo(int $activoId): array
    {
        $stmt = $this->db->prepare("SELECT id, ruta_foto, descripcion, es_principal FROM activo_foto WHERE activo_id = ? ORDER BY es_principal DESC, id DESC");
        $stmt->execute([$activoId]);
        return $stmt->fetchAll();
    }

    public function agregarFotoActivo(
        int $activoId,
        string $rutaFoto,
        string $descripcion = '',
        bool $esPrincipal = false
    ): int {
        // Si es principal, desmarcar la anterior
        if ($esPrincipal) {
            $stmt = $this->db->prepare("UPDATE activo_foto SET es_principal = 0 WHERE activo_id = ?");
            $stmt->execute([$activoId]);
        }

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

        $nuevaFotoId = (int) ($result['nueva_foto_id'] ?? 0);
        
        if ($nuevaFotoId > 0) {
            $detalle = "Se agregó una nueva foto" . ($esPrincipal ? " (marcada como principal)" : "");
            $this->registrarHistorialInterno($activoId, 'modificacion', $detalle);
        }

        return $nuevaFotoId;
    }

    /**
     * Obtiene la foto de un edificio
     */
    public function obtenerFotoEdificio(int $edificioId): ?array
    {
        $stmt = $this->db->prepare("SELECT id, ruta_foto FROM edificio_foto WHERE edificio_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$edificioId]);
        $foto = $stmt->fetch();
        return is_array($foto) ? $foto : null;
    }

    /**
     * Agrega una foto a un edificio
     */
    public function agregarFotoEdificio(int $edificioId, string $rutaFoto, string $descripcion = ''): int
    {
        // El SP sp_agregar_foto_edificio no limpia anteriores, lo hacemos manual si queremos solo una
        $fotoAnterior = $this->obtenerFotoEdificio($edificioId);
        if ($fotoAnterior) {
            $this->eliminarFotoEdificio((int)$fotoAnterior['id']);
        }

        $stmt = $this->db->prepare("CALL sp_agregar_foto_edificio(:edificio_id, :ruta, :descripcion)");
        $stmt->execute([
            'edificio_id' => $edificioId,
            'ruta' => $rutaFoto,
            'descripcion' => $descripcion
        ]);
        
        $result = $stmt->fetch();
        $stmt->closeCursor();
        
        return (int) ($result['nueva_foto_id'] ?? 0);
    }

    /**
     * Elimina una foto de edificio
     */
    public function eliminarFotoEdificio(int $fotoId): bool
    {
        // 1. Obtener ruta para borrar archivo
        $stmt = $this->db->prepare("SELECT ruta_foto FROM edificio_foto WHERE id = ?");
        $stmt->execute([$fotoId]);
        $rutaFoto = $stmt->fetchColumn();

        // 2. Ejecutar el procedimiento almacenado
        $stmt = $this->db->prepare("CALL sp_eliminar_foto_edificio(:foto_id)");
        $stmt->execute(['foto_id' => $fotoId]);
        $result = $stmt->fetch();
        $stmt->closeCursor();

        // 3. Eliminar archivo físico
        if ($rutaFoto) {
            $rutaCompleta = __DIR__ . '/../../public/' . ltrim($rutaFoto, '/');
            if (file_exists($rutaCompleta)) {
                unlink($rutaCompleta);
            }
        }
        
        return isset($result['filas_eliminadas']) && $result['filas_eliminadas'] > 0;
    }

    public function establecerPrincipalFotoActivo(int $fotoId): bool
    {
        // Obtener el activo_id de esta foto
        $stmt = $this->db->prepare("SELECT activo_id FROM activo_foto WHERE id = ?");
        $stmt->execute([$fotoId]);
        $fotoInfo = $stmt->fetch();

        if (!$fotoInfo) return false;
        
        $activoId = (int)$fotoInfo['activo_id'];

        // Desmarcar todas las fotos de este activo
        $stmt = $this->db->prepare("UPDATE activo_foto SET es_principal = 0 WHERE activo_id = ?");
        $stmt->execute([$activoId]);

        // Marcar la seleccionada como principal
        $stmt = $this->db->prepare("UPDATE activo_foto SET es_principal = 1 WHERE id = ?");
        $exito = $stmt->execute([$fotoId]);

        if ($exito) {
            $this->registrarHistorialInterno($activoId, 'modificacion', "Se cambió la foto principal del activo");
        }

        return $exito;
    }

    /**
     * Método interno para registrar historial manualmente cuando no hay un trigger directo
     */
    private function registrarHistorialInterno(int $activoId, string $accion, string $detalle): void
    {
        try {
            // Obtenemos el usuario de la sesión de base de datos (@usuario_id_sesion)
            $stmt = $this->db->prepare("
                INSERT INTO historial_activo (activo_id, usuario_id, accion, detalle)
                VALUES (:activo_id, IFNULL(@usuario_id_sesion, (SELECT id FROM usuario LIMIT 1)), :accion, :detalle)
            ");
            $stmt->execute([
                'activo_id' => $activoId,
                'accion' => $accion,
                'detalle' => $detalle
            ]);
        } catch (\Throwable $e) {
            error_log("Error al registrar historial manual: " . $e->getMessage());
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function usuarioIdPorLogin(string $login): ?array
    {
        // Consulta para recuperación de contraseña (incluye datos para el correo).
        $stmt = $this->db->prepare(
            'SELECT id, activo, email, nombre_completo
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

    /**
     * Obtiene todos los usuarios del sistema (vista administrador)
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodosUsuarios(): array
    {
        $stmt = $this->db->query(
            'SELECT 
                u.id,
                u.username,
                u.email,
                u.nombre_completo,
                u.rol_id,
                r.nombre AS rol_nombre,
                u.activo,
                u.fecha_creado
             FROM usuario u
             JOIN rol r ON r.id = u.rol_id
             ORDER BY u.nombre_completo'
        );

        return $stmt === false ? [] : $stmt->fetchAll();
    }

    public function registrarUsuario(string $username, string $email, string $passwordHash, string $nombreCompleto, int $rolId): int
    {
        $stmt = $this->db->prepare("CALL sp_registrar_usuario(:username, :email, :passhash, :nombre, :rol_id)");
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'passhash' => $passwordHash,
            'nombre' => $nombreCompleto,
            'rol_id' => $rolId
        ]);
        
        $result = $stmt->fetch();
        $stmt->closeCursor();
        
        return (int) $result['nuevo_usuario_id'] ?? 0;
    }

    public function editarUsuario(int $usuarioId, string $username, string $email, string $nombreCompleto, int $rolId, bool $activo): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE usuario SET username = :username, email = :email, nombre_completo = :nombre, rol_id = :rol_id, activo = :activo
             WHERE id = :id"
        );
        
        return $stmt->execute([
            'id' => $usuarioId,
            'username' => $username,
            'email' => $email,
            'nombre' => $nombreCompleto,
            'rol_id' => $rolId,
            'activo' => $activo ? 1 : 0
        ]);
    }

    public function cambiarEstadoUsuario(int $usuarioId, bool $activo): bool
    {
        $stmt = $this->db->prepare("UPDATE usuario SET activo = :activo WHERE id = :id");
        $stmt->execute([
            'id' => $usuarioId,
            'activo' => $activo
        ]);
        
        return $stmt->rowCount() > 0;
    }

    public function obtenerFotoUsuario(int $usuarioId): ?array
    {
        $stmt = $this->db->prepare("SELECT id, ruta_foto FROM usuario_foto WHERE usuario_id = :usuario_id ORDER BY id DESC LIMIT 1");
        $stmt->execute(['usuario_id' => $usuarioId]);
        
        $foto = $stmt->fetch();
        return is_array($foto) ? $foto : null;
    }

    public function eliminarFotoUsuario(int $fotoId): bool
    {
        $stmt = $this->db->prepare("CALL sp_eliminar_foto_usuario(:foto_id)");
        $stmt->execute(['foto_id' => $fotoId]);
        
        $result = $stmt->fetch();
        $stmt->closeCursor();
        
        return isset($result['filas_eliminadas']) && $result['filas_eliminadas'] > 0;
    }

    public function agregarFotoUsuario(int $usuarioId, string $rutaFoto, string $descripcion): int
    {
        // Eliminar fotos anteriores antes de agregar la nueva
        $fotoAnterior = $this->obtenerFotoUsuario($usuarioId);
        if ($fotoAnterior) {
            $this->eliminarFotoUsuario($fotoAnterior['id']);
            
            // Eliminar archivo fisico del servidor
            $rutaCompleta = __DIR__ . '/../../public' . $fotoAnterior['ruta_foto'];
            if (file_exists($rutaCompleta)) {
                unlink($rutaCompleta);
            }
        }

        $stmt = $this->db->prepare("CALL sp_agregar_foto_usuario(:usuario_id, :ruta, :descripcion)");
        $stmt->execute([
            'usuario_id' => $usuarioId,
            'ruta' => $rutaFoto,
            'descripcion' => $descripcion
        ]);
        
        $result = $stmt->fetch();
        $stmt->closeCursor();
        
        return (int) $result['nueva_foto_id'] ?? 0;
    }

    /**
     * Obtiene un usuario por su ID
     * @return array<string, mixed>|null
     */
    public function obtenerUsuarioPorId(int $usuarioId): ?array
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
                u.activo,
                u.fecha_creado
             FROM usuario u
             JOIN rol r ON r.id = u.rol_id
             WHERE u.id = :id
             LIMIT 1'
        );
        
        $stmt->execute(['id' => $usuarioId]);
        $usuario = $stmt->fetch();
        
        return is_array($usuario) ? $usuario : null;
    }

    /**
     * Obtiene todos los roles del sistema
     * @return array<int, array<string, mixed>>
     */
    public function obtenerRoles(): array
    {
        $stmt = $this->db->query('SELECT id, nombre, descripcion, ver_todo FROM rol ORDER BY id');
        return $stmt === false ? [] : $stmt->fetchAll();
    }

    public function obtenerRolPorId(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, nombre, descripcion, ver_todo FROM rol WHERE id = ?');
        $stmt->execute([$id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public function guardarRol(int $id, string $nombre, string $descripcion, bool $verTodo): int
    {
        if ($id > 0) {
            $stmt = $this->db->prepare('UPDATE rol SET nombre = ?, descripcion = ?, ver_todo = ? WHERE id = ?');
            $stmt->execute([$nombre, $descripcion, $verTodo ? 1 : 0, $id]);
            return $id;
        } else {
            $stmt = $this->db->prepare('INSERT INTO rol (nombre, descripcion, ver_todo) VALUES (?, ?, ?)');
            $stmt->execute([$nombre, $descripcion, $verTodo ? 1 : 0]);
            return (int)$this->db->lastInsertId();
        }
    }

    public function eliminarRol(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM rol WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Cambia la contraseña de un usuario
     */
    public function cambiarContrasena(int $usuarioId, string $passwordHash): bool
    {
        $stmt = $this->db->prepare("CALL sp_cambiar_contrasena(:id, :passhash)");
        $stmt->execute([
            'id' => $usuarioId,
            'passhash' => $passwordHash
        ]);
        
        $result = $stmt->fetch();
        $stmt->closeCursor();
        
        return isset($result['filas_afectadas']) && $result['filas_afectadas'] > 0;
    }

    /**
     * Permite a un usuario editar su propio perfil (nombre y email)
     */
    public function editarPerfil(int $usuarioId, string $email, string $nombreCompleto): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE usuario SET email = :email, nombre_completo = :nombre 
             WHERE id = :id"
        );
        
        return $stmt->execute([
            'id' => $usuarioId,
            'email' => $email,
            'nombre' => $nombreCompleto
        ]);
    }

    /**
     * Obtiene usuarios por el ID de su rol
     * @return array<int, array<string, mixed>>
     */
    public function obtenerUsuariosPorRolId(int $rolId): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id, u.username, u.nombre_completo, u.email
             FROM usuario u
             WHERE u.rol_id = :rol_id AND u.activo = 1
             ORDER BY u.nombre_completo'
        );
        $stmt->execute(['rol_id' => $rolId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los usuarios asignados a un edificio
     * @return array<int, array<string, mixed>>
     */
    public function obtenerUsuariosAsignadosAEdificio(int $edificioId): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id, u.username, u.nombre_completo
             FROM usuario_edificio ue
             JOIN usuario u ON u.id = ue.usuario_id
             WHERE ue.edificio_id = :edificio_id'
        );
        $stmt->execute(['edificio_id' => $edificioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene todas las asignaciones de edificios a usuarios
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodasAsignaciones(): array
    {
        $stmt = $this->db->query('SELECT * FROM vista_usuario_edificios ORDER BY nombre_completo, edificio_nombre');
        return $stmt === false ? [] : $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene edificios que no tienen ningún usuario asignado
     * @return array<int, array<string, mixed>>
     */
    public function obtenerEdificiosNoAsignados(): array
    {
        $sql = 'SELECT e.id, e.nombre 
                FROM edificio e 
                LEFT JOIN usuario_edificio ue ON e.id = ue.edificio_id 
                WHERE ue.edificio_id IS NULL 
                ORDER BY e.nombre';
        $stmt = $this->db->query($sql);
        return $stmt === false ? [] : $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Asigna un edificio a un usuario (usa el SP seguro)
     */
    public function asignarEdificioAUsuario(int $usuarioId, int $edificioId): bool
    {
        $stmt = $this->db->prepare('CALL sp_asignar_edificio(:usuario_id, :edificio_id)');
        $stmt->execute([
            'usuario_id' => $usuarioId,
            'edificio_id' => $edificioId
        ]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return isset($res['filas_afectadas']);
    }

    /**
     * Quita la asignación de un edificio a un usuario
     */
    public function quitarAsignacionEdificio(int $usuarioId, int $edificioId): bool
    {
        $stmt = $this->db->prepare('CALL sp_quitar_edificio(:usuario_id, :edificio_id)');
        $stmt->execute([
            'usuario_id' => $usuarioId,
            'edificio_id' => $edificioId
        ]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return isset($res['filas_afectadas']);
    }
}
