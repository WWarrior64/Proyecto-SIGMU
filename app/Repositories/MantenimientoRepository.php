<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use PDO;
use RuntimeException;

final class MantenimientoRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * Obtiene los mantenimientos para el calendario del mes actual
     */
    public function obtenerMantenimientosCalendario(int $mes, int $anio): array
    {
        $stmt = $this->db->prepare(
            "SELECT 
                m.id, 
                m.activo_id, 
                a.codigo as activo_codigo, 
                a.nombre as activo_nombre,
                m.fecha_agendada, 
                m.estado,
                m.descripcion_problema
             FROM mantenimiento m
             JOIN activo a ON a.id = m.activo_id
             WHERE MONTH(m.fecha_agendada) = :mes 
               AND YEAR(m.fecha_agendada) = :anio
               AND m.estado != 'cancelado'"
        );

        $stmt->execute(['mes' => $mes, 'anio' => $anio]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene los activos que tienen mantenimientos pendientes (sin fecha agendada o en estado pendiente)
     */
    public function obtenerMantenimientosPendientes(): array
    {
        $stmt = $this->db->query(
            "SELECT 
                m.id, 
                m.activo_id, 
                a.codigo as activo_codigo, 
                a.nombre as activo_nombre,
                e.nombre as edificio_nombre,
                m.descripcion_problema,
                m.fecha_reporte,
                (SELECT ruta_foto FROM activo_foto WHERE activo_id = a.id AND es_principal = 1 LIMIT 1) as foto_principal
             FROM mantenimiento m
             JOIN activo a ON a.id = m.activo_id
             JOIN sala s ON s.id = a.sala_id
             JOIN edificio e ON e.id = s.edificio_id
             WHERE m.estado = 'pendiente' AND m.fecha_agendada IS NULL
             ORDER BY m.fecha_reporte ASC"
        );

        return $stmt->fetchAll();
    }

    /**
     * Obtiene la lista de técnicos disponibles (rol 'Personal Mantenimiento')
     */
    public function obtenerTecnicosDisponibles(): array
    {
        $stmt = $this->db->query(
            "SELECT id, nombre_completo, username 
             FROM vista_usuarios 
             WHERE rol_nombre = 'Personal Mantenimiento' AND activo = 1"
        );
        return $stmt->fetchAll();
    }

    /**
     * Agenda un mantenimiento
     */
    public function agendarMantenimiento(int $id, int $tecnicoId, string $fecha, string $notas): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE mantenimiento SET 
                usuario_mantenimiento_id = :tecnico_id,
                fecha_agendada = :fecha,
                notas_intervencion = :notas,
                estado = 'en_proceso'
             WHERE id = :id"
        );

        return $stmt->execute([
            'id' => $id,
            'tecnico_id' => $tecnicoId,
            'fecha' => $fecha,
            'notas' => $notas
        ]);
    }

    /**
     * Obtiene estadísticas rápidas para el panel
     */
    public function obtenerEstadisticas(): array
    {
        $stats = [];
        
        // Total programados (en proceso)
        $stmt = $this->db->query("SELECT COUNT(*) FROM mantenimiento WHERE estado = 'en_proceso'");
        $stats['programados'] = (int) $stmt->fetchColumn();

        // Técnicos activos
        $stmt = $this->db->query("SELECT COUNT(*) FROM vista_usuarios WHERE rol_nombre = 'Personal Mantenimiento' AND activo = 1");
        $stats['tecnicos'] = (int) $stmt->fetchColumn();

        return $stats;
    }
}
