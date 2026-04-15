-- ============================================================
-- MIGRACION: Agregar campos detallados al historial de activos
-- Fecha: 10/04/2026
-- Objetivo: Registrar campo modificado, valor anterior y valor nuevo
-- ============================================================

USE sigmu;

-- Agregar nuevos campos a la tabla historial_activo
ALTER TABLE historial_activo 
ADD COLUMN IF NOT EXISTS campo_modificado VARCHAR(100) NULL AFTER detalle,
ADD COLUMN IF NOT EXISTS valor_anterior TEXT NULL AFTER campo_modificado,
ADD COLUMN IF NOT EXISTS valor_nuevo TEXT NULL AFTER valor_anterior;

-- Actualizar indices
CREATE INDEX IF NOT EXISTS idx_historial_campo ON historial_activo(campo_modificado);
CREATE INDEX IF NOT EXISTS idx_historial_fecha ON historial_activo(fecha);

-- ============================================================
-- ACTUALIZAR TRIGGERS PARA DETECCION DE CAMBIOS POR CAMPO
-- ============================================================
DELIMITER //

-- Trigger AFTER INSERT (Registro nuevo)
DROP TRIGGER IF EXISTS trg_activo_ai//
CREATE DEFINER='root'@'localhost'
TRIGGER trg_activo_ai
AFTER INSERT ON activo
FOR EACH ROW
BEGIN
    -- Registro principal de creacion
    INSERT INTO historial_activo (
        activo_id, usuario_id, accion, detalle,
        estado_anterior, estado_nuevo,
        sala_anterior_id, sala_nueva_id,
        campo_modificado, valor_anterior, valor_nuevo
    ) VALUES (
        NEW.id, NEW.usuario_creador_id,
        'registro',
        CONCAT('Activo registrado exitosamente: ', NEW.nombre),
        NULL, NEW.estado, NULL, NEW.sala_id,
        NULL, NULL, NULL
    );
END//

