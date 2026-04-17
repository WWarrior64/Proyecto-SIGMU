<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SigmuService;
use Throwable;

final class TipoActivoController
{
    private readonly SigmuService $sigmuService;

    public function __construct()
    {
        $this->sigmuService = new SigmuService();
    }

    /**
     * Retorna todos los tipos de activo en formato JSON (para filtros AJAX)
     * Ruta: /sigmu/activo/tipos
     */
    public function index(): void
    {
        header('Content-Type: application/json');
        
        try {
            $tipos = $this->sigmuService->obtenerTiposActivo();
            echo json_encode($tipos);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
