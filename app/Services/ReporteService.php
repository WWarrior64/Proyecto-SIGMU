<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ReporteRepository;
use Dompdf\Dompdf;
use Dompdf\Options;

final class ReporteService
{
    private readonly ReporteRepository $repository;

    public function __construct(?ReporteRepository $repository = null)
    {
        $this->repository = $repository ?? new ReporteRepository();
    }

    /**
     * Genera el HTML para el reporte individual de activo
     */
    public function generarReporteIndividual(int $activoId, array $secciones): string
    {
        $activo = $this->repository->obtenerDatosActivo($activoId);
        if (!$activo) {
            throw new \RuntimeException("Activo no encontrado");
        }

        $datos = [
            'activo' => $activo,
            'secciones' => $secciones,
            'historial' => $secciones['historial'] ? $this->repository->obtenerHistorialActivo($activoId) : [],
            'mantenimientos' => $secciones['mantenimientos'] ? $this->repository->obtenerMantenimientosActivo($activoId) : [],
            'fecha_generacion' => date('d/m/Y H:i:s')
        ];

        return $this->renderView('reportes/pdf_individual', $datos);
    }

    /**
     * Genera el reporte general de activos
     */
    public function generarReporteGeneral(array $filtros, array $secciones): string
    {
        $activos = $this->repository->obtenerActivosGeneral($filtros);
        $activoIds = array_column($activos, 'id');

        $historiales = $secciones['historial'] ? $this->repository->obtenerHistorialMultiples($activoIds) : [];
        $mantenimientos = $secciones['mantenimientos'] ? $this->repository->obtenerMantenimientosMultiples($activoIds) : [];
        
        $datos = [
            'activos' => $activos,
            'filtros' => $filtros,
            'secciones' => $secciones,
            'historiales' => $historiales,
            'mantenimientos' => $mantenimientos,
            'fecha_generacion' => date('d/m/Y H:i:s'),
            'estadisticas' => $secciones['resumen'] ? $this->calcularEstadisticas($activos) : null
        ];

        return $this->renderView('reportes/pdf_general', $datos);
    }

    /**
     * Exporta a PDF usando Dompdf
     */
    public function exportarAPdf(string $html, string $filename, bool $inline = false): void
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Limpiar cualquier salida previa para evitar PDFs corruptos
        if (ob_get_length()) ob_end_clean();
        
        $dompdf->stream($filename . ".pdf", ["Attachment" => !$inline]);
        exit; // Asegurar que no se envíe nada más
    }

    /**
     * Calcula estadísticas básicas para el reporte general
     */
    private function calcularEstadisticas(array $activos): array
    {
        $stats = [
            'por_estado' => [],
            'por_edificio' => [],
            'total' => count($activos)
        ];

        foreach ($activos as $a) {
            $estado = $a['estado'];
            $edificio = $a['edificio_nombre'];

            $stats['por_estado'][$estado] = ($stats['por_estado'][$estado] ?? 0) + 1;
            $stats['por_edificio'][$edificio] = ($stats['por_edificio'][$edificio] ?? 0) + 1;
        }

        return $stats;
    }

    /**
     * Helper para renderizar una vista a string
     */
    private function renderView(string $viewPath, array $data): string
    {
        extract($data);
        ob_start();
        $viewFile = __DIR__ . '/../../resources/views/' . $viewPath . '.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "Error: Vista no encontrada ($viewPath)";
        }
        return ob_get_clean();
    }

    /**
     * Genera el "Excel" (HTML Table)
     */
    public function generarExcelListado(array $activos): string
    {
        $html = '<table border="1">';
        $html .= '<thead>
                    <tr>
                        <th>Codigo</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Descripcion</th>
                        <th>Estado</th>
                        <th>Sala</th>
                        <th>Edificio</th>
                        <th>Fecha Registro</th>
                    </tr>
                  </thead>';
        $html .= '<tbody>';

        $salaActual = '';
        foreach ($activos as $a) {
            $salaLabel = $a['sala_nombre'] . " (" . $a['edificio_nombre'] . ")";
            if ($salaActual !== $salaLabel) {
                $html .= '<tr style="background-color: #f2f2f2;"><td colspan="8"><b>Sala: ' . $salaLabel . '</b></td></tr>';
                $salaActual = $salaLabel;
            }
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($a['codigo']) . '</td>';
            $html .= '<td>' . htmlspecialchars($a['nombre']) . '</td>';
            $html .= '<td>' . htmlspecialchars($a['tipo_nombre']) . '</td>';
            $html .= '<td>' . htmlspecialchars($a['descripcion'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($a['estado']) . '</td>';
            $html .= '<td>' . htmlspecialchars($a['sala_nombre']) . '</td>';
            $html .= '<td>' . htmlspecialchars($a['edificio_nombre']) . '</td>';
            $html .= '<td>' . htmlspecialchars($a['fecha_creado']) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }

    public function descargarExcel(string $html, string $filename): void
    {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
        echo $html;
    }
}
