<?php

declare(strict_types=1);

// Evitar que el script sea accesible desde el navegador web.
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Acceso denegado: Este script solo se puede ejecutar desde la línea de comandos (CLI).\n");
}

echo "=== Iniciando Respaldo de Base de Datos SIGMU ===\n";

try {
    // Cargar el bootstrap para inicializar el autoload, helpers y variables del .env
    require_once __DIR__ . '/../bootstrap/app.php';

    // Obtener configuración de base de datos directamente
    $config = require __DIR__ . '/database.php';
    $dbName = $config['database'];
    $dbHost = $config['host'];
    
    echo "Conectando a la base de datos '{$dbName}' en '{$dbHost}'...\n";
    $db = \App\Support\Database::connection();

    // Directorio de almacenamiento de respaldos fuera del proyecto (Carpeta personal del usuario)
    $homeDir = $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'] ?? getenv('HOME') ?? getenv('USERPROFILE') ?? null;
    if ($homeDir === null) {
        $backupDir = __DIR__ . '/../storage/backups';
    } else {
        $backupDir = rtrim($homeDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'sigmu-backups';
    }

    if (!file_exists($backupDir)) {
        if (!mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
            throw new \RuntimeException(sprintf('Directorio "%s" no pudo ser creado', $backupDir));
        }
    }

    $fileName = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $backupPath = $backupDir . DIRECTORY_SEPARATOR . $fileName;

    echo "Creando archivo de respaldo en: {$backupPath}\n";
    $fileHandle = fopen($backupPath, 'w');
    if ($fileHandle === false) {
        throw new \RuntimeException("No se pudo abrir el archivo de respaldo para escritura.");
    }

    // Cabecera inicial del archivo SQL
    fwrite($fileHandle, "-- ============================================================\n");
    fwrite($fileHandle, "-- RESPALDO DE BASE DE DATOS SIGMU (AUTOMÁTICO)\n");
    fwrite($fileHandle, "-- Fecha de generación: " . date('Y-m-d H:i:s') . "\n");
    fwrite($fileHandle, "-- Host: {$dbHost} | Base de datos: {$dbName}\n");
    fwrite($fileHandle, "-- ============================================================\n\n");

    fwrite($fileHandle, "SET FOREIGN_KEY_CHECKS = 0;\n");
    fwrite($fileHandle, "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;\n\n");
    fwrite($fileHandle, "-- Seleccionar la base de datos (permite importar desde phpMyAdmin sin seleccionarla manualmente)\n");
    fwrite($fileHandle, "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n");
    fwrite($fileHandle, "USE `{$dbName}`;\n\n");

    // ============================================================
    // ORDEN DE RESPALDO (debe coincidir con las dependencias):
    // 1. Tablas (estructura + datos) - base de todo
    // 2. Funciones (operan sobre las tablas)
    // 3. Procedimientos (operan sobre las tablas)
    // 4. Vistas (dependen de tablas y funciones)
    // 5. Triggers (dependen de tablas)
    // 6. Permisos (GRANT para usuario sigmu_app)
    // ============================================================

    // Obtener Tablas y Vistas (las necesitamos para clasificar)
    echo "Analizando tablas y vistas...\n";
    $stmt = $db->query("SHOW FULL TABLES");
    $tables = [];
    $views = [];

    while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
        $name = $row[0];
        $type = $row[1];
        if ($type === 'VIEW') {
            $views[] = $name;
        } else {
            $tables[] = $name;
        }
    }

    // ============================================================
    // 1. TABLAS (Estructura y Registros) - base de todo
    // ============================================================
    foreach ($tables as $table) {
        echo "Respaldando estructura de tabla: {$table}...\n";
        fwrite($fileHandle, "-- ------------------------------------------------------------\n");
        fwrite($fileHandle, "-- Estructura de la tabla `{$table}`\n");
        fwrite($fileHandle, "-- ------------------------------------------------------------\n");
        fwrite($fileHandle, "DROP TABLE IF EXISTS `{$table}`;\n");

        $createStmt = $db->query("SHOW CREATE TABLE `{$table}`");
        $createRow = $createStmt->fetch(\PDO::FETCH_ASSOC);
        
        $createTableSql = '';
        foreach ($createRow as $key => $val) {
            if (stripos($key, 'create table') !== false) {
                $createTableSql = $val;
                break;
            }
        }
        fwrite($fileHandle, $createTableSql . ";\n\n");

        // Respaldar Registros (Datos)
        echo "Respaldando datos de tabla: {$table}...\n";
        $dataStmt = $db->query("SELECT * FROM `{$table}`");
        $batchSize = 100;
        $count = 0;
        $valuesList = [];

        while ($dataRow = $dataStmt->fetch(\PDO::FETCH_ASSOC)) {
            $escapedRow = [];
            foreach ($dataRow as $val) {
                if ($val === null) {
                    $escapedRow[] = 'NULL';
                } else {
                    $escapedRow[] = $db->quote((string)$val);
                }
            }
            $valuesList[] = "(" . implode(', ', $escapedRow) . ")";
            $count++;

            if ($count % $batchSize === 0) {
                fwrite($fileHandle, "INSERT INTO `{$table}` VALUES \n" . implode(",\n", $valuesList) . ";\n");
                $valuesList = [];
            }
        }

        if (!empty($valuesList)) {
            fwrite($fileHandle, "INSERT INTO `{$table}` VALUES \n" . implode(",\n", $valuesList) . ";\n");
        }
        fwrite($fileHandle, "\n");
    }

    // ============================================================
    // 2. FUNCIONES (operan sobre las tablas, las vistas dependen de ellas)
    // ============================================================
    echo "Respaldando funciones...\n";
    $functionsStmt = $db->prepare("
        SELECT ROUTINE_NAME, ROUTINE_TYPE 
        FROM information_schema.ROUTINES 
        WHERE ROUTINE_SCHEMA = ? AND ROUTINE_TYPE = 'FUNCTION'
    ");
    $functionsStmt->execute([$dbName]);
    $functions = $functionsStmt->fetchAll(\PDO::FETCH_ASSOC);

    if (!empty($functions)) {
        fwrite($fileHandle, "-- ============================================================\n");
        fwrite($fileHandle, "-- FUNCIONES\n");
        fwrite($fileHandle, "-- ============================================================\n");
        fwrite($fileHandle, "DELIMITER //\n\n");
    }

    foreach ($functions as $function) {
        $name = $function['ROUTINE_NAME'];
        echo "Respaldando FUNCIÓN: {$name}...\n";
        
        fwrite($fileHandle, "-- ------------------------------------------------------------\n");
        fwrite($fileHandle, "-- Función `{$name}`\n");
        fwrite($fileHandle, "-- ------------------------------------------------------------\n");
        fwrite($fileHandle, "DROP FUNCTION IF EXISTS `{$name}`//\n\n");

        $showStmt = $db->query("SHOW CREATE FUNCTION `{$name}`");
        $row = $showStmt->fetch(\PDO::FETCH_ASSOC);
        
        $createSql = '';
        foreach ($row as $key => $val) {
            if (stripos($key, 'create function') !== false) {
                $createSql = $val;
                break;
            }
        }

        if (!empty($createSql)) {
            $createSql = preg_replace('/^CREATE\s+/i', 'CREATE ', $createSql);
            fwrite($fileHandle, $createSql . "//\n\n");
        }
    }

    if (!empty($functions)) {
        fwrite($fileHandle, "DELIMITER ;\n\n");
    }

    // ============================================================
    // 3. PROCEDIMIENTOS (operan sobre las tablas)
    // ============================================================
    echo "Respaldando procedimientos...\n";
    $proceduresStmt = $db->prepare("
        SELECT ROUTINE_NAME, ROUTINE_TYPE 
        FROM information_schema.ROUTINES 
        WHERE ROUTINE_SCHEMA = ? AND ROUTINE_TYPE = 'PROCEDURE'
    ");
    $proceduresStmt->execute([$dbName]);
    $procedures = $proceduresStmt->fetchAll(\PDO::FETCH_ASSOC);

    if (!empty($procedures)) {
        fwrite($fileHandle, "-- ============================================================\n");
        fwrite($fileHandle, "-- PROCEDIMIENTOS\n");
        fwrite($fileHandle, "-- ============================================================\n");
        fwrite($fileHandle, "DELIMITER //\n\n");
    }

    foreach ($procedures as $procedure) {
        $name = $procedure['ROUTINE_NAME'];
        echo "Respaldando PROCEDIMIENTO: {$name}...\n";
        
        fwrite($fileHandle, "-- ------------------------------------------------------------\n");
        fwrite($fileHandle, "-- Procedimiento `{$name}`\n");
        fwrite($fileHandle, "-- ------------------------------------------------------------\n");
        fwrite($fileHandle, "DROP PROCEDURE IF EXISTS `{$name}`//\n\n");

        $showStmt = $db->query("SHOW CREATE PROCEDURE `{$name}`");
        $row = $showStmt->fetch(\PDO::FETCH_ASSOC);
        
        $createSql = '';
        foreach ($row as $key => $val) {
            if (stripos($key, 'create procedure') !== false) {
                $createSql = $val;
                break;
            }
        }

        if (!empty($createSql)) {
            fwrite($fileHandle, $createSql . "//\n\n");
        }
    }

    if (!empty($procedures)) {
        fwrite($fileHandle, "DELIMITER ;\n\n");
    }

    // ============================================================
    // 4. VISTAS (dependen de tablas y funciones)
    // ============================================================
    foreach ($views as $view) {
        echo "Respaldando vista: {$view}...\n";
        fwrite($fileHandle, "-- ------------------------------------------------------------\n");
        fwrite($fileHandle, "-- Estructura de la vista `{$view}`\n");
        fwrite($fileHandle, "-- ------------------------------------------------------------\n");
        fwrite($fileHandle, "DROP VIEW IF EXISTS `{$view}`;\n");

        $createStmt = $db->query("SHOW CREATE VIEW `{$view}`");
        $createRow = $createStmt->fetch(\PDO::FETCH_ASSOC);

        $createViewSql = '';
        foreach ($createRow as $key => $val) {
            if (stripos($key, 'create view') !== false) {
                $createViewSql = $val;
                break;
            }
        }
        fwrite($fileHandle, $createViewSql . ";\n\n");
    }

    // ============================================================
    // 5. TRIGGERS (dependen de las tablas)
    // ============================================================
    echo "Respaldando triggers...\n";
    $triggersStmt = $db->prepare("
        SELECT TRIGGER_NAME 
        FROM information_schema.TRIGGERS 
        WHERE TRIGGER_SCHEMA = ?
    ");
    $triggersStmt->execute([$dbName]);
    $triggers = $triggersStmt->fetchAll(\PDO::FETCH_ASSOC);

    if (!empty($triggers)) {
        fwrite($fileHandle, "-- ============================================================\n");
        fwrite($fileHandle, "-- TRIGGERS\n");
        fwrite($fileHandle, "-- ============================================================\n");
        fwrite($fileHandle, "DELIMITER //\n\n");
    }

    foreach ($triggers as $trigger) {
        $name = $trigger['TRIGGER_NAME'];
        echo "Respaldando trigger: {$name}...\n";
        
        fwrite($fileHandle, "-- ------------------------------------------------------------\n");
        fwrite($fileHandle, "-- Trigger `{$name}`\n");
        fwrite($fileHandle, "-- ------------------------------------------------------------\n");
        fwrite($fileHandle, "DROP TRIGGER IF EXISTS `{$name}`//\n\n");

        // MySQL 5.7+ usa columna 'SQL Original Statement'
        // MySQL 8.0+ usa columna 'SQL Original Statement'
        // En algunos entornos puede llamarse 'Statement' o 'Create Trigger'
        // El contenido SQL comienza con "CREATE DEFINER=`...` TRIGGER"
        $showStmt = $db->query("SHOW CREATE TRIGGER `{$name}`");
        $row = $showStmt->fetch(\PDO::FETCH_ASSOC);
        
        $createTriggerSql = '';
        
        // Buscar en todas las columnas cualquier valor que contenga un CREATE TRIGGER
        // Nota: El formato es "CREATE DEFINER=`user`@`host` TRIGGER `name` ..."
        // Por lo tanto NO buscamos exactamente "CREATE TRIGGER" sino solo "TRIGGER"
        foreach ($row as $colName => $colValue) {
            if ($colValue !== null && stripos($colValue, 'TRIGGER') !== false) {
                // Verificar que realmente comienza con CREATE (es la sentencia completa)
                $trimmed = trim($colValue);
                if (stripos($trimmed, 'CREATE') === 0) {
                    $createTriggerSql = $trimmed;
                    break;
                }
            }
        }

        if (!empty($createTriggerSql)) {
            // Asegurar que termina correctamente (sin el DELIMITER // del original)
            $createTriggerSql = rtrim($createTriggerSql, " \t\n\r\0\x0B;");
            fwrite($fileHandle, $createTriggerSql . "//\n\n");
        } else {
            echo "  [ADVERTENCIA] No se pudo extraer SQL del trigger: {$name}\n";
        }
    }

    if (!empty($triggers)) {
        fwrite($fileHandle, "DELIMITER ;\n\n");
    }

    // ============================================================
    // 6. PERMISOS (GRANT) para el usuario sigmu_app
    // ============================================================
    echo "Respaldando permisos del usuario 'sigmu_app'...\n";
    
    fwrite($fileHandle, "-- ============================================================\n");
    fwrite($fileHandle, "-- PERMISOS DEL USUARIO DE APLICACIÓN\n");
    fwrite($fileHandle, "-- ============================================================\n");
    fwrite($fileHandle, "-- Crear o actualizar usuario de aplicación\n");
    fwrite($fileHandle, "CREATE USER IF NOT EXISTS 'sigmu_app'@'localhost'\n");
    fwrite($fileHandle, "    IDENTIFIED BY 'CambiarEstaContrasena2026!';\n\n");
    fwrite($fileHandle, "REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'sigmu_app'@'localhost';\n\n");

    // Permisos de sesión
    fwrite($fileHandle, "-- Sesión\n");
    fwrite($fileHandle, "GRANT EXECUTE ON PROCEDURE `{$dbName}`.`set_usuario_sesion`             TO 'sigmu_app'@'localhost';\n");
    fwrite($fileHandle, "GRANT EXECUTE ON PROCEDURE `{$dbName}`.`limpiar_usuario_sesion`         TO 'sigmu_app'@'localhost';\n\n");

    // Permisos de activos
    fwrite($fileHandle, "-- Activos\n");
    fwrite($fileHandle, "GRANT EXECUTE ON PROCEDURE `{$dbName}`.`sp_registrar_activo`            TO 'sigmu_app'@'localhost';\n");
    fwrite($fileHandle, "GRANT EXECUTE ON PROCEDURE `{$dbName}`.`sp_editar_activo`               TO 'sigmu_app'@'localhost';\n");
    fwrite($fileHandle, "GRANT EXECUTE ON PROCEDURE `{$dbName}`.`sp_eliminar_activo`             TO 'sigmu_app'@'localhost';\n\n");

    // Permisos de fotos de activos
    fwrite($fileHandle, "-- Fotos de activos\n");
    fwrite($fileHandle, "GRANT EXECUTE ON PROCEDURE `{$dbName}`.`sp_agregar_foto_activo`         TO 'sigmu_app'@'localhost';\n");
    fwrite($fileHandle, "GRANT EXECUTE ON PROCEDURE `{$dbName}`.`sp_eliminar_foto_activo`        TO 'sigmu_app'@'localhost';\n\n");

    // Permisos de fotos de edificios
    fwrite($fileHandle, "-- Fotos de edificios\n");
    fwrite($fileHandle, "GRANT EXECUTE ON PROCEDURE `{$dbName}`.`sp_agregar_foto_edificio`       TO 'sigmu_app'@'localhost';\n");
    fwrite($fileHandle, "GRANT EXECUTE ON PROCEDURE `{$dbName}`.`sp_eliminar_foto_edificio`      TO 'sigmu_app'@'localhost';\n\n");

    // Permisos de fotos de usuario
    fwrite($fileHandle, "-- Fotos de usuario\n");
    fwrite($fileHandle, "GRANT EXECUTE ON PROCEDURE `{$dbName}`.`sp_agregar_foto_usuario`        TO 'sigmu_app'@'localhost';\n");
    fwrite($fileHandle, "GRANT EXECUTE ON PROCEDURE `{$dbName}`.`sp_eliminar_foto_usuario`       TO 'sigmu_app'@'localhost';\n\n");

    // Permisos de mantenimientos
    fwrite($fileHandle, "-- Mantenimientos\n");
    fwrite($fileHandle, "GRANT EXECUTE ON PROCEDURE `{$dbName}`.`sp_registrar_mantenimiento`     TO 'sigmu_app'@'localhost';\n");
    fwrite($fileHandle, "GRANT EXECUTE ON PROCEDURE `{$dbName}`.`sp_completar_mantenimiento`     TO 'sigmu_app'@'localhost';\n\n");

    // Permisos de tipos de activo
    fwrite($fileHandle, "-- Tipos de activo\n");
    fwrite($fileHandle, "GRANT EXECUTE ON PROCEDURE `{$dbName}`.`sp_registrar_tipo_activo`       TO 'sigmu_app'@'localhost';\n");
    fwrite($fileHandle, "GRANT EXECUTE ON PROCEDURE `{$dbName}`.`sp_editar_tipo_activo`          TO 'sigmu_app'@'localhost';\n\n");

    // Permisos de usuarios
    fwrite($fileHandle, "-- Usuarios\n");
    fwrite($fileHandle, "GRANT EXECUTE ON PROCEDURE `{$dbName}`.`sp_registrar_usuario`           TO 'sigmu_app'@'localhost';\n");
    fwrite($fileHandle, "GRANT EXECUTE ON PROCEDURE `{$dbName}`.`sp_editar_usuario`              TO 'sigmu_app'@'localhost';\n");
    fwrite($fileHandle, "GRANT EXECUTE ON PROCEDURE `{$dbName}`.`sp_cambiar_contrasena`          TO 'sigmu_app'@'localhost';\n");
    fwrite($fileHandle, "GRANT EXECUTE ON PROCEDURE `{$dbName}`.`sp_asignar_edificio`            TO 'sigmu_app'@'localhost';\n");
    fwrite($fileHandle, "GRANT EXECUTE ON PROCEDURE `{$dbName}`.`sp_quitar_edificio`             TO 'sigmu_app'@'localhost';\n\n");

    // Permisos de edificios y salas
    fwrite($fileHandle, "-- Edificios y salas\n");
    fwrite($fileHandle, "GRANT EXECUTE ON PROCEDURE `{$dbName}`.`sp_registrar_edificio`          TO 'sigmu_app'@'localhost';\n");
    fwrite($fileHandle, "GRANT EXECUTE ON PROCEDURE `{$dbName}`.`sp_editar_edificio`             TO 'sigmu_app'@'localhost';\n");
    fwrite($fileHandle, "GRANT EXECUTE ON PROCEDURE `{$dbName}`.`sp_eliminar_edificio`           TO 'sigmu_app'@'localhost';\n");
    fwrite($fileHandle, "GRANT EXECUTE ON PROCEDURE `{$dbName}`.`sp_registrar_sala`              TO 'sigmu_app'@'localhost';\n");
    fwrite($fileHandle, "GRANT EXECUTE ON PROCEDURE `{$dbName}`.`sp_editar_sala`                 TO 'sigmu_app'@'localhost';\n");
    fwrite($fileHandle, "GRANT EXECUTE ON PROCEDURE `{$dbName}`.`sp_eliminar_sala`               TO 'sigmu_app'@'localhost';\n\n");

    // Permisos de vistas de lectura
    fwrite($fileHandle, "-- Vistas de lectura\n");
    $viewGrants = [
        'vista_mis_edificios',
        'vista_mis_salas',
        'vista_mis_activos',
        'vista_mis_mantenimientos',
        'vista_mis_historial',
        'vista_fotos_activo',
        'vista_fotos_edificio',
        'vista_fotos_usuario',
        'vista_tipos_activo',
        'vista_usuarios',
        'vista_usuario_edificios',
        'vista_roles',
    ];
    foreach ($viewGrants as $viewName) {
        fwrite($fileHandle, "GRANT SELECT ON `{$dbName}`.`{$viewName}` TO 'sigmu_app'@'localhost';\n");
    }
    fwrite($fileHandle, "\nFLUSH PRIVILEGES;\n\n");

    // Reactivar validaciones de claves foráneas
    fwrite($fileHandle, "SET FOREIGN_KEY_CHECKS = 1;\n");
    fclose($fileHandle);

    echo "¡Respaldo completado con éxito! Archivo guardado: {$fileName}\n";

    // 8. Rotación automática de respaldos (Mantener solo los últimos 5)
    echo "Aplicando rotación de respaldos...\n";
    $backupFiles = glob($backupDir . '/backup_*.sql');
    if ($backupFiles !== false) {
        usort($backupFiles, static function (string $a, string $b): int {
            return filemtime($b) - filemtime($a);
        });

        if (count($backupFiles) > 5) {
            $filesToDelete = array_slice($backupFiles, 5);
            foreach ($filesToDelete as $oldFile) {
                if (is_file($oldFile)) {
                    unlink($oldFile);
                    echo "Rotación: Eliminando respaldo antiguo: " . basename($oldFile) . "\n";
                }
            }
        }
    }

    echo "=== Proceso finalizado correctamente ===\n";

} catch (\Throwable $e) {
    fwrite(STDERR, "ERROR durante el respaldo: " . $e->getMessage() . "\n");
    exit(1);
}