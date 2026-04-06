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
     * Listar todos los activos con paginación y búsqueda
     */
    public function listar(int $pagina = 1, int $porPagina = 10, string $busqueda = ''): array
    {
        try {
            $offset = ($pagina - 1) * $porPagina;
            
            $sql = "SELECT a.id, a.nombre, COALESCE(ta.nombre, 'Sin tipo') as tipo, a.estado, a.codigo, a.sala_id, a.usuario_creador_id, a.fecha_creado,
                           COALESCE(s.nombre, 'Sin sala') as sala_nombre, COALESCE(e.nombre, 'Sin edificio') as edificio_nombre
                    FROM activos a
                    LEFT JOIN tipos_activo ta ON a.tipo_activo_id = ta.id
                    LEFT JOIN salas s ON a.sala_id = s.id
                    LEFT JOIN edificios e ON s.edificio_id = e.id
                    WHERE 1=1";
            
            $params = [];
            
            if (!empty($busqueda)) {
                $sql .= " AND (a.nombre LIKE :busqueda OR a.codigo LIKE :busqueda OR ta.nombre LIKE :busqueda OR s.nombre LIKE :busqueda OR e.nombre LIKE :busqueda)";
                $params[':busqueda'] = '%' . $busqueda . '%';
            }
            
            $sql .= " ORDER BY a.id DESC LIMIT :limit OFFSET :offset";
            $params[':limit'] = $porPagina;
            $params[':offset'] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            // Si la tabla no existe, retornar array vacío
            return [];
        }
    }

    /**
     * Contar total de activos para paginación
     */
    public function contar(string $busqueda = ''): int
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM activos a LEFT JOIN tipos_activo ta ON a.tipo_activo_id = ta.id WHERE 1=1";
            $params = [];
            
            if (!empty($busqueda)) {
                $sql .= " AND (a.nombre LIKE :busqueda OR a.codigo LIKE :busqueda OR ta.nombre LIKE :busqueda)";
                $params[':busqueda'] = '%' . $busqueda . '%';
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            // Si la tabla no existe, retornar 0
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
                 FROM activos a 
                 LEFT JOIN tipos_activo ta ON a.tipo_activo_id = ta.id 
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
        $sql = "INSERT INTO activos (nombre, descripcion, tipo_activo_id, estado, codigo, sala_id, usuario_creador_id, fecha_creado)
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
        $sql = "UPDATE activos SET nombre = :nombre, descripcion = :descripcion, tipo_activo_id = :tipo_activo_id, 
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
     * Eliminar un activo
     */
    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM activos WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Obtener todas las salas para el select
     */
    public function obtenerHabitaciones(): array
    {
        try {
            $stmt = $this->db->query("SELECT id, nombre FROM salas ORDER BY nombre");
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
            $stmt = $this->db->query("SELECT id, nombre FROM tipos_activo ORDER BY nombre");
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
                 FROM salas s
                 LEFT JOIN edificios e ON s.edificio_id = e.id
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
}
