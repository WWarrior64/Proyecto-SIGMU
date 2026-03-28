<?php

namespace App\Models;

use App\Support\Database;

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
        $offset = ($pagina - 1) * $porPagina;
        
        $sql = "SELECT a.id, a.nombre, a.tipo, a.estado, a.codigo, a.habitacion_id, a.creado_por, a.fecha_creacion
                FROM activos a
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($busqueda)) {
            $sql .= " AND (a.nombre LIKE :busqueda OR a.codigo LIKE :busqueda OR a.tipo LIKE :busqueda)";
            $params[':busqueda'] = '%' . $busqueda . '%';
        }
        
        $sql .= " ORDER BY a.id DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $porPagina;
        $params[':offset'] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }

    /**
     * Contar total de activos para paginación
     */
    public function contar(string $busqueda = ''): int
    {
        $sql = "SELECT COUNT(*) as total FROM activos a WHERE 1=1";
        $params = [];
        
        if (!empty($busqueda)) {
            $sql .= " AND (a.nombre LIKE :busqueda OR a.codigo LIKE :busqueda OR a.tipo LIKE :busqueda)";
            $params[':busqueda'] = '%' . $busqueda . '%';
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return (int) $stmt->fetchColumn();
    }

    /**
     * Obtener un activo por su ID
     */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM activos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    /**
     * Crear un nuevo activo
     */
    public function crear(array $datos): int
    {
        $sql = "INSERT INTO activos (nombre, descripcion, tipo, estado, codigo, habitacion_id, creado_por, fecha_creacion, imagen)
                VALUES (:nombre, :descripcion, :tipo, :estado, :codigo, :habitacion_id, :creado_por, :fecha_creacion, :imagen)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre' => $datos['nombre'],
            ':descripcion' => $datos['descripcion'],
            ':tipo' => $datos['tipo'],
            ':estado' => $datos['estado'],
            ':codigo' => $datos['codigo'],
            ':habitacion_id' => $datos['habitacion_id'],
            ':creado_por' => $datos['creado_por'],
            ':fecha_creacion' => $datos['fecha_creacion'],
            ':imagen' => $datos['imagen'] ?? null
        ]);
        
        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualizar un activo existente
     */
    public function actualizar(int $id, array $datos): bool
    {
        $sql = "UPDATE activos SET nombre = :nombre, descripcion = :descripcion, tipo = :tipo, 
                estado = :estado, codigo = :codigo, habitacion_id = :habitacion_id, imagen = :imagen
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':nombre' => $datos['nombre'],
            ':descripcion' => $datos['descripcion'],
            ':tipo' => $datos['tipo'],
            ':estado' => $datos['estado'],
            ':codigo' => $datos['codigo'],
            ':habitacion_id' => $datos['habitacion_id'],
            ':imagen' => $datos['imagen'] ?? null
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
     * Obtener todas las habitaciones para el select
     */
    public function obtenerHabitaciones(): array
    {
        $stmt = $this->db->query("SELECT id, nombre FROM habitaciones ORDER BY nombre");
        return $stmt->fetchAll();
    }
}