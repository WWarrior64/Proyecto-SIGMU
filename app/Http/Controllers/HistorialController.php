<?php

namespace App\Http\Controllers;

use App\Support\Database;
use PDO;
use Throwable;

class HistorialController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * Muestra el historial general de cambios
     */
    public function index(): string
    {
        if (!$this->requireAuth()) {
            return '';
        }

        try {
            // Paginación y Ordenamiento
            $pagina = (int) ($_GET['pagina'] ?? 1);
            $porPagina = 50;
            $offset = ($pagina - 1) * $porPagina;

            $ordenarPor = trim((string) ($_GET['ordenar_por'] ?? 'fecha'));
            $ordenDireccion = strtoupper((string) ($_GET['orden_direccion'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

            // Validar campos de ordenamiento
            $camposPermitidos = ['id', 'fecha', 'accion', 'activo_codigo', 'usuario_nombre', 'sala_anterior_nombre', 'sala_nueva_nombre', 'estado_nuevo'];
            $ordenarPor = in_array($ordenarPor, $camposPermitidos) ? $ordenarPor : 'fecha';

            $camposMap = [
                'id' => 'h.id',
                'fecha' => 'h.fecha',
                'accion' => 'h.accion',
                'activo_codigo' => 'a.codigo',
                'usuario_nombre' => 'u.nombre_completo',
                'sala_anterior_nombre' => 'sa.nombre',
                'sala_nueva_nombre' => 'sn.nombre',
                'estado_nuevo' => 'h.estado_nuevo'
            ];
            $campoOrdenSql = $camposMap[$ordenarPor] ?? 'h.fecha';

            // Obtener usuario de sesion
            $usuario = $_SESSION['auth_user'] ?? [];
            $esAdministrador = isset($usuario['rol_id']) && \App\Support\Roles::is($usuario['rol_id'], \App\Support\Roles::ADMIN);
            $userId = (int)($usuario['id'] ?? 0);

            // Obtener parametros de filtros
            $busqueda = trim((string) ($_GET['busqueda'] ?? ''));
            $filtroAccion = trim((string) ($_GET['accion'] ?? ''));
            $filtroEstado = trim((string) ($_GET['estado'] ?? ''));
            $filtroUsuario = filter_input(INPUT_GET, 'usuario', FILTER_VALIDATE_INT) ?: 0;

            // Obtener IDs de edificios accesibles para el usuario (si no es admin)
            $edificiosAccesiblesIds = [];
            if (!$esAdministrador) {
                $stmtEdificios = $this->db->prepare("
                    SELECT edificio_id FROM usuario_edificio WHERE usuario_id = ?
                    UNION
                    SELECT id FROM edificio WHERE 1 = (SELECT ver_todo FROM rol WHERE id = ?)
                ");
                $stmtEdificios->execute([$userId, $usuario['rol_id'] ?? 0]);
                $edificiosAccesiblesIds = $stmtEdificios->fetchAll(PDO::FETCH_COLUMN);
            }

            // --- 1. CONTAR TOTAL PARA PAGINACIÓN ---
            $sqlCount = "SELECT COUNT(*) FROM historial_activo h
                        JOIN activo a ON a.id = h.activo_id
                        JOIN usuario u ON u.id = h.usuario_id
                        LEFT JOIN sala sa ON sa.id = h.sala_anterior_id
                        LEFT JOIN sala sn ON sn.id = h.sala_nueva_id
                        WHERE 1=1";
            $paramsCount = [];

            // Jurisdicción (Count)
            if (!$esAdministrador) {
                if (empty($edificiosAccesiblesIds)) {
                    $sqlCount .= " AND h.usuario_id = ?";
                    $paramsCount[] = $userId;
                } else {
                    $placeholders = implode(',', array_fill(0, count($edificiosAccesiblesIds), '?'));
                    $sqlCount .= " AND (h.usuario_id = ? OR sa.edificio_id IN ($placeholders) OR sn.edificio_id IN ($placeholders))";
                    $paramsCount = array_merge([$userId], $edificiosAccesiblesIds, $edificiosAccesiblesIds);
                }
            }

            // Filtros (Count)
            if (!empty($busqueda)) {
                $sqlCount .= " AND (h.detalle LIKE ? OR a.nombre LIKE ? OR a.codigo LIKE ? OR u.nombre_completo LIKE ?)";
                $b = "%$busqueda%";
                $paramsCount = array_merge($paramsCount, [$b, $b, $b, $b]);
            }
            if (!empty($filtroAccion)) {
                $sqlCount .= " AND accion = ?";
                $paramsCount[] = $filtroAccion;
            }
            if (!empty($filtroEstado)) {
                $sqlCount .= " AND (estado_anterior = ? OR estado_nuevo = ?)";
                $paramsCount = array_merge($paramsCount, [$filtroEstado, $filtroEstado]);
            }
            if ($esAdministrador && $filtroUsuario > 0) {
                $sqlCount .= " AND usuario_id = ?";
                $paramsCount[] = $filtroUsuario;
            }

            $stmtCount = $this->db->prepare($sqlCount);
            $stmtCount->execute($paramsCount);
            $total = (int) $stmtCount->fetchColumn();
            $totalPaginas = (int) ceil($total / $porPagina);

            // --- 2. CONSULTA PAGINADA ---
            $sql = "SELECT
                h.id, h.fecha, h.accion, h.detalle,
                h.estado_anterior, h.estado_nuevo,
                h.sala_anterior_id, h.sala_nueva_id,
                h.usuario_id, a.codigo AS activo_codigo,
                a.nombre AS activo_nombre, u.nombre_completo AS usuario_nombre,
                u.username AS usuario_username, sa.nombre AS sala_anterior_nombre,
                sn.nombre AS sala_nueva_nombre, sa.edificio_id AS edificio_anterior_id,
                sn.edificio_id AS edificio_nuevo_id
            FROM historial_activo h
            JOIN activo a ON a.id = h.activo_id
            JOIN usuario u ON u.id = h.usuario_id
            LEFT JOIN sala sa ON sa.id = h.sala_anterior_id
            LEFT JOIN sala sn ON sn.id = h.sala_nueva_id
            WHERE 1=1";
            $params = [];

            // Jurisdicción (Query)
            if (!$esAdministrador) {
                if (empty($edificiosAccesiblesIds)) {
                    $sql .= " AND h.usuario_id = ?";
                    $params[] = $userId;
                } else {
                    $placeholders = implode(',', array_fill(0, count($edificiosAccesiblesIds), '?'));
                    $sql .= " AND (h.usuario_id = ? OR sa.edificio_id IN ($placeholders) OR sn.edificio_id IN ($placeholders))";
                    $params = array_merge([$userId], $edificiosAccesiblesIds, $edificiosAccesiblesIds);
                }
            }

            // Filtros (Query)
            if (!empty($busqueda)) {
                $sql .= " AND (h.detalle LIKE ? OR a.nombre LIKE ? OR a.codigo LIKE ? OR u.nombre_completo LIKE ?)";
                $b = "%$busqueda%";
                $params = array_merge($params, [$b, $b, $b, $b]);
            }
            if (!empty($filtroAccion)) {
                $sql .= " AND accion = ?";
                $params[] = $filtroAccion;
            }
            if (!empty($filtroEstado)) {
                $sql .= " AND (estado_anterior = ? OR estado_nuevo = ?)";
                $params = array_merge($params, [$filtroEstado, $filtroEstado]);
            }
            if ($esAdministrador && $filtroUsuario > 0) {
                $sql .= " AND usuario_id = ?";
                $params[] = $filtroUsuario;
            }

            $sql .= " ORDER BY $campoOrdenSql $ordenDireccion, h.id DESC LIMIT ? OFFSET ?";
            $params[] = (int) $porPagina;
            $params[] = (int) $offset;

            $stmt = $this->db->prepare($sql);
            // Bind manual para asegurar tipos
            foreach ($params as $k => $v) {
                $stmt->bindValue($k + 1, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Compatibilidad con registros antiguos
            foreach ($historial as &$registro) {
                if (!empty($registro['detalle'])) {
                    if (!empty($registro['sala_anterior_id']) && !empty($registro['sala_anterior_nombre'])) {
                        $registro['detalle'] = str_replace('"' . $registro['sala_anterior_id'] . '"', '"' . $registro['sala_anterior_nombre'] . '"', $registro['detalle']);
                    }
                    if (!empty($registro['sala_nueva_id']) && !empty($registro['sala_nueva_nombre'])) {
                        $registro['detalle'] = str_replace('"' . $registro['sala_nueva_id'] . '"', '"' . $registro['sala_nueva_nombre'] . '"', $registro['detalle']);
                    }
                }
            }
            unset($registro);

            // Usuarios para filtro
            $usuarios = [];
            if ($esAdministrador) {
                $stmtUsuarios = $this->db->query("SELECT id, nombre_completo FROM usuario WHERE activo = 1 ORDER BY nombre_completo");
                $usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);
            }

            return view('reportes_consultas.historial_general', [
                'historial' => $historial,
                'usuarios' => $usuarios,
                'esAdministrador' => $esAdministrador,
                'busqueda' => $busqueda,
                'filtroAccion' => $filtroAccion,
                'filtroEstado' => $filtroEstado,
                'filtroUsuario' => $filtroUsuario,
                'pagina' => $pagina,
                'totalPaginas' => $totalPaginas,
                'total' => $total,
                'ordenarPor' => $ordenarPor,
                'ordenDireccion' => $ordenDireccion
            ]);

        } catch (Throwable $exception) {
            return '<h2>Error</h2><p>' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
        }
    }

    /**
     * Sincronizar sesion de base de datos
     */
    private function syncDatabaseSession(): void
    {
        $userId = $_SESSION['auth_user']['id'] ?? null;
        if (!empty($userId)) {
            $this->db->exec("SET @usuario_id_sesion = " . (int)$userId);
        }
    }

    /**
     * Verificar autenticacion
     */
    private function requireAuth(): bool
    {
        $user = $_SESSION['auth_user'] ?? null;
        if (!$user || empty($user['id'])) {
            header('Location: /sigmu?error=debes_iniciar_sesion');
            return false;
        }

        $this->syncDatabaseSession();
        return true;
    }
}