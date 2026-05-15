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
     * Genera el contenido para exportar a formato Excel 2003 XML con estilos
     */
    public function generarExcelListado(array $activos): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <?mso-application progid="Excel.Sheet"?>
        <Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
                  xmlns:x="urn:schemas-microsoft-com:office:excel"
                  xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
            <Styles>
                <Style ss:ID="Default" ss:Name="Normal">
                    <Alignment ss:Vertical="Bottom"/>
                    <Borders/>
                    <Font ss:FontName="Calibri" x:Family="Swiss"/>
                    <Interior/>
                    <NumberFormat/>
                    <Protection/>
                </Style>
                <Style ss:ID="Header">
                    <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
                    <Borders>
                        <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                    </Borders>
                    <Font ss:FontName="Calibri" x:Family="Swiss" ss:Bold="1" ss:Color="#FFFFFF"/>
                    <Interior ss:Color="#4F81BD" ss:Pattern="Solid"/>
                </Style>
                <Style ss:ID="SalaHeader">
                    <Font ss:FontName="Calibri" x:Family="Swiss" ss:Bold="1"/>
                    <Interior ss:Color="#D9E1F2" ss:Pattern="Solid"/>
                </Style>
            </Styles>
            <Worksheet ss:Name="Inventario">
                <Table>
                    <Column ss:Width="100"/>
                    <Column ss:Width="150"/>
                    <Column ss:Width="100"/>
                    <Column ss:Width="200"/>
                    <Column ss:Width="80"/>
                    <Column ss:Width="120"/>
                    <Column ss:Width="120"/>
                    <Column ss:Width="100"/>
                    <Row>
                        <Cell ss:StyleID="Header"><Data ss:Type="String">Codigo</Data></Cell>
                        <Cell ss:StyleID="Header"><Data ss:Type="String">Nombre</Data></Cell>
                        <Cell ss:StyleID="Header"><Data ss:Type="String">Tipo</Data></Cell>
                        <Cell ss:StyleID="Header"><Data ss:Type="String">Descripcion</Data></Cell>
                        <Cell ss:StyleID="Header"><Data ss:Type="String">Estado</Data></Cell>
                        <Cell ss:StyleID="Header"><Data ss:Type="String">Sala</Data></Cell>
                        <Cell ss:StyleID="Header"><Data ss:Type="String">Edificio</Data></Cell>
                        <Cell ss:StyleID="Header"><Data ss:Type="String">Fecha Registro</Data></Cell>
                    </Row>';

        $salaActual = '';
        foreach ($activos as $a) {
            $salaLabel = $a['sala_nombre'] . " (" . $a['edificio_nombre'] . ")";
            if ($salaActual !== $salaLabel) {
                $xml .= '
                    <Row>
                        <Cell ss:StyleID="SalaHeader" ss:MergeAcross="7"><Data ss:Type="String">Sala: ' . htmlspecialchars($salaLabel) . '</Data></Cell>
                    </Row>';
                $salaActual = $salaLabel;
            }

            $xml .= '
                    <Row>
                        <Cell><Data ss:Type="String">' . htmlspecialchars($a['codigo']) . '</Data></Cell>
                        <Cell><Data ss:Type="String">' . htmlspecialchars($a['nombre']) . '</Data></Cell>
                        <Cell><Data ss:Type="String">' . htmlspecialchars($a['tipo_nombre']) . '</Data></Cell>
                        <Cell><Data ss:Type="String">' . htmlspecialchars($a['descripcion'] ?? '') . '</Data></Cell>
                        <Cell><Data ss:Type="String">' . htmlspecialchars($a['estado']) . '</Data></Cell>
                        <Cell><Data ss:Type="String">' . htmlspecialchars($a['sala_nombre']) . '</Data></Cell>
                        <Cell><Data ss:Type="String">' . htmlspecialchars($a['edificio_nombre']) . '</Data></Cell>
                        <Cell><Data ss:Type="String">' . htmlspecialchars($a['fecha_creado']) . '</Data></Cell>
                    </Row>';
        }

        $xml .= '
                </Table>
            </Worksheet>
        </Workbook>';

        return $xml;
    }

    public function descargarExcel(string $content, string $filename): void
    {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
        echo $content;
    }
}
