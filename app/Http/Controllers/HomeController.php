<?php

declare(strict_types=1);

namespace App\Http\Controllers;

// Controlador simple para confirmar que la app responde.
// Todo lo real del sistema cuelga de /sigmu.
final class HomeController
{
    public function index(): string
    {
        return '<h1>SIGMU funcionando</h1><p><a href="/sigmu">Ir al flujo MVC SIGMU</a></p>';
    }
}
