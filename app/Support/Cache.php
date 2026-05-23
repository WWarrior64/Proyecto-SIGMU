<?php

declare(strict_types=1);

namespace App\Support;

final class Cache
{
    private static ?string $cacheDir = null;

    /**
     * Obtiene un valor del caché
     */
    public static function get(string $key): mixed
    {
        $file = self::getFilePath($key);
        if (!is_file($file)) {
            return null;
        }

        $data = file_get_contents($file);
        $cached = json_decode($data, true);

        if (!$cached || !isset($cached['expires_at'], $cached['value'])) {
            return null;
        }

        if (time() > $cached['expires_at']) {
            unlink($file);
            return null;
        }

        return $cached['value'];
    }

    /**
     * Almacena un valor en el caché
     */
    public static function set(string $key, mixed $value, int $ttlSeconds = 3600): void
    {
        $file = self::getFilePath($key);
        $data = json_encode([
            'expires_at' => time() + $ttlSeconds,
            'value' => $value,
        ], JSON_UNESCAPED_UNICODE);

        file_put_contents($file, $data, LOCK_EX);
    }

    // Elimina una clave del caché
    public static function forget(string $key): void
    {
        $file = self::getFilePath($key);
        if (is_file($file)) {
            unlink($file);
        }
    }

    /**
     * Recupera un valor del caché o lo genera con el callback si no existe
     */
    public static function remember(string $key, callable $callback, int $ttlSeconds = 3600): mixed
    {
        $cached = self::get($key);
        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();
        self::set($key, $value, $ttlSeconds);
        return $value;
    }

    /**
     * Limpia todo el caché
     */
    public static function flush(): void
    {
        $dir = self::getCacheDir();
        $files = glob("{$dir}/*.cache");
        foreach ($files as $file) {
            unlink($file);
        }
    }

    private static function getFilePath(string $key): string
    {
        $dir = self::getCacheDir();
        $hash = md5($key);
        return "{$dir}/{$hash}.cache";
    }

    private static function getCacheDir(): string
    {
        if (self::$cacheDir === null) {
            self::$cacheDir = __DIR__ . '/../../storage/cache';
            if (!is_dir(self::$cacheDir)) {
                mkdir(self::$cacheDir, 0755, true);
            }
        }
        return self::$cacheDir;
    }
}
