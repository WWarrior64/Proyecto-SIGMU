<?php

declare(strict_types=1);

namespace App\Support;

final class Validator
{
    public const REGEX_NOMBRE = "/^[a-zA-Z\xC3\xA1\xC3\xA9\xC3\xAD\xC3\xB3\xC3\xBA\xC3\x81\xC3\x89\xC3\x8D\xC3\x93\xC3\x9A\xC3\xB1\xC3\x910-9 .,&'-]+$/u";

    public const REGEX_USERNAME = '/^[a-zA-Z0-9_.-]+$/';

    public const MAX_NOMBRE_CORTO = 100;

    public const MAX_NOMBRE_ACTIVO = 200;

    public const MAX_DESCRIPCION = 1000;

    public static function nombre(string $value, string $campo = 'Nombre', int $maxLength = self::MAX_NOMBRE_CORTO): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return "$campo es obligatorio";
        }
        if (mb_strlen($value) > $maxLength) {
            return "$campo no puede exceder $maxLength caracteres";
        }
        if (!preg_match(self::REGEX_NOMBRE, $value)) {
            return "$campo contiene caracteres no permitidos. Solo letras, numeros, espacios, tildes, ., &, ' y -";
        }
        return null;
    }

    public static function username(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return 'El nombre de usuario es obligatorio';
        }
        if (mb_strlen($value) > 50) {
            return 'El nombre de usuario no puede exceder 50 caracteres';
        }
        if (!preg_match(self::REGEX_USERNAME, $value)) {
            return 'El nombre de usuario solo puede contener letras, numeros, guiones y puntos';
        }
        return null;
    }

    public static function email(string $value, string $campo = 'Correo electronico'): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return "$campo es obligatorio";
        }
        if (mb_strlen($value) > 100) {
            return "$campo no puede exceder 100 caracteres";
        }
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "$campo no tiene un formato valido. Debe incluir @ y un dominio valido.";
        }
        return null;
    }

    public static function descripcion(string $value, string $campo = 'Descripcion', int $maxLength = self::MAX_DESCRIPCION): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (mb_strlen($value) > $maxLength) {
            return "$campo no puede exceder $maxLength caracteres";
        }
        return null;
    }

    public static function descripcionRequerida(string $value, string $campo = 'Descripcion', int $maxLength = self::MAX_DESCRIPCION): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return "$campo es obligatorio";
        }
        return self::descripcion($value, $campo, $maxLength);
    }
}