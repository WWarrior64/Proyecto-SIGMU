<?php
declare(strict_types=1);

namespace App\Support;

final class Roles
{
    public const ADMIN = 1;
    public const RESPONSABLE_AREA = 2;
    public const MANTENIMIENTO = 3;

    public static function is(mixed $roleId, int $target): bool
    {
        return (int)$roleId === $target;
    }

    public static function in(mixed $roleId, array $targets): bool
    {
        return in_array((int)$roleId, $targets, true);
    }
}
