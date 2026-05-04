-- ============================================================
-- SIGMU v2: Permisos y SP para Personal Mantenimiento
-- Ejecutar como administrador (root o equivalente).
--
-- Contexto:
--   La cuenta sigmu_app solo tenia SELECT en vistas y EXECUTE en SP.
--   El panel de mantenimiento usa tablas base (catalogo) y UPDATE directo
--   al agendar; ademas sp_registrar_mantenimiento / sp_completar_mantenimiento
--   exigian fn_tiene_acceso_edificio (tecnicos sin usuario_edificio fallaban)
--   y sp_editar_activo no permitia a Personal Mantenimiento.
--
-- Si sigmu_app usa otro host (ej. %), repetir los GRANT cambiando @'localhost'.
-- ============================================================

USE sigmu;

-- Lectura de catalogo y listados que ejecuta PHP con PDO
GRANT SELECT ON sigmu.edificio TO 'sigmu_app'@'localhost';
GRANT SELECT ON sigmu.sala TO 'sigmu_app'@'localhost';
GRANT SELECT ON sigmu.activo TO 'sigmu_app'@'localhost';
GRANT SELECT ON sigmu.tipo_activo TO 'sigmu_app'@'localhost';
GRANT SELECT ON sigmu.mantenimiento TO 'sigmu_app'@'localhost';

-- Agenda desde PHP (UPDATE directo en MantenimientoRepository::agendarMantenimiento)
GRANT UPDATE ON sigmu.mantenimiento TO 'sigmu_app'@'localhost';

-- Historial al reportar falla / registrar cambios desde PHP (FallaRepository, MantenimientoRepository)
GRANT INSERT ON sigmu.historial_activo TO 'sigmu_app'@'localhost';

FLUSH PRIVILEGES;

DELIMITER //

DROP PROCEDURE IF EXISTS sp_registrar_mantenimiento//
CREATE DEFINER='root'@'localhost'
PROCEDURE sp_registrar_mantenimiento(
    IN p_activo_id    INT,
    IN p_descripcion  TEXT,
    IN p_fecha_agenda DATE
)
SQL SECURITY DEFINER
BEGIN
    DECLARE v_edificio_id INT;

    SELECT s.edificio_id INTO v_edificio_id
    FROM activo a
    JOIN sala s ON s.id = a.sala_id
    WHERE a.id = p_activo_id LIMIT 1;

    IF v_edificio_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Activo no encontrado';
    END IF;

    IF fn_tiene_acceso_edificio(fn_usuario_sesion(), v_edificio_id) = FALSE
       AND fn_rol_usuario() <> 'Personal Mantenimiento' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado: activo fuera de su edificio';
    END IF;

    INSERT INTO mantenimiento (
        activo_id, usuario_reporte_id,
        descripcion_problema, fecha_agendada
    ) VALUES (
        p_activo_id, fn_usuario_sesion(),
        p_descripcion, p_fecha_agenda
    );

    SELECT LAST_INSERT_ID() AS nuevo_mantenimiento_id;
END//

DROP PROCEDURE IF EXISTS sp_completar_mantenimiento//
CREATE DEFINER='root'@'localhost'
PROCEDURE sp_completar_mantenimiento(
    IN p_mantenimiento_id INT,
    IN p_notas            TEXT
)
SQL SECURITY DEFINER
BEGIN
    DECLARE v_activo_id   INT;
    DECLARE v_edificio_id INT;

    SELECT activo_id INTO v_activo_id
    FROM mantenimiento
    WHERE id = p_mantenimiento_id LIMIT 1;

    IF v_activo_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Mantenimiento no encontrado';
    END IF;

    SELECT s.edificio_id INTO v_edificio_id
    FROM activo a
    JOIN sala s ON s.id = a.sala_id
    WHERE a.id = v_activo_id LIMIT 1;

    IF fn_tiene_acceso_edificio(fn_usuario_sesion(), v_edificio_id) = FALSE
       AND fn_rol_usuario() <> 'Personal Mantenimiento' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado';
    END IF;

    UPDATE mantenimiento SET
        estado                   = 'completado',
        fecha_completada         = NOW(),
        usuario_mantenimiento_id = fn_usuario_sesion(),
        notas_intervencion       = p_notas
    WHERE id = p_mantenimiento_id;

    INSERT INTO historial_activo (activo_id, usuario_id, accion, detalle)
    VALUES (
        v_activo_id, fn_usuario_sesion(),
        'mantenimiento',
        CONCAT('Mantenimiento completado. Notas: ', COALESCE(p_notas, 'Sin notas'))
    );
END//

DROP PROCEDURE IF EXISTS sp_editar_activo//
CREATE DEFINER='root'@'localhost'
PROCEDURE sp_editar_activo(
    IN p_activo_id   INT,
    IN p_nombre      VARCHAR(100),
    IN p_tipo_id     INT,
    IN p_descripcion TEXT,
    IN p_estado      VARCHAR(20),
    IN p_sala_id     INT
)
SQL SECURITY DEFINER
BEGIN
    DECLARE v_edificio_actual INT;
    DECLARE v_edificio_nuevo  INT;

    SELECT s.edificio_id INTO v_edificio_actual
    FROM activo a
    JOIN sala s ON s.id = a.sala_id
    WHERE a.id = p_activo_id LIMIT 1;

    IF v_edificio_actual IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Activo no encontrado';
    END IF;

    IF fn_rol_usuario() NOT IN ('Administrador', 'Responsable de Area', 'Personal Mantenimiento') THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado: no tiene permiso para editar activos';
    END IF;

    IF fn_tiene_acceso_edificio(fn_usuario_sesion(), v_edificio_actual) = FALSE
       AND fn_rol_usuario() <> 'Personal Mantenimiento' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado: no tiene permiso sobre ese activo';
    END IF;

    IF p_sala_id IS NOT NULL THEN
        SELECT edificio_id INTO v_edificio_nuevo
        FROM sala WHERE id = p_sala_id LIMIT 1;

        IF v_edificio_nuevo IS NULL THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Sala destino no encontrada';
        END IF;

        IF fn_tiene_acceso_edificio(fn_usuario_sesion(), v_edificio_nuevo) = FALSE
           AND fn_rol_usuario() <> 'Personal Mantenimiento' THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Acceso denegado: no tiene permiso sobre la sala destino';
        END IF;
    END IF;

    UPDATE activo SET
        nombre         = COALESCE(p_nombre,      nombre),
        tipo_activo_id = COALESCE(p_tipo_id,     tipo_activo_id),
        descripcion    = COALESCE(p_descripcion, descripcion),
        estado         = COALESCE(p_estado,      estado),
        sala_id        = COALESCE(p_sala_id,     sala_id)
    WHERE id = p_activo_id;

    SELECT ROW_COUNT() AS filas_afectadas;
END//

DELIMITER ;
