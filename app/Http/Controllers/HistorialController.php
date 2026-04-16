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
            // Obtener usuario de sesion
            $usuario = $_SESSION['auth_user'] ?? [];
            $esAdministrador = isset($usuario['rol_nombre']) && $usuario['rol_nombre'] === 'Administrador';

            // Obtener parametros de filtros
            $busqueda = trim((string) ($_GET['busqueda'] ?? ''));
            $filtroAccion = trim((string) ($_GET['accion'] ?? ''));
            $filtroEstado = trim((string) ($_GET['estado'] ?? ''));
            $filtroUsuario = filter_input(INPUT_GET, 'usuario', FILTER_VALIDATE_INT) ?: 0;

            // Construir consulta base usando la vista existente del sistema
            $sql = "SELECT
                vh.*,
                a.codigo AS activo_codigo,
                a.nombre AS activo_nombre,
                u.nombre_completo AS usuario_nombre,
                u.username AS usuario_username,
                sa.nombre AS sala_anterior_nombre,
                sn.nombre AS sala_nueva_nombre
            FROM vista_mis_historial vh
            JOIN activo a ON a.id = vh.activo_id
            JOIN usuario u ON u.id = vh.usuario_id
            LEFT JOIN sala sa ON sa.id = vh.sala_anterior_id
            LEFT JOIN sala sn ON sn.id = vh.sala_nueva_id
            WHERE 1=1";
            $params = [];

            // Aplicar filtros
            if (!empty($busqueda)) {
                $sql .= " AND (detalle LIKE ? OR activo_nombre LIKE ? OR activo_codigo LIKE ? OR usuario_nombre LIKE ?)";
                $busquedaParam = "%$busqueda%";
                $params[] = $busquedaParam;
                $params[] = $busquedaParam;
                $params[] = $busquedaParam;
                $params[] = $busquedaParam;
            }

            if (!empty($filtroAccion)) {
                $sql .= " AND accion = ?";
                $params[] = $filtroAccion;
            }

            if (!empty($filtroEstado)) {
                $sql .= " AND (estado_anterior = ? OR estado_nuevo = ?)";
                $params[] = $filtroEstado;
                $params[] = $filtroEstado;
            }

            // Filtro por usuario solo para administrador
            if ($esAdministrador && $filtroUsuario > 0) {
                $sql .= " AND usuario_id = ?";
                $params[] = $filtroUsuario;
            }

            // Ordenar por fecha descendente
            $sql .= " ORDER BY fecha DESC LIMIT 500";

            // Ejecutar consulta
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // ✅ Compatibilidad con registros antiguos: reemplazar IDs por nombres
            foreach ($historial as &$registro) {
                // Reemplazar IDs de sala por nombres en registros antiguos
                if (!empty($registro['detalle'])) {
                    // Reemplazar sala anterior
                    if (!empty($registro['sala_anterior_id']) && !empty($registro['sala_anterior_nombre'])) {
                        $registro['detalle'] = str_replace(
                            '"' . $registro['sala_anterior_id'] . '"',
                            '"' . $registro['sala_anterior_nombre'] . '"',
                            $registro['detalle']
                        );
                    }
                    // Reemplazar sala nueva
                    if (!empty($registro['sala_nueva_id']) && !empty($registro['sala_nueva_nombre'])) {
                        $registro['detalle'] = str_replace(
                            '"' . $registro['sala_nueva_id'] . '"',
                            '"' . $registro['sala_nueva_nombre'] . '"',
                            $registro['detalle']
                        );
                    }
                }
            }
            unset($registro);

            // Obtener lista de usuarios solo para administrador
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
                'filtroUsuario' => $filtroUsuario
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