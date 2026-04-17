<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Activo;
use App\Services\SigmuService;
use App\Support\Session;
use Throwable;

final class SalaController
{
    private readonly Activo $activoModelo;
    private readonly SigmuService $sigmuService;

    public function __construct()
    {
        $this->activoModelo = new Activo();
        $this->sigmuService = new SigmuService();
    }

    /**
     * Muestra los activos de una sala específica
     * Ruta: /sigmu/sala?sala_id=X
     */
    public function activos(): string
    {
        if (!$this->requireAuth()) {
            return '';
        }

        $salaId = filter_input(INPUT_GET, 'sala_id', FILTER_VALIDATE_INT);
        if (!$salaId) {
            return '<h2>sala_id invalido</h2><p><a href="/sigmu">Volver</a></p>';
        }

        try {
            // Parámetros de ordenamiento y paginación
            $pagina = (int) ($_GET['pagina'] ?? 1);
            $busqueda = trim((string) ($_GET['busqueda'] ?? ''));
            $porPagina = 50;
            $ordenarPor = trim((string) ($_GET['ordenar_por'] ?? 'id'));
            $ordenDireccion = trim((string) ($_GET['orden_direccion'] ?? 'DESC'));
            
            // Validar campos permitidos
            $camposPermitidos = ['id', 'codigo', 'nombre', 'tipo', 'estado'];
            $ordenarPor = in_array($ordenarPor, $camposPermitidos) ? $ordenarPor : 'id';
            $ordenDireccion = strtoupper($ordenDireccion) === 'ASC' ? 'ASC' : 'DESC';
            
            // Filtros (opcional en esta vista)
            $estados = (array)($_GET['estados'] ?? []);
            $tipos = array_filter(array_map('intval', (array)($_GET['tipos'] ?? [])));
            
            // Obtener todos los tipos de activo para el filtro del frontend
            $todosLosTipos = $this->activoModelo->obtenerTiposActivo();

            // Obtener activos de la sala (Filtrado en BD)
            $activos = $this->activoModelo->listar($pagina, $porPagina, $busqueda, $estados, $tipos, $salaId, $ordenarPor, $ordenDireccion);
            
            // Contar total para paginación (filtrado por sala)
            $total = $this->activoModelo->contar($busqueda, $estados, $tipos, $salaId);
            $totalPaginas = ceil($total / $porPagina);
            
            // Obtener información de contexto (Sala y Edificio)
            $salaNombre = 'Sin sala';
            $edificioNombre = 'Sin edificio';
            $edificioId = 0;

            $salaInfo = $this->activoModelo->obtenerSalaConEdificio($salaId);
            if ($salaInfo) {
                $salaNombre = $salaInfo['sala_nombre'];
                $edificioNombre = $salaInfo['edificio_nombre'];
                $edificioId = (int)$salaInfo['edificio_id'];
            }

            return view('inventario_catalogacion.listado_activos', [
                'salaId' => $salaId,
                'activos' => $activos,
                'pagina' => $pagina,
                'totalPaginas' => $totalPaginas,
                'busqueda' => $busqueda,
                'total' => $total,
                'sala' => $salaNombre,
                'edificio' => $edificioNombre,
                'edificio_id' => $edificioId,
                'ordenarPor' => $ordenarPor,
                'ordenDireccion' => $ordenDireccion,
                'tiposDisponibles' => $todosLosTipos,
                'estadosSeleccionados' => $estados,
                'tiposSeleccionados' => $tipos
            ]);
        } catch (Throwable $exception) {
            return '<h2>Error</h2><p>' . htmlspecialchars($exception->getMessage()) . '</p>';
        }
    }

    private function requireAuth(): bool
    {
        if (!Session::has('auth_user')) {
            header('Location: /sigmu?error=debes_iniciar_sesion');
            return false;
        }
        
        // Sincronizar sesión BD
        $this->sigmuService->iniciarSesionBd((int)Session::get('auth_user')['id']);
        return true;
    }
}
