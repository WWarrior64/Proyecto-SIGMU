<?php

declare(strict_types=1);

namespace App\Http\Controllers;

// Controlador simple para confirmar que la app responde.
// Todo lo real del sistema cuelga de /sigmu.
final class HomeController
{
    public function index(): void
    {
        header('Location: /sigmu');
        exit;
    }
}
