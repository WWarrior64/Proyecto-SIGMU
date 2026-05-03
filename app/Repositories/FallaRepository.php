<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use PDO;

final class FallaRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * Registra un nuevo reporte de falla y actualiza el estado del activo
     */
    public function registrarFalla(int $activoId, int $usuarioId, string $tipoFalla, string $descripcion): bool
    {
        try {
            // Obtener datos actuales antes del cambio para el historial
            $stmtData = $this->db->prepare("SELECT estado, sala_id FROM activo WHERE id = :id");
            $stmtData->execute(['id' => $activoId]);
            $current = $stmtData->fetch();
            
            $estadoAnterior = $current['estado'] ?? null;
            $salaActualId = $current['sala_id'] ?? null;

            $this->db->beginTransaction();

            // 1. Registrar el mantenimiento usando el procedimiento almacenado
            $descripcionCompleta = "[" . strtoupper($tipoFalla) . "] " . $descripcion;
            
            $stmt = $this->db->prepare("CALL sp_registrar_mantenimiento(:activo_id, :descripcion, NULL)");
            $stmt->execute([
                'activo_id'   => $activoId,
                'descripcion' => $descripcionCompleta
            ]);
            $stmt->closeCursor();

            // 2. Actualizar el estado del activo a 'reparacion' usando el procedimiento almacenado
            $stmtActivo = $this->db->prepare("CALL sp_editar_activo(:activo_id, NULL, NULL, NULL, 'reparacion', NULL)");
            $stmtActivo->execute(['activo_id' => $activoId]);
            $stmtActivo->closeCursor();

            // 3. Registrar explícitamente en el historial con todos los campos requeridos
            $this->registrarHistorialInterno(
                $activoId, 
                'cambio_estado', 
                "Falla reportada: " . $descripcionCompleta,
                $estadoAnterior,
                'reparacion',
                (int)$salaActualId,
                (int)$salaActualId
            );

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error al registrar falla: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene los datos básicos del activo para el formulario
     */
    public function obtenerDatosActivo(int $activoId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT a.id, a.codigo, a.nombre, a.estado, e.nombre as edificio_nombre 
             FROM activo a
             JOIN sala s ON s.id = a.sala_id
             JOIN edificio e ON e.id = s.edificio_id
             WHERE a.id = :id"
        );
        $stmt->execute(['id' => $activoId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Método interno para registrar historial manualmente con todos los campos
     */
    private function registrarHistorialInterno(
        int $activoId, 
        string $accion, 
        string $detalle,
        ?string $estadoAnterior = null,
        ?string $estadoNuevo = null,
        ?int $salaAnteriorId = null,
        ?int $salaNuevaId = null
    ): void {
        try {
            // Obtenemos el usuario de la sesión de base de datos (@usuario_id_sesion)
            $stmt = $this->db->prepare("
                INSERT INTO historial_activo (
                    activo_id, usuario_id, accion, detalle,
                    estado_anterior, estado_nuevo,
                    sala_anterior_id, sala_nueva_id
                )
                VALUES (
                    :activo_id, 
                    IFNULL(@usuario_id_sesion, (SELECT id FROM usuario LIMIT 1)), 
                    :accion, :detalle,
                    :est_ant, :est_nue,
                    :sala_ant, :sala_nue
                )
            ");
            $stmt->execute([
                'activo_id' => $activoId,
                'accion'    => $accion,
                'detalle'   => $detalle,
                'est_ant'   => $estadoAnterior,
                'est_nue'   => $estadoNuevo,
                'sala_ant'  => $salaAnteriorId,
                'sala_nue'  => $salaNuevaId
            ]);
        } catch (\Throwable $e) {
            error_log("Error al registrar historial manual en FallaRepository: " . $e->getMessage());
        }
    }
}
