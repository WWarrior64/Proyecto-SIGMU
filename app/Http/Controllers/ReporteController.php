<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ReporteService;
use App\Services\SigmuService;
use App\Support\Session;
use App\Support\Csrf;
use Throwable;

final class ReporteController
{
    private readonly ReporteService $reporteService;
    private readonly SigmuService $sigmuService;

    public function __construct()
    {
        $this->reporteService = new ReporteService();
        $this->sigmuService = new SigmuService();
    }

    /**
     * Muestra la vista de configuración del reporte individual
     */
    public function individualConfig(int $activoId): string
    {
        if (!$this->requireAuth()) return '';

        $activoModel = new \App\Models\Activo();
        $activo = $activoModel->obtenerPorId($activoId);
        
        if (!$activo) {
            header('Location: /sigmu/edificios?error=activo_no_encontrado');
            return '';
        }

        // VALIDACIÓN DE PERMISOS
        $user = Session::get('auth_user');
        if ($user['rol_nombre'] !== 'Administrador') {
            $misEdificios = array_column($this->sigmuService->obtenerMisEdificios(), 'id');
            $sala = $activoModel->obtenerSalaConEdificio((int)$activo['sala_id']);
            if (!$sala || !in_array((int)$sala['edificio_id'], $misEdificios)) {
                header('Location: /sigmu/edificios?error=acceso_denegado_reporte');
                return '';
            }
        }

        return view('reportes.individual_config', [
            'activo' => $activo
        ]);
    }

    /**
     * Procesa la exportación del reporte individual
     */
    public function exportarIndividual(): void
    {
        $this->procesarIndividual(false);
    }

    /**
     * Muestra una vista previa del reporte individual
     */
    public function previewIndividual(): void
    {
        $this->procesarIndividual(true);
    }

    private function procesarIndividual(bool $inline): void
    {
        if (!$this->requireAuth()) return;

        $activoId = (int)($_POST['activo_id'] ?? 0);
        
        // VALIDACIÓN DE PERMISOS
        $activoModel = new \App\Models\Activo();
        $activo = $activoModel->obtenerPorId($activoId);
        if (!$activo) return;

        $user = Session::get('auth_user');
        if ($user['rol_nombre'] !== 'Administrador') {
            $misEdificios = array_column($this->sigmuService->obtenerMisEdificios(), 'id');
            $sala = $activoModel->obtenerSalaConEdificio((int)$activo['sala_id']);
            if (!$sala || !in_array((int)$sala['edificio_id'], $misEdificios)) {
                header('Location: /sigmu/edificios?error=acceso_denegado_export');
                return;
            }
        }

        $secciones = [
            'datos_generales' => isset($_POST['datos_generales']),
            'imagenes' => isset($_POST['imagenes']),
            'historial' => isset($_POST['historial']),
            'mantenimientos' => isset($_POST['mantenimientos'])
        ];

        try {
            $html = $this->reporteService->generarReporteIndividual($activoId, $secciones);
            $this->reporteService->exportarAPdf($html, "Reporte_Activo_" . $activoId, $inline);
        } catch (Throwable $e) {
            echo "Error al generar reporte: " . $e->getMessage();
        }
    }

    /**
     * Muestra el panel de reportes generales
     */
    public function general(): string
    {
        if (!$this->requireAuth()) return '';

        $user = Session::get('auth_user');
        if ($user['rol_nombre'] === 'Personal Mantenimiento') {
            header('Location: /sigmu?error=acceso_denegado');
            return '';
        }

        $edificios = $this->sigmuService->obtenerMisEdificios();
        $tiposActivo = $this->sigmuService->obtenerTiposActivo();
        $usuarios = ($user['rol_nombre'] === 'Administrador') ? $this->sigmuService->obtenerTodosUsuarios() : [];

        return view('reportes.general', [
            'edificios' => $edificios,
            'tiposActivo' => $tiposActivo,
            'usuarios' => $usuarios
        ]);
    }

    /**
     * Procesa la exportación del reporte general
     */
    public function exportarGeneral(): void
    {
        $this->procesarGeneral(false);
    }

    /**
     * Muestra una vista previa del reporte general
     */
    public function previewGeneral(): void
    {
        $this->procesarGeneral(true);
    }

