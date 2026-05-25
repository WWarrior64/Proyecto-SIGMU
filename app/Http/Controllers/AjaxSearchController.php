<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Activo;
use App\Support\Database;
use App\Support\Session;
use App\Support\Roles;
use PDO;
use Throwable;

final class AjaxSearchController
{
    private readonly Activo $activoModelo;
    private $db;

    public function __construct()
    {
        $this->activoModelo = new Activo();
        $this->db = Database::connection();
    }

    /**
     * Búsqueda AJAX de activos en una sala
     */
    public function activos(): string
    {
        header('Content-Type: application/json');
        
        if (!$this->requireAuth()) {
            return json_encode(['success' => false, 'message' => 'No autorizado']);
        }

        try {
            $salaId = (int) ($_GET['sala_id'] ?? 0);
            $pagina = (int) ($_GET['pagina'] ?? 1);
            $busqueda = trim((string) ($_GET['busqueda'] ?? ''));
            $porPagina = 50;
            $ordenarPor = trim((string) ($_GET['ordenar_por'] ?? 'id'));
            $ordenDireccion = trim((string) ($_GET['orden_direccion'] ?? 'DESC'));
            
            $estados = (array)($_GET['estados'] ?? []);
            $tipos = array_filter(array_map('intval', (array)($_GET['tipos'] ?? [])));
            
            $activos = $this->activoModelo->listar($pagina, $porPagina, $busqueda, $estados, $tipos, $salaId, $ordenarPor, $ordenDireccion);
            $total = $this->activoModelo->contar($busqueda, $estados, $tipos, $salaId);
            $totalPaginas = (int) ceil($total / $porPagina);

            // Renderizar el partial de las filas
            $htmlRows = view('partials.activos_table_rows', [
                'activos' => $activos,
                'salaId' => $salaId
            ]);

            // Renderizar el partial de la paginación
            $htmlPagination = view('partials.pagination_ajax', [
                'items' => $activos,
                'total' => $total,
                'pagina' => $pagina,
                'totalPaginas' => $totalPaginas,
                'label' => 'activos',
                'ajaxClass' => 'ajax-page'
            ]);

            return json_encode([
                'success' => true,
                'htmlRows' => $htmlRows,
                'htmlPagination' => $htmlPagination,
                'total' => $total,
                'pagina' => $pagina,
                'totalPaginas' => $totalPaginas
            ]);
        } catch (Throwable $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Búsqueda AJAX de historial (General o Individual)
     */
    public function historial(): string
    {
        header('Content-Type: application/json');
        
        if (!$this->requireAuth()) {
            return json_encode(['success' => false, 'message' => 'No autorizado']);
        }

        try {
            $activoId = (int) ($_GET['id'] ?? 0);
            $pagina = (int) ($_GET['pagina'] ?? 1);
            $busqueda = trim((string) ($_GET['busqueda'] ?? ''));
            $porPagina = 50;
            $filtroAccion = trim((string) ($_GET['accion'] ?? ''));
            $filtroEstado = trim((string) ($_GET['estado'] ?? ''));
            $filtroUsuario = (int) ($_GET['usuario'] ?? 0);
            $ordenarPor = trim((string) ($_GET['ordenar_por'] ?? 'fecha'));
            $ordenDireccion = trim((string) ($_GET['orden_direccion'] ?? 'DESC'));

            if ($activoId > 0) {
                // Historial Individual
                $historial = $this->activoModelo->obtenerHistorial($activoId, $busqueda, $filtroAccion, $filtroEstado, $pagina, $porPagina, $ordenarPor, $ordenDireccion);
                $total = $this->activoModelo->contarHistorial($activoId, $busqueda, $filtroAccion, $filtroEstado);
            } else {
                // Historial General
                $historial = $this->obtenerHistorialGeneral($pagina, $porPagina, $busqueda, $filtroAccion, $filtroEstado, $filtroUsuario, $ordenarPor, $ordenDireccion);
                $total = $this->contarHistorialGeneral($busqueda, $filtroAccion, $filtroEstado, $filtroUsuario);
            }

            $totalPaginas = (int) ceil($total / $porPagina);

            $htmlRows = view('partials.historial_table_rows', [
                'historial' => $historial,
                'general' => ($activoId === 0)
            ]);

            $htmlPagination = view('partials.pagination_ajax', [
                'items' => $historial,
                'total' => $total,
                'pagina' => $pagina,
                'totalPaginas' => $totalPaginas,
                'label' => 'registros',
                'ajaxClass' => 'ajax-page-historial'
            ]);

            return json_encode([
                'success' => true,
                'htmlRows' => $htmlRows,
                'htmlPagination' => $htmlPagination,
                'total' => $total,
                'pagina' => $pagina
            ]);
        } catch (Throwable $e) {
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function obtenerHistorialGeneral($pagina, $porPagina, $busqueda, $accion, $estado, $usuarioId, $ordenarPor, $ordenDireccion): array
    {
        $offset = ($pagina - 1) * $porPagina;
        $params = [];
        
        $usuarioSesion = $_SESSION['auth_user'] ?? [];
        $esAdministrador = isset($usuarioSesion['rol_id']) && Roles::is($usuarioSesion['rol_id'], Roles::ADMIN);
        $userIdSesion = (int)($usuarioSesion['id'] ?? 0);

        // Jurisdicción
        $edificiosAccesiblesIds = [];
        if (!$esAdministrador) {
            $stmtEdificios = $this->db->prepare("
                SELECT edificio_id FROM usuario_edificio WHERE usuario_id = ?
                UNION
                SELECT id FROM edificio WHERE 1 = (SELECT ver_todo FROM rol WHERE id = ?)
            ");
            $stmtEdificios->execute([$userIdSesion, $usuarioSesion['rol_id'] ?? 0]);
            $edificiosAccesiblesIds = $stmtEdificios->fetchAll(PDO::FETCH_COLUMN);
        }

        $sql = "SELECT h.*, u.nombre_completo as usuario_nombre, u.username as usuario_username,
                       sa.nombre as sala_anterior_nombre, sn.nombre as sala_nueva_nombre,
                       a.codigo as activo_codigo, a.nombre as activo_nombre
                FROM historial_activo h
                LEFT JOIN usuario u ON h.usuario_id = u.id
                LEFT JOIN sala sa ON h.sala_anterior_id = sa.id
                LEFT JOIN sala sn ON h.sala_nueva_id = sn.id
                LEFT JOIN activo a ON h.activo_id = a.id
                WHERE 1=1";

        if (!$esAdministrador) {
            if (empty($edificiosAccesiblesIds)) {
                $sql .= " AND h.usuario_id = ?";
                $params[] = $userIdSesion;
            } else {
                $placeholders = implode(',', array_fill(0, count($edificiosAccesiblesIds), '?'));
                $sql .= " AND (h.usuario_id = ? OR sa.edificio_id IN ($placeholders) OR sn.edificio_id IN ($placeholders))";
                $params = array_merge($params, [$userIdSesion], $edificiosAccesiblesIds, $edificiosAccesiblesIds);
            }
        }

        if (!empty($busqueda)) {
            $sql .= " AND (h.detalle LIKE ? OR a.nombre LIKE ? OR a.codigo LIKE ? OR u.nombre_completo LIKE ? OR h.accion LIKE ? OR h.estado_nuevo LIKE ? OR sn.nombre LIKE ?)";
            $b = "%$busqueda%";
            $params = array_merge($params, [$b, $b, $b, $b, $b, $b, $b]);
        }
        if (!empty($accion)) {
            $sql .= " AND h.accion = ?";
            $params[] = $accion;
        }
        if (!empty($estado)) {
            $sql .= " AND (h.estado_anterior = ? OR h.estado_nuevo = ?)";
            $params = array_merge($params, [$estado, $estado]);
        }
        if ($esAdministrador && $usuarioId > 0) {
            $sql .= " AND h.usuario_id = ?";
            $params[] = $usuarioId;
        }

        $camposMap = [
            'usuario_nombre' => 'u.nombre_completo',
            'activo_codigo' => 'a.codigo',
            'fecha' => 'h.fecha',
            'id' => 'h.id',
            'accion' => 'h.accion',
            'estado_nuevo' => 'h.estado_nuevo',
            'sala_anterior_nombre' => 'sa.nombre',
            'sala_nueva_nombre' => 'sn.nombre'
        ];
        $campoOrden = $camposMap[$ordenarPor] ?? 'h.fecha';
        $sql .= " ORDER BY $campoOrden $ordenDireccion, h.id DESC LIMIT ? OFFSET ?";
        $params[] = (int)$porPagina;
        $params[] = (int)$offset;

        $stmt = $this->db->prepare($sql);
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

        return $historial;
    }

    private function contarHistorialGeneral($busqueda, $accion, $estado, $usuarioId): int
    {
        $params = [];
        $usuarioSesion = $_SESSION['auth_user'] ?? [];
        $esAdministrador = isset($usuarioSesion['rol_id']) && Roles::is($usuarioSesion['rol_id'], Roles::ADMIN);
        $userIdSesion = (int)($usuarioSesion['id'] ?? 0);

        $edificiosAccesiblesIds = [];
        if (!$esAdministrador) {
            $stmtEdificios = $this->db->prepare("
                SELECT edificio_id FROM usuario_edificio WHERE usuario_id = ?
                UNION
                SELECT id FROM edificio WHERE 1 = (SELECT ver_todo FROM rol WHERE id = ?)
            ");
            $stmtEdificios->execute([$userIdSesion, $usuarioSesion['rol_id'] ?? 0]);
            $edificiosAccesiblesIds = $stmtEdificios->fetchAll(PDO::FETCH_COLUMN);
        }

        $sql = "SELECT COUNT(*) FROM historial_activo h
                LEFT JOIN activo a ON a.id = h.activo_id
                LEFT JOIN usuario u ON u.id = h.usuario_id
                LEFT JOIN sala sa ON sa.id = h.sala_anterior_id
                LEFT JOIN sala sn ON sn.id = h.sala_nueva_id
                WHERE 1=1";

        if (!$esAdministrador) {
            if (empty($edificiosAccesiblesIds)) {
                $sql .= " AND h.usuario_id = ?";
                $params[] = $userIdSesion;
            } else {
                $placeholders = implode(',', array_fill(0, count($edificiosAccesiblesIds), '?'));
                $sql .= " AND (h.usuario_id = ? OR sa.edificio_id IN ($placeholders) OR sn.edificio_id IN ($placeholders))";
                $params = array_merge($params, [$userIdSesion], $edificiosAccesiblesIds, $edificiosAccesiblesIds);
            }
        }

        if (!empty($busqueda)) {
            $sql .= " AND (h.detalle LIKE ? OR a.nombre LIKE ? OR a.codigo LIKE ? OR u.nombre_completo LIKE ? OR h.accion LIKE ? OR h.estado_nuevo LIKE ? OR sn.nombre LIKE ?)";
            $b = "%$busqueda%";
            $params = array_merge($params, [$b, $b, $b, $b, $b, $b, $b]);
        }
        if (!empty($accion)) {
            $sql .= " AND h.accion = ?";
            $params[] = $accion;
        }
        if (!empty($estado)) {
            $sql .= " AND (h.estado_anterior = ? OR h.estado_nuevo = ?)";
            $params = array_merge($params, [$estado, $estado]);
        }
        if ($esAdministrador && $usuarioId > 0) {
            $sql .= " AND h.usuario_id = ?";
            $params[] = $usuarioId;
        }

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k + 1, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    private function requireAuth(): bool
    {
        if (!Session::has('auth_user')) return false;
        $userId = (int)Session::get('auth_user')['id'];
        $this->db->exec("SET @usuario_id_sesion = $userId");
        return true;
    }
}