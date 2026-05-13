<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use PDO;

final class ReporteRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * Obtiene datos completos de un activo para el reporte individual
     */
    public function obtenerDatosActivo(int $id): ?array
    {
        $sql = "SELECT a.*, ta.nombre as tipo_nombre, 
                       s.nombre as sala_nombre, e.nombre as edificio_nombre,
                       u.nombre_completo as usuario_creador_nombre
                FROM activo a
                LEFT JOIN tipo_activo ta ON a.tipo_activo_id = ta.id
                LEFT JOIN sala s ON a.sala_id = s.id
                LEFT JOIN edificio e ON s.edificio_id = e.id
                LEFT JOIN usuario u ON a.usuario_creador_id = u.id
                WHERE a.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $activo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $activo ?: null;
    }

    /**
     * Obtiene todas las fotos de un activo
     */
    public function obtenerFotosActivo(int $activoId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM activo_foto WHERE activo_id = ? ORDER BY es_principal DESC, id ASC");
        $stmt->execute([$activoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el historial de movimientos de un activo
     */
    public function obtenerHistorialActivo(int $activoId): array
    {
        $sql = "SELECT h.*, u.nombre_completo as usuario_nombre,
                       s1.nombre as sala_anterior_nombre, s2.nombre as sala_nueva_nombre
                FROM historial_activo h
                LEFT JOIN usuario u ON h.usuario_id = u.id
                LEFT JOIN sala s1 ON h.sala_anterior_id = s1.id
                LEFT JOIN sala s2 ON h.sala_nueva_id = s2.id
                WHERE h.activo_id = :activo_id
                ORDER BY h.fecha DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['activo_id' => $activoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el historial de mantenimientos de un activo
     */
    public function obtenerMantenimientosActivo(int $activoId): array
    {
        $sql = "SELECT m.*, u1.nombre_completo as usuario_reporte_nombre,
                       u2.nombre_completo as usuario_mantenimiento_nombre
                FROM mantenimiento m
                LEFT JOIN usuario u1 ON m.usuario_reporte_id = u1.id
                LEFT JOIN usuario u2 ON m.usuario_mantenimiento_id = u2.id
                WHERE m.activo_id = :activo_id
                ORDER BY m.fecha_reporte DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['activo_id' => $activoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene activos filtrados para el reporte general
     */
    public function obtenerActivosGeneral(array $filtros): array
    {
        $sql = "SELECT a.*, ta.nombre as tipo_nombre, 
                       s.nombre as sala_nombre, e.nombre as edificio_nombre,
                       u.nombre_completo as usuario_creador_nombre,
                       af.ruta_foto as foto_principal
                FROM activo a
                INNER JOIN sala s ON a.sala_id = s.id
                INNER JOIN edificio e ON s.edificio_id = e.id
                LEFT JOIN tipo_activo ta ON a.tipo_activo_id = ta.id
                LEFT JOIN usuario u ON a.usuario_creador_id = u.id
                LEFT JOIN activo_foto af ON af.activo_id = a.id AND af.es_principal = 1
                WHERE 1=1";
        
        $params = [];

        // Filtro por edificios accesibles (ya aplicado en el controlador usualmente, 
        // pero aquí lo reforzamos si no es ver_todo)
        if (!empty($filtros['edificios'])) {
            $placeholders = [];
            foreach ($filtros['edificios'] as $i => $id) {
                $key = ":edif_$i";
                $placeholders[] = $key;
                $params[$key] = $id;
            }
            $sql .= " AND e.id IN (" . implode(',', $placeholders) . ")";
        }

        if (!empty($filtros['salas'])) {
            $placeholders = [];
            foreach ($filtros['salas'] as $i => $id) {
                $key = ":sala_$i";
                $placeholders[] = $key;
                $params[$key] = $id;
            }
            $sql .= " AND s.id IN (" . implode(',', $placeholders) . ")";
        }

        if (!empty($filtros['tipos'])) {
            $placeholders = [];
            foreach ($filtros['tipos'] as $i => $id) {
                $key = ":tipo_$i";
                $placeholders[] = $key;
                $params[$key] = $id;
            }
            $sql .= " AND a.tipo_activo_id IN (" . implode(',', $placeholders) . ")";
        }

        if (!empty($filtros['estados'])) {
            $placeholders = [];
            foreach ($filtros['estados'] as $i => $estado) {
                $key = ":estado_$i";
                $placeholders[] = $key;
                $params[$key] = $estado;
            }
            $sql .= " AND a.estado IN (" . implode(',', $placeholders) . ")";
        }

        if (!empty($filtros['fecha_inicio'])) {
            $sql .= " AND a.fecha_creado >= :fecha_inicio";
            $params['fecha_inicio'] = $filtros['fecha_inicio'] . " 00:00:00";
        }

        if (!empty($filtros['fecha_fin'])) {
            $sql .= " AND a.fecha_creado <= :fecha_fin";
            $params['fecha_fin'] = $filtros['fecha_fin'] . " 23:59:59";
        }

        if (!empty($filtros['fecha_act_inicio'])) {
            $sql .= " AND a.fecha_actualizado >= :fecha_act_inicio";
            $params['fecha_act_inicio'] = $filtros['fecha_act_inicio'] . " 00:00:00";
        }

        if (!empty($filtros['fecha_act_fin'])) {
            $sql .= " AND a.fecha_actualizado <= :fecha_act_fin";
            $params['fecha_act_fin'] = $filtros['fecha_act_fin'] . " 23:59:59";
        }

        if (!empty($filtros['usuario_creador_id'])) {
            $sql .= " AND a.usuario_creador_id = :usuario_creador_id";
            $params['usuario_creador_id'] = $filtros['usuario_creador_id'];
        }

        $sql .= " ORDER BY e.nombre, s.nombre, a.nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el historial de múltiples activos a la vez
     */
    public function obtenerHistorialMultiples(array $activoIds): array
    {
        if (empty($activoIds)) return [];
        
        $placeholders = implode(',', array_fill(0, count($activoIds), '?'));
        $sql = "SELECT h.activo_id, h.*, u.nombre_completo as usuario_nombre,
                    s1.nombre as sala_anterior_nombre, s2.nombre as sala_nueva_nombre
                FROM historial_activo h
                LEFT JOIN usuario u ON h.usuario_id = u.id
                LEFT JOIN sala s1 ON h.sala_anterior_id = s1.id
                LEFT JOIN sala s2 ON h.sala_nueva_id = s2.id
                WHERE h.activo_id IN ($placeholders)
                ORDER BY h.activo_id, h.fecha DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($activoIds);
        return $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
    }

    public function obtenerMantenimientosMultiples(array $activoIds): array
    {
        if (empty($activoIds)) return [];
        
        $placeholders = implode(',', array_fill(0, count($activoIds), '?'));
        $sql = "SELECT m.activo_id, m.*, u1.nombre_completo as usuario_reporte_nombre,
                    u2.nombre_completo as usuario_mantenimiento_nombre
                FROM mantenimiento m
                LEFT JOIN usuario u1 ON m.usuario_reporte_id = u1.id
                LEFT JOIN usuario u2 ON m.usuario_mantenimiento_id = u2.id
                WHERE m.activo_id IN ($placeholders)
                ORDER BY m.activo_id, m.fecha_reporte DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($activoIds);
        return $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
    }
}
