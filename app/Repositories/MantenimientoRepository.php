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
                s.nombre as sala_nombre,
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
     * Obtiene los datos detallados de un mantenimiento por su ID
     */
    public function obtenerMantenimientoPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT 
                m.*, 
                a.codigo as activo_codigo, 
                a.nombre as activo_nombre,
                u.email as email_tecnico,
                u.nombre_completo as tecnico_nombre
             FROM mantenimiento m
             JOIN activo a ON a.id = m.activo_id
             LEFT JOIN usuario u ON u.id = m.usuario_mantenimiento_id
             WHERE m.id = :id"
        );
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Obtiene el listado completo de mantenimientos ordenados por fecha ascendente
     */
    public function obtenerListadoMantenimientos(): array
    {
        $stmt = $this->db->query(
            "SELECT 
                m.id,
                a.codigo as activo_codigo,
                a.nombre as activo_nombre,
                m.fecha_agendada,
                m.fecha_reporte,
                m.fecha_completada,
                m.estado,
                u.nombre_completo as responsable,
                m.descripcion_problema
             FROM mantenimiento m
             JOIN activo a ON a.id = m.activo_id
             LEFT JOIN usuario u ON u.id = m.usuario_mantenimiento_id
             ORDER BY m.fecha_agendada ASC, m.fecha_reporte ASC"
        );

        return $stmt->fetchAll();
    }

    /**
     * Marca un mantenimiento como completado con datos detallados
     */
    public function completarMantenimiento(int $id, string $notas = '', string $fechaReal = '', string $resultado = 'resuelto', string $observaciones = ''): bool
    {
        try {
            $this->db->beginTransaction();

            // 1. Obtener datos del mantenimiento y activo antes de cerrar
            $stmtData = $this->db->prepare("SELECT activo_id FROM mantenimiento WHERE id = :id");
            $stmtData->execute(['id' => $id]);
            $activoId = (int)$stmtData->fetchColumn();

            // 2. Actualizar el mantenimiento
            // Concatenamos el resultado y observaciones en las notas si es necesario, 
            // o usamos los campos de la tabla si existen (según el script SQL v2, usamos notas_intervencion)
            $detalleFinal = "RESULTADO: " . strtoupper($resultado) . "\n";
            $detalleFinal .= "TRABAJO: " . $notas . "\n";
            if (!empty($observaciones)) {
                $detalleFinal .= "OBS: " . $observaciones;
            }

            $stmt = $this->db->prepare(
                "UPDATE mantenimiento SET 
                    estado = 'completado',
                    fecha_completada = :fecha_comp,
                    usuario_mantenimiento_id = IFNULL(@usuario_id_sesion, usuario_mantenimiento_id),
                    notas_intervencion = :notas
                 WHERE id = :id"
            );
            
            $stmt->execute([
                'id' => $id,
                'fecha_comp' => !empty($fechaReal) ? $fechaReal . ' ' . date('H:i:s') : date('Y-m-d H:i:s'),
                'notas' => $detalleFinal
            ]);
            $stmt->closeCursor();

            // 3. Si el resultado es 'resuelto', el activo vuelve a estar 'disponible' (activo)
            if ($resultado === 'resuelto') {
                $stmtActivo = $this->db->prepare("CALL sp_editar_activo(:activo_id, NULL, NULL, NULL, 'disponible', NULL)");
                $stmtActivo->execute(['activo_id' => $activoId]);
                $stmtActivo->closeCursor();
            }

            // 4. Registrar en historial_activo
            $stmtHist = $this->db->prepare("
                INSERT INTO historial_activo (activo_id, usuario_id, accion, detalle, estado_nuevo)
                VALUES (:activo_id, IFNULL(@usuario_id_sesion, 1), 'mantenimiento', :detalle, :est_nue)
            ");
            $stmtHist->execute([
                'activo_id' => $activoId,
                'detalle' => "Mantenimiento finalizado ($resultado). Detalle: $notas",
                'est_nue' => ($resultado === 'resuelto') ? 'disponible' : 'reparacion'
            ]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error al completar mantenimiento detallado: " . $e->getMessage());
            return false;
        }
    }
}
