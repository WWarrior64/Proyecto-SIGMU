<?php

namespace App\Models;

use App\Support\Database;
use PDO;

class Activo
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * Listar todos los activos con paginación, búsqueda y filtros combinados
     * 
     * @param int $pagina Numero de pagina
     * @param int $porPagina Registros por pagina
     * @param string $busqueda Texto de busqueda
     * @param array $estados Array de estados a filtrar (vacio = todos)
     * @param array $tipos Array de tipos de activo a filtrar (vacio = todos)
     */
    public function listar(int $pagina = 1, int $porPagina = 10, string $busqueda = '', array $estados = [], array $tipos = [], string $ordenarPor = 'id', string $ordenDireccion = 'DESC'): array
    {
        try {
            $offset = ($pagina - 1) * $porPagina;
            
        // Validar campos de ordenamiento permitidos
        $camposPermitidos = ['id', 'codigo', 'nombre', 'tipo', 'estado', 'sala_nombre', 'fecha_creado'];
        $ordenarPor = in_array(strtolower($ordenarPor), $camposPermitidos) ? $ordenarPor : 'id';
        $ordenDireccion = strtoupper($ordenDireccion) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT a.id, a.nombre, COALESCE(ta.nombre, 'Sin tipo') as tipo, a.estado, a.codigo, a.sala_id, a.usuario_creador_id, a.fecha_creado,
                       COALESCE(s.nombre, 'Sin sala') as sala_nombre, COALESCE(e.nombre, 'Sin edificio') as edificio_nombre
                FROM activo a
                LEFT JOIN tipo_activo ta ON a.tipo_activo_id = ta.id
                LEFT JOIN sala s ON a.sala_id = s.id
                LEFT JOIN edificio e ON s.edificio_id = e.id
                WHERE 1=1";
            
            $params = [];
            
            // 🔍 Filtro de busqueda de texto
            if (!empty($busqueda)) {
                $sql .= " AND (a.nombre LIKE :busqueda OR a.codigo LIKE :busqueda OR ta.nombre LIKE :busqueda OR s.nombre LIKE :busqueda OR e.nombre LIKE :busqueda)";
                $params[':busqueda'] = '%' . $busqueda . '%';
            }
            
            // ✅ ✅ ✅ SE ELIMINA EL FILTRO SERVIDOR:
            // Ahora TODOS LOS ACTIVOS (incluidos descartados) se envian al cliente
            // El filtrado se hace 100% en el navegador con Javascript
            // Esto permite mostrar ocultar activos descartados sin recargar la pagina
            
            // 🎯 Filtro por TIPO DE ACTIVO (admite multiples valores al mismo tiempo)
            if (!empty($tipos) && is_array($tipos)) {
                $placeholders = [];
                foreach ($tipos as $idx => $tipoId) {
                    $key = ":tipo_{$idx}";
                    $placeholders[] = $key;
                    $params[$key] = (int)$tipoId;
                }
                $sql .= " AND a.tipo_activo_id IN (" . implode(',', $placeholders) . ")";
            }
            
        // Mapear nombres de campo frontend a nombres reales de tabla
        $camposMap = [
            'id' => 'a.id',
            'codigo' => 'a.codigo',
            'nombre' => 'a.nombre',
            'tipo' => 'ta.nombre',
            'estado' => 'a.estado',
            'sala_nombre' => 's.nombre',
            'fecha_creado' => 'a.fecha_creado'
        ];
        
        $campoOrden = $camposMap[$ordenarPor] ?? 'a.id';
        $sql .= " ORDER BY $campoOrden $ordenDireccion LIMIT :limit OFFSET :offset";
            $params[':limit'] = $porPagina;
            $params[':offset'] = $offset;
            
            $stmt = $this->db->prepare($sql);
            
            // Bind de parametros integer correctamente (evita error de tipo en MySQL)
            $stmt->bindParam(':limit', $porPagina, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            
            // Bind del resto de parametros
            foreach ($params as $key => $value) {
                if ($key !== ':limit' && $key !== ':offset') {
                    $stmt->bindValue($key, $value);
                }
            }
            
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error en Activo::listar: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar total de activos para paginación con filtros aplicados
     */
    public function contar(string $busqueda = '', array $estados = [], array $tipos = []): int
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM activo a LEFT JOIN tipo_activo ta ON a.tipo_activo_id = ta.id WHERE 1=1";
            $params = [];
            
            // 🔍 Filtro de busqueda de texto
            if (!empty($busqueda)) {
                $sql .= " AND (a.nombre LIKE :busqueda OR a.codigo LIKE :busqueda OR ta.nombre LIKE :busqueda)";
                $params[':busqueda'] = '%' . $busqueda . '%';
            }
            
            // 🎯 Filtro por ESTADO
            // ✅ MISMO COMPORTAMIENTO EN EL CONTADOR PARA PAGINACION
            if (!empty($estados) && is_array($estados)) {
                $placeholders = [];
                foreach ($estados as $idx => $estado) {
                    $key = ":estado_{$idx}";
                    $placeholders[] = $key;
                    $params[$key] = $estado;
                }
                $sql .= " AND a.estado IN (" . implode(',', $placeholders) . ")";
            } else {
                // Ningun filtro marcado: excluir solo descartado
                $sql .= " AND a.estado != 'descartado'";
            }
            
            // 🎯 Filtro por TIPO DE ACTIVO
            if (!empty($tipos) && is_array($tipos)) {
                $placeholders = [];
                foreach ($tipos as $idx => $tipoId) {
                    $key = ":tipo_{$idx}";
                    $placeholders[] = $key;
                    $params[$key] = (int)$tipoId;
                }
                $sql .= " AND a.tipo_activo_id IN (" . implode(',', $placeholders) . ")";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log("Error en Activo::contar: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener un activo por su ID
     */
    public function obtenerPorId(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT a.*, ta.nombre as tipo 
                 FROM activo a 
                 LEFT JOIN tipo_activo ta ON a.tipo_activo_id = ta.id 
                 WHERE a.id = :id"
            );
            $stmt->execute([':id' => $id]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado ?: null;
        } catch (\PDOException $e) {
            // Si la tabla no existe, retornar null
            return null;
        }
    }

    /**
     * Crear un nuevo activo
     */
    public function create(array $datos): int
    {
        $sql = "INSERT INTO activo (nombre, descripcion, tipo_activo_id, estado, codigo, sala_id, usuario_creador_id, fecha_creado)
                VALUES (:nombre, :descripcion, :tipo_activo_id, :estado, :codigo, :sala_id, :usuario_creador_id, :fecha_creado)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre'            => $datos['nombre'],
            ':descripcion'       => $datos['descripcion'],
            ':tipo_activo_id'    => $datos['tipo_activo_id'],
            ':estado'            => $datos['estado'],
            ':codigo'            => $datos['codigo'],
            ':sala_id'           => $datos['sala_id'],
            ':usuario_creador_id'=> $datos['usuario_creador_id'],
            ':fecha_creado'      => $datos['fecha_creado'],
        ]);
        
        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualizar un activo existente
     */
    public function actualizar(int $id, array $datos): bool
    {
        $sql = "UPDATE activo SET nombre = :nombre, descripcion = :descripcion, tipo_activo_id = :tipo_activo_id, 
                estado = :estado, codigo = :codigo, sala_id = :sala_id, fecha_actualizado = :fecha_actualizado
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':nombre' => $datos['nombre'],
            ':descripcion' => $datos['descripcion'] ?? '',
            ':tipo_activo_id' => $datos['tipo_activo_id'] ?? $datos['tipo'] ?? 1,
            ':estado' => $datos['estado'],
            ':codigo' => $datos['codigo'],
            ':sala_id' => $datos['sala_id'] ?? $datos['habitacion_id'] ?? 1,
            ':fecha_actualizado' => $datos['fecha_actualizado'] ?? date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Dar de baja un activo (cambia estado a descartado SIN eliminar)
     */
    public function darDeBaja(int $id, int $usuarioId): bool
    {
        try {
            $this->db->beginTransaction();
            
            // Establecer usuario de sesion para el trigger
            $this->db->exec("SET @usuario_id_sesion = " . (int)$usuarioId);
            
            // Actualizar estado
            $stmt = $this->db->prepare("UPDATE activo SET estado = 'descartado', fecha_actualizado = NOW() WHERE id = :id");
            $resultado = $stmt->execute([':id' => $id]);
            
            // Registrar en historial manualmente
            $stmtHistorial = $this->db->prepare("
                INSERT INTO historial_activo 
                (activo_id, usuario_id, accion, detalle, estado_anterior, estado_nuevo)
                SELECT id, ?, 'retiro', 'Activo dado de baja definitivamente', estado, 'descartado'
                FROM activo WHERE id = ?
            ");
            $stmtHistorial->execute([$usuarioId, $id]);
            
            $this->db->commit();
            
            return $resultado;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Error en darDeBaja: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar un activo
     */
    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM activo WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Obtener todas las salas para el select
     */
    public function obtenerHabitaciones(): array
    {
        try {
            $stmt = $this->db->query("SELECT id, nombre FROM sala ORDER BY nombre");
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            // Si la tabla no existe, retornar array vacío
            return [];
        }
    }

    /**
     * Obtener todos los tipos de activo para el select
     */
    public function obtenerTiposActivo(): array
    {
        try {
            $stmt = $this->db->query("SELECT id, nombre FROM tipo_activo ORDER BY nombre");
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            // Si la tabla no existe, retornar array vacío
            return [];
        }
    }

    /**
     * Obtener información de la sala con su edificio
     */
    public function obtenerSalaConEdificio(int $salaId): ?array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT s.id, s.nombre as sala_nombre, s.numero_piso, 
                        e.id as edificio_id, e.nombre as edificio_nombre
                 FROM sala s
                 LEFT JOIN edificio e ON s.edificio_id = e.id
                 WHERE s.id = :sala_id"
            );
            $stmt->execute([':sala_id' => $salaId]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado ?: null;
        } catch (\PDOException $e) {
            // Si la tabla no existe, retornar null
            return null;
        }
    }

    /**
     * Obtener historial de cambios de un activo con todos los campos + BUSQUEDA Y FILTROS
     */
    public function obtenerHistorial(int $activoId, string $busqueda = '', string $filtroAccion = '', string $filtroEstado = ''): array
    {
        try {
            $sql = "
                SELECT 
                    h.id, 
                    h.fecha, 
                    h.accion, 
                    h.detalle,
                    h.estado_anterior,
                    h.estado_nuevo,
                    h.sala_anterior_id,
                    h.sala_nueva_id,
                    u.nombre_completo as usuario_nombre,
                    u.username as usuario_username,
                    sa.nombre as sala_anterior_nombre,
                    sn.nombre as sala_nueva_nombre
                FROM historial_activo h
                LEFT JOIN usuario u ON h.usuario_id = u.id
                LEFT JOIN sala sa ON h.sala_anterior_id = sa.id
                LEFT JOIN sala sn ON h.sala_nueva_id = sn.id
                WHERE h.activo_id = :activo_id
            ";

            $params = [':activo_id' => $activoId];

            // ✅ Busqueda general
            if (!empty($busqueda)) {
                $sql .= " AND (
                    h.detalle LIKE :busqueda OR
                    h.accion LIKE :busqueda OR
                    h.estado_anterior LIKE :busqueda OR
                    h.estado_nuevo LIKE :busqueda OR
                    u.nombre_completo LIKE :busqueda OR
                    u.username LIKE :busqueda OR
                    sa.nombre LIKE :busqueda OR
                    sn.nombre LIKE :busqueda
                )";
                $params[':busqueda'] = '%' . $busqueda . '%';
            }

            // ✅ Filtro por Accion
            if (!empty($filtroAccion)) {
                $sql .= " AND h.accion = :accion";
                $params[':accion'] = $filtroAccion;
            }

            // ✅ Filtro por Estado
            if (!empty($filtroEstado)) {
                $sql .= " AND (h.estado_anterior = :estado OR h.estado_nuevo = :estado)";
                $params[':estado'] = $filtroEstado;
            }

            $sql .= " ORDER BY h.fecha DESC, h.id DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
    }
}