    private function procesarGeneral(bool $inline): void
    {
        if (!$this->requireAuth()) return;

        $user = Session::get('auth_user');
        $misEdificios = array_column($this->sigmuService->obtenerMisEdificios(), 'id');
        $edificiosSolicitados = $_POST['edificios'] ?? [];

        // Si no seleccionó ninguno, por defecto todos los accesibles
        // Si seleccionó alguno, filtrar para que solo sean los que tiene acceso
        if (empty($edificiosSolicitados)) {
            $edificiosAFiltrar = $misEdificios;
        } else {
            $edificiosAFiltrar = array_intersect($edificiosSolicitados, $misEdificios);
        }

        $filtros = [
            'edificios' => $edificiosAFiltrar,
            'salas' => $_POST['salas'] ?? [],
            'tipos' => $_POST['tipos'] ?? [],
            'estados' => $_POST['estados'] ?? [],
            'fecha_inicio' => $_POST['fecha_inicio'] ?? '',
            'fecha_fin' => $_POST['fecha_fin'] ?? '',
            'fecha_act_inicio' => $_POST['fecha_act_inicio'] ?? '',
            'fecha_act_fin' => $_POST['fecha_act_fin'] ?? '',
            'usuario_creador_id' => $_POST['usuario_creador_id'] ?? null
        ];

        $secciones = [
            'datos_generales' => isset($_POST['sec_datos']),
            'imagenes' => isset($_POST['sec_imagenes']),
            'historial' => isset($_POST['sec_historial']),
            'mantenimientos' => isset($_POST['sec_mantenimientos']),
            'resumen' => isset($_POST['sec_resumen'])
        ];

        try {
            $html = $this->reporteService->generarReporteGeneral($filtros, $secciones);
            $this->reporteService->exportarAPdf($html, "Reporte_General_Activos_" . date('Ymd'), $inline);
        } catch (Throwable $e) {
            echo "Error al generar reporte: " . $e->getMessage();
        }
    }

    /**
     * Exportación rápida desde el listado de inventario
     */
    public function exportarInventario(): void
    {
        if (!$this->requireAuth()) return;

        $formato = $_GET['formato'] ?? 'pdf';
        
        // Simular los filtros que vienen de la pantalla de listado
        $filtros = [
            'busqueda' => $_GET['busqueda'] ?? '',
            'estados' => !empty($_GET['estados']) ? explode(',', $_GET['estados']) : [],
            'tipos' => !empty($_GET['tipos']) ? explode(',', $_GET['tipos']) : [],
            'sala_id' => (int)($_GET['sala_id'] ?? 0)
        ];

        // Usamos el modelo Activo para obtener los datos respetando filtros
        $activoModel = new \App\Models\Activo();
        $activos = $activoModel->listar(1, 1000, $filtros['busqueda'], $filtros['estados'], $filtros['tipos'], $filtros['sala_id']);

        // Mapear a formato esperado por el service de reportes (nombres de campos)
        foreach ($activos as &$a) {
            $a['tipo_nombre'] = $a['tipo'];
        }

        if ($formato === 'excel') {
            $html = $this->reporteService->generarExcelListado($activos);
            $this->reporteService->descargarExcel($html, "Inventario_" . date('Ymd'));
        } else {
            // PDF rápido
            $datos = [
                'activos' => $activos,
                'fecha_generacion' => date('d/m/Y H:i:s'),
                'titulo' => 'Listado General de Inventario'
            ];
            $html = $this->renderView('reportes/pdf_listado_rapido', $datos);
            $this->reporteService->exportarAPdf($html, "Inventario_" . date('Ymd'));
        }
    }

    private function requireAuth(): bool
    {
        if (!Session::has('auth_user')) {
            header('Location: /sigmu?error=debes_iniciar_sesion');
            return false;
        }
        $this->sigmuService->iniciarSesionBd((int)Session::get('auth_user')['id']);
        return true;
    }

    private function renderView(string $viewPath, array $data): string
    {
        extract($data);
        ob_start();
        $viewFile = __DIR__ . '/../../../resources/views/' . $viewPath . '.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "Error: Vista no encontrada ($viewPath)";
        }
        return ob_get_clean();
    }
}
