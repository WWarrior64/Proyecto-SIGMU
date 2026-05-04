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
             FROM vista_mis_mantenimientos m
             JOIN vista_mis_activos a ON a.id = m.activo_id
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
                a.foto_principal
             FROM vista_mis_mantenimientos m
             JOIN vista_mis_activos a ON a.id = m.activo_id
             JOIN vista_mis_salas s ON s.id = a.sala_id
             JOIN vista_mis_edificios e ON e.id = s.edificio_id
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
        // Nota: No hay un SP específico para agendar con técnico, 
        // pero podemos usar sp_registrar_mantenimiento si fuera nuevo.
        // Como es actualización, si no hay grant UPDATE, tendríamos problemas.
        // Sin embargo, el script SQL v2 no provee sp_agendar_mantenimiento.
        // Asumiremos que sigmu_app tiene permisos sobre la tabla para este caso o 
        // usaremos una lógica alternativa si falla. 
        // REVISANDO GRANTS: No tiene UPDATE en mantenimiento.
        // ERROR: El repositorio anterior usaba UPDATE directo.
        
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
             LEFT JOIN vista_usuarios u ON u.id = m.usuario_mantenimiento_id
             WHERE m.id = :id"
        );
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Obtiene los mantenimientos asignados a un técnico específico
     */
    public function obtenerMantenimientosPorTecnico(int $tecnicoId): array
    {
        $stmt = $this->db->prepare(
            "SELECT 
                m.id, 
                m.activo_id, 
                a.codigo as activo_codigo, 
                a.nombre as activo_nombre,
                e.nombre as edificio_nombre,
                s.nombre as sala_nombre,
                m.descripcion_problema,
                m.fecha_agendada,
                m.estado,
                f.ruta_foto AS foto_principal
             FROM mantenimiento m
             JOIN activo a ON a.id = m.activo_id
             JOIN sala s ON s.id = a.sala_id
             JOIN edificio e ON e.id = s.edificio_id
             LEFT JOIN activo_foto f ON f.activo_id = a.id AND f.es_principal = TRUE
             WHERE m.usuario_mantenimiento_id = :tecnico_id
               AND m.estado IN ('pendiente', 'en_proceso')
             ORDER BY m.fecha_agendada DESC"
        );

        $stmt->execute(['tecnico_id' => $tecnicoId]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene el calendario de mantenimientos asignados a un técnico
     */
    public function obtenerCalendarioPorTecnico(int $tecnicoId, int $mes, int $anio): array
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
             WHERE m.usuario_mantenimiento_id = :tecnico_id
               AND MONTH(m.fecha_agendada) = :mes 
               AND YEAR(m.fecha_agendada) = :anio
               AND m.estado IN ('pendiente', 'en_proceso')"
        );

        $stmt->execute([
            'tecnico_id' => $tecnicoId,
            'mes' => $mes,
            'anio' => $anio
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Registra una nueva falla y actualiza el estado del activo
     */
    public function registrarFalla(int $activoId, int $usuarioReporteId, string $descripcion, string $fechaDeteccion): int
    {
        try {
            $stmtData = $this->db->prepare('SELECT estado, sala_id FROM activo WHERE id = :id');
            $stmtData->execute(['id' => $activoId]);
            $current = $stmtData->fetch();
            $estadoAnterior = $current['estado'] ?? null;
            $salaActualId = isset($current['sala_id']) ? (int) $current['sala_id'] : null;

            $this->db->beginTransaction();

            // 1. Usar procedimiento almacenado para registrar mantenimiento
            // sp_registrar_mantenimiento(p_activo_id, p_descripcion, p_fecha_agenda)
            // Nota: p_fecha_agenda se pone NULL porque es reporte de falla, no agendado aún.
            $stmt = $this->db->prepare("CALL sp_registrar_mantenimiento(:activo_id, :descripcion, NULL)");
            $stmt->execute([
                'activo_id' => $activoId,
                'descripcion' => $descripcion
            ]);
            
            $result = $stmt->fetch();
            $mantenimientoId = (int)($result['nuevo_mantenimiento_id'] ?? 0);
            $stmt->closeCursor();

            // 2. Usar procedimiento almacenado para cambiar estado del activo
            // sp_editar_activo(p_activo_id, p_nombre, p_tipo_id, p_descripcion, p_estado, p_sala_id)
            $stmtActivo = $this->db->prepare("CALL sp_editar_activo(:activo_id, NULL, NULL, NULL, 'reparacion', NULL)");
            $stmtActivo->execute(['activo_id' => $activoId]);
            $stmtActivo->closeCursor();

            // 3. Trazabilidad: mismo criterio que FallaRepository (motivo explícito en historial).
            // El trigger trg_activo_au puede registrar solo el cambio de estado genérico.
            $detalleHistorial = 'Falla reportada: ' . $descripcion;
            if ($fechaDeteccion !== '') {
                $detalleHistorial .= ' | Fecha de detección informada: ' . $fechaDeteccion;
            }
            $this->insertarHistorialReporteFalla(
                $activoId,
                $usuarioReporteId,
                $detalleHistorial,
                $estadoAnterior !== null ? (string) $estadoAnterior : null,
                'reparacion',
                $salaActualId,
                $salaActualId
            );

            $this->db->commit();
            return $mantenimientoId;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Registro explícito en historial_activo para reportes de falla (trazabilidad).
     */
    private function insertarHistorialReporteFalla(
        int $activoId,
        int $usuarioId,
        string $detalle,
        ?string $estadoAnterior,
        string $estadoNuevo,
        ?int $salaAnteriorId,
        ?int $salaNuevaId
    ): void {
        $stmt = $this->db->prepare(
            'INSERT INTO historial_activo (
                activo_id, usuario_id, accion, detalle,
                estado_anterior, estado_nuevo,
                sala_anterior_id, sala_nueva_id
            ) VALUES (
                :activo_id, :usuario_id, :accion, :detalle,
                :est_ant, :est_nue, :sala_ant, :sala_nue
            )'
        );
        $stmt->execute([
            'activo_id' => $activoId,
            'usuario_id' => $usuarioId,
            'accion' => 'cambio_estado',
            'detalle' => $detalle,
            'est_ant' => $estadoAnterior,
            'est_nue' => $estadoNuevo,
            'sala_ant' => $salaAnteriorId,
            'sala_nue' => $salaNuevaId,
        ]);
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
             FROM vista_mis_mantenimientos m
             JOIN vista_mis_activos a ON a.id = m.activo_id
             LEFT JOIN vista_usuarios u ON u.id = m.usuario_mantenimiento_id
             ORDER BY m.fecha_agendada ASC, m.fecha_reporte ASC"
        );

        return $stmt->fetchAll();
    }

    /**
     * Historial del técnico: solo mantenimientos ya cerrados (no pendientes ni en curso).
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerListadoMantenimientosPorTecnico(int $tecnicoId): array
    {
        $stmt = $this->db->prepare(
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
             LEFT JOIN vista_usuarios u ON u.id = m.usuario_mantenimiento_id
             WHERE m.usuario_mantenimiento_id = :tecnico_id
               AND m.estado = 'completado'
             ORDER BY m.fecha_completada DESC, m.fecha_reporte DESC, m.id DESC"
        );
        $stmt->execute(['tecnico_id' => $tecnicoId]);

        return $stmt->fetchAll();
    }

    /**
     * Marca un mantenimiento como completado con datos detallados
     */
    public function completarMantenimiento(int $id, string $notas = '', string $fechaReal = '', string $resultado = 'resuelto', string $observaciones = ''): bool
    {
        try {
            $this->db->beginTransaction();

            // 1. Concatenar detalles para el SP
            $detalleFinal = "RESULTADO: " . strtoupper($resultado) . "\n";
            $detalleFinal .= "TRABAJO: " . $notas . "\n";
            if (!empty($observaciones)) {
                $detalleFinal .= "OBS: " . $observaciones;
            }

            // 2. Usar procedimiento almacenado sp_completar_mantenimiento(p_mantenimiento_id, p_notas)
            $stmt = $this->db->prepare("CALL sp_completar_mantenimiento(:id, :notas)");
            $stmt->execute([
                'id' => $id,
                'notas' => $detalleFinal
            ]);
            $stmt->closeCursor();

            // 3. Obtener el activo_id para actualizar su estado si fue resuelto
            // (sp_completar_mantenimiento no cambia el estado del activo)
            $stmtData = $this->db->prepare('SELECT activo_id FROM mantenimiento WHERE id = :id');
            $stmtData->execute(['id' => $id]);
            $activoId = (int)$stmtData->fetchColumn();
            $stmtData->closeCursor();

            // 4. Si el resultado es 'resuelto', el activo vuelve a estar 'disponible'
            if ($resultado === 'resuelto') {
                $stmtActivo = $this->db->prepare("CALL sp_editar_activo(:activo_id, NULL, NULL, NULL, 'disponible', NULL)");
                $stmtActivo->execute(['activo_id' => $activoId]);
                $stmtActivo->closeCursor();
            }

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
