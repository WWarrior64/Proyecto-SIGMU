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

    // 1. Obtener Tablas y Vistas
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

    // 2. Respaldar Tablas (Estructura y Registros)
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

    // 3. Respaldar Vistas
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

    // 4. Respaldar Procedimientos y Funciones
    echo "Respaldando procedimientos y funciones...\n";
    $routinesStmt = $db->prepare("
        SELECT ROUTINE_NAME, ROUTINE_TYPE 
        FROM information_schema.ROUTINES 
        WHERE ROUTINE_SCHEMA = ?
    ");
    $routinesStmt->execute([$dbName]);
    $routines = $routinesStmt->fetchAll(\PDO::FETCH_ASSOC);

    foreach ($routines as $routine) {
        $name = $routine['ROUTINE_NAME'];
        $type = $routine['ROUTINE_TYPE'];

        echo "Respaldando {$type}: {$name}...\n";
        fwrite($fileHandle, "-- ------------------------------------------------------------\n");
        fwrite($fileHandle, "-- Estructura de {$type} `{$name}`\n");
        fwrite($fileHandle, "-- ------------------------------------------------------------\n");
        
        if ($type === 'PROCEDURE') {
            fwrite($fileHandle, "DROP PROCEDURE IF EXISTS `{$name}`;\n");
            $showStmt = $db->query("SHOW CREATE PROCEDURE `{$name}`");
            $row = $showStmt->fetch(\PDO::FETCH_ASSOC);
            $createSql = '';
            foreach ($row as $key => $val) {
                if (stripos($key, 'create procedure') !== false) {
                    $createSql = $val;
                    break;
                }
            }
        } else {
            fwrite($fileHandle, "DROP FUNCTION IF EXISTS `{$name}`;\n");
            $showStmt = $db->query("SHOW CREATE FUNCTION `{$name}`");
            $row = $showStmt->fetch(\PDO::FETCH_ASSOC);
            $createSql = '';
            foreach ($row as $key => $val) {
                if (stripos($key, 'create function') !== false) {
                    $createSql = $val;
                    break;
                }
            }
        }

        if (!empty($createSql)) {
            fwrite($fileHandle, "DELIMITER //\n");
            fwrite($fileHandle, $createSql . "//\n");
            fwrite($fileHandle, "DELIMITER ;\n\n");
        }
    }

    // 5. Respaldar Triggers
    echo "Respaldando triggers...\n";
    $triggersStmt = $db->prepare("
        SELECT TRIGGER_NAME 
        FROM information_schema.TRIGGERS 
        WHERE TRIGGER_SCHEMA = ?
    ");
    $triggersStmt->execute([$dbName]);
    $triggers = $triggersStmt->fetchAll(\PDO::FETCH_ASSOC);

    foreach ($triggers as $trigger) {
        $name = $trigger['TRIGGER_NAME'];
        echo "Respaldando trigger: {$name}...\n";
        
        fwrite($fileHandle, "-- ------------------------------------------------------------\n");
        fwrite($fileHandle, "-- Estructura del trigger `{$name}`\n");
        fwrite($fileHandle, "-- ------------------------------------------------------------\n");
        fwrite($fileHandle, "DROP TRIGGER IF EXISTS `{$name}`;\n");

        $showStmt = $db->query("SHOW CREATE TRIGGER `{$name}`");
        $row = $showStmt->fetch(\PDO::FETCH_ASSOC);
        
        $createTriggerSql = '';
        foreach ($row as $key => $val) {
            if (stripos($key, 'statement') !== false || stripos($key, 'create') !== false) {
                $createTriggerSql = $val;
                break;
            }
        }

        if (!empty($createTriggerSql)) {
            fwrite($fileHandle, "DELIMITER //\n");
            fwrite($fileHandle, $createTriggerSql . "//\n");
            fwrite($fileHandle, "DELIMITER ;\n\n");
        }
    }

    // Reactivar validaciones de claves foráneas
    fwrite($fileHandle, "SET FOREIGN_KEY_CHECKS = 1;\n");
    fclose($fileHandle);

    echo "¡Respaldo completado con éxito! Archivo guardado: {$fileName}\n";

    // 6. Rotación automática de respaldos (Mantener solo los últimos 5)
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