-- Trigger AFTER UPDATE (Modificaciones)
DROP TRIGGER IF EXISTS trg_activo_au//
CREATE DEFINER='root'@'localhost'
TRIGGER trg_activo_au
AFTER UPDATE ON activo
FOR EACH ROW
BEGIN
    DECLARE v_accion VARCHAR(20);
    DECLARE v_campo VARCHAR(100);
    DECLARE v_valor_anterior TEXT;
    DECLARE v_valor_nuevo TEXT;
    
    -- Determinar accion general
    IF OLD.estado <> NEW.estado AND OLD.sala_id <> NEW.sala_id THEN
        SET v_accion = 'modificacion';
    ELSEIF OLD.estado <> NEW.estado THEN
        SET v_accion = 'cambio_estado';
    ELSEIF OLD.sala_id <> NEW.sala_id THEN
        SET v_accion = 'traslado';
    ELSE
        SET v_accion = 'modificacion';
    END IF;

    -- ✅ Verificar cada campo individualmente y registrar cambio
    -- Codigo
    IF OLD.codigo <> NEW.codigo THEN
        INSERT INTO historial_activo (
            activo_id, usuario_id, accion, detalle, campo_modificado, 
            valor_anterior, valor_nuevo, estado_anterior, estado_nuevo,
            sala_anterior_id, sala_nueva_id
        ) VALUES (
            NEW.id, @usuario_id_sesion, v_accion,
            CONCAT('Código modificado de "', OLD.codigo, '" a "', NEW.codigo, '"'),
            'codigo', OLD.codigo, NEW.codigo,
            OLD.estado, NEW.estado, OLD.sala_id, NEW.sala_id
        );
    END IF;
    
    -- Nombre
    IF OLD.nombre <> NEW.nombre THEN
        INSERT INTO historial_activo (
            activo_id, usuario_id, accion, detalle, campo_modificado, 
            valor_anterior, valor_nuevo, estado_anterior, estado_nuevo,
            sala_anterior_id, sala_nueva_id
        ) VALUES (
            NEW.id, @usuario_id_sesion, v_accion,
            CONCAT('Nombre modificado de "', OLD.nombre, '" a "', NEW.nombre, '"'),
            'nombre', OLD.nombre, NEW.nombre,
            OLD.estado, NEW.estado, OLD.sala_id, NEW.sala_id
        );
    END IF;
    
    -- Tipo de activo
    IF OLD.tipo_activo_id <> NEW.tipo_activo_id THEN
        SET @tipo_anterior = (SELECT nombre FROM tipo_activo WHERE id = OLD.tipo_activo_id);
        SET @tipo_nuevo = (SELECT nombre FROM tipo_activo WHERE id = NEW.tipo_activo_id);
        
        INSERT INTO historial_activo (
            activo_id, usuario_id, accion, detalle, campo_modificado, 
            valor_anterior, valor_nuevo, estado_anterior, estado_nuevo,
            sala_anterior_id, sala_nueva_id
        ) VALUES (
            NEW.id, @usuario_id_sesion, v_accion,
            CONCAT('Tipo de activo modificado de "', COALESCE(@tipo_anterior, OLD.tipo_activo_id), '" a "', COALESCE(@tipo_nuevo, NEW.tipo_activo_id), '"'),
            'tipo_activo_id', OLD.tipo_activo_id, NEW.tipo_activo_id,
            OLD.estado, NEW.estado, OLD.sala_id, NEW.sala_id
        );
    END IF;
    
    -- Descripcion
    IF OLD.descripcion <> NEW.descripcion THEN
        INSERT INTO historial_activo (
            activo_id, usuario_id, accion, detalle, campo_modificado, 
            valor_anterior, valor_nuevo, estado_anterior, estado_nuevo,
            sala_anterior_id, sala_nueva_id
        ) VALUES (
            NEW.id, @usuario_id_sesion, v_accion,
            'Descripción modificada',
            'descripcion', OLD.descripcion, NEW.descripcion,
            OLD.estado, NEW.estado, OLD.sala_id, NEW.sala_id
        );
    END IF;
    
    -- Estado
    IF OLD.estado <> NEW.estado THEN
        INSERT INTO historial_activo (
            activo_id, usuario_id, accion, detalle, campo_modificado, 
            valor_anterior, valor_nuevo, estado_anterior, estado_nuevo,
            sala_anterior_id, sala_nueva_id
        ) VALUES (
            NEW.id, @usuario_id_sesion, 'cambio_estado',
            CONCAT('Estado modificado de "', OLD.estado, '" a "', NEW.estado, '"'),
            'estado', OLD.estado, NEW.estado,
            OLD.estado, NEW.estado, OLD.sala_id, NEW.sala_id
        );
    END IF;
    
    -- Sala
    IF OLD.sala_id <> NEW.sala_id THEN
        SET @sala_anterior = (SELECT nombre FROM sala WHERE id = OLD.sala_id);
        SET @sala_nueva = (SELECT nombre FROM sala WHERE id = NEW.sala_id);
        
        INSERT INTO historial_activo (
            activo_id, usuario_id, accion, detalle, campo_modificado, 
            valor_anterior, valor_nuevo, estado_anterior, estado_nuevo,
            sala_anterior_id, sala_nueva_id
        ) VALUES (
            NEW.id, @usuario_id_sesion, 'traslado',
            CONCAT('Activo trasladado de "', COALESCE(@sala_anterior, OLD.sala_id), '" a "', COALESCE(@sala_nueva, NEW.sala_id), '"'),
            'sala_id', OLD.sala_id, NEW.sala_id,
            OLD.estado, NEW.estado, OLD.sala_id, NEW.sala_id
        );
    END IF;
END//

-- Trigger AFTER DELETE
DROP TRIGGER IF EXISTS trg_activo_ad//
CREATE DEFINER='root'@'localhost'
TRIGGER trg_activo_ad
AFTER DELETE ON activo
FOR EACH ROW
BEGIN
    INSERT INTO historial_activo (
        activo_id, usuario_id, accion, detalle,
        estado_anterior, estado_nuevo,
        sala_anterior_id, sala_nueva_id
    ) VALUES (
        OLD.id, @usuario_id_sesion,
        'eliminacion',
        CONCAT('Activo eliminado: ', OLD.nombre),
        OLD.estado, NULL, OLD.sala_id, NULL
    );
END//

DELIMITER ;

-- ============================================================
-- FIN MIGRACION
-- ============================================================