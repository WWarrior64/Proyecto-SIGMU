<?php

declare(strict_types=1);

namespace App\Support;

final class Logger
{
    public const INFO = 'INFO';
    public const WARNING = 'WARNING';
    public const ERROR = 'ERROR';

    private static ?string $logDir = null;

    /**
     * Registra un mensaje en el log
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        $logDir = self::getLogDir();
        $date = date('Y-m-d');
        $time = date('H:i:s');
        $file = "{$logDir}/sigmu-{$date}.log";

        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $line = "[{$time}] [{$level}] {$message}{$contextStr}" . PHP_EOL;

        error_log($line, 3, $file);
    }

    /**
     * Registra un mensaje informativo
     */
    public static function info(string $message, array $context = []): void
    {
        self::log(self::INFO, $message, $context);
    }

    /**
     * Registra una advertencia
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log(self::WARNING, $message, $context);
    }

    /**
     * Registra un error
     */
    public static function error(string $message, array $context = []): void
    {
        self::log(self::ERROR, $message, $context);
    }

    /**
     * Obtiene la ruta del directorio de logs
     */
    private static function getLogDir(): string
    {
        if (self::$logDir === null) {
            self::$logDir = __DIR__ . '/../../storage/logs';
            if (!is_dir(self::$logDir)) {
                mkdir(self::$logDir, 0755, true);
            }
        }
        return self::$logDir;
    }

    /**
     * Limpia logs más antiguos que los días especificados
     */
    public static function clean(int $days = 30): void
    {
        $logDir = self::getLogDir();
        $files = glob("{$logDir}/sigmu-*.log");
        $expire = strtotime("-{$days} days");

        foreach ($files as $file) {
            if (filemtime($file) < $expire) {
                unlink($file);
            }
        }
    }
}