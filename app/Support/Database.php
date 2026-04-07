<?php

declare(strict_types=1);

namespace App\Support;

use PDO;
use PDOException;
use RuntimeException;

// Conexión PDO compartida (singleton simple) para toda la app.
// Evitamos abrir múltiples conexiones en el mismo request.
final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        // Si ya existe conexión, la reutilizamos.
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        // Cargamos configuración desde /config/database.php (usa variables del .env).
        $config = require __DIR__ . '/../../config/database.php';

        // Armamos el DSN de MySQL con charset utf8mb4.
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        try {
            // Configuramos PDO para que lance excepciones y devuelva arrays asociativos.
            self::$connection = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $exception) {
            // Mensaje claro cuando no conecta (credenciales, host, puerto, etc).
            throw new RuntimeException('Database connection failed: ' . $exception->getMessage());
        }

        return self::$connection;
    }
}
