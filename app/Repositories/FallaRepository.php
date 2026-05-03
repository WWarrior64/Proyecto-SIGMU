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
            $this->db->beginTransaction();

            // 1. Insertar en la tabla mantenimiento
            // Como la tabla no tiene 'tipo_falla', lo concatenamos en la descripción del problema
            $descripcionCompleta = "[" . strtoupper($tipoFalla) . "] " . $descripcion;
            
            $stmt = $this->db->prepare(
                "INSERT INTO mantenimiento (activo_id, usuario_reporte_id, descripcion_problema, estado, fecha_reporte) 
                 VALUES (:activo_id, :usuario_id, :descripcion, 'pendiente', NOW())"
            );
            
            $stmt->execute([
                'activo_id'   => $activoId,
                'usuario_id'  => $usuarioId,
                'descripcion' => $descripcionCompleta
            ]);

            // 2. Actualizar el estado del activo a 'reparacion'
            // El trigger trg_activo_au se encargará de registrar esto en historial_activo
            $stmtActivo = $this->db->prepare(
                "UPDATE activo SET estado = 'reparacion', fecha_actualizado = NOW() WHERE id = :activo_id"
            );
            
            $stmtActivo->execute(['activo_id' => $activoId]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Error al registrar falla: " . $e->getMessage());
            return false;
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
}
