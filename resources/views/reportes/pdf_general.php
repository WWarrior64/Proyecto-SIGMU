<?php
/**
 * @var array $activos
 * @var array $filtros
 * @var array $secciones
 * @var array $historiales
 * @var array $mantenimientos
 * @var string $fecha_generacion
 * @var array|null $estadisticas
 * @var array $authUser
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte General de Inventario - SIGMU</title>
    <style>
        @page { margin: 100px 50px; }
        
        header { 
            position: fixed; 
            top: -70px; 
            left: 0; 
            right: 0; 
            height: 60px; 
            border-bottom: 3px solid #9a2018; 
            display: table;
            width: 100%;
        }
        
        .header-logo { display: table-cell; vertical-align: middle; width: 50%; }
        .header-info { display: table-cell; vertical-align: middle; text-align: right; font-size: 9px; color: #666; width: 50%; line-height: 1.2; }
        
        footer { 
            position: fixed; 
            bottom: -60px; 
            left: 0; 
            right: 0; 
            height: 40px;
            text-align: center; 
            font-size: 9px; 
            color: #999; 
            border-top: 1px solid #eee; 
            padding-top: 10px;
        }
        
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #212121; font-size: 10px; line-height: 1.4; }
        
        .cover { text-align: center; margin-top: 50px; page-break-after: always; }
        .cover h1 { font-size: 32px; color: #9a2018; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 2px; }
        .cover h2 { font-size: 18px; color: #444; margin-bottom: 40px; }
        .cover-details { display: inline-block; text-align: left; background: #f9f9f9; padding: 30px; border-radius: 10px; border: 1px solid #e0e0e0; min-width: 350px; }
        .cover-details p { margin: 10px 0; font-size: 12px; }
        .cover-details strong { color: #9a2018; }

        .section-title { 
            background: #9a2018; 
            color: white; 
            padding: 8px 15px; 
            font-size: 13px; 
            font-weight: bold; 
            margin: 25px 0 15px 0; 
            text-transform: uppercase;
        }
        
        .group-edificio { background: #2c3e50; color: white; padding: 6px 12px; font-size: 11px; font-weight: bold; margin-top: 20px; border-radius: 4px; }
        .group-sala { border-bottom: 2px solid #9a2018; color: #9a2018; padding: 8px 0 4px 0; font-size: 11px; font-weight: bold; margin-bottom: 10px; margin-top: 15px; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; table-layout: fixed; }
        table th { background-color: #f5f5f5; border: 1px solid #ddd; padding: 8px; text-align: left; font-weight: bold; color: #333; font-size: 9px; text-transform: uppercase; }
        table td { border: 1px solid #eee; padding: 8px; text-align: left; vertical-align: top; word-wrap: break-word; }
        table tr:nth-child(even) { background-color: #fafafa; }
        
        .sub-section { margin-left: 20px; border-left: 3px solid #eee; padding-left: 15px; margin-bottom: 20px; }
        .sub-title { font-weight: bold; color: #9a2018; font-size: 9px; margin-bottom: 5px; text-transform: uppercase; }
        .sub-table { font-size: 8.5px; border: none; }
        .sub-table th { background: #fff; border-bottom: 2px solid #eee; border-top: none; border-left: none; border-right: none; color: #666; }
        .sub-table td { border: none; border-bottom: 1px solid #f5f5f5; }

        .summary-grid { width: 100%; margin-top: 20px; }
        .summary-card { background: #fff; border: 1px solid #e0e0e0; padding: 15px; text-align: center; border-radius: 8px; }
        .summary-value { font-size: 24px; font-weight: bold; color: #9a2018; }
        .summary-label { font-size: 10px; color: #666; text-transform: uppercase; }

        .badge { padding: 3px 6px; border-radius: 3px; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .badge-disponible { background: #e8f5e9; color: #2e7d32; }
        .badge-en_uso { background: #e3f2fd; color: #1565c0; }
        .badge-reparacion { background: #fff3e0; color: #ef6c00; }
        .badge-des cartado { background: #ffeeb2; color: #5d4037; }

        .annual-summary { margin-top: 30px; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; }
        .annual-summary-header { background: #2c3e50; color: white; padding: 10px 15px; font-weight: bold; font-size: 11px; }
        .annual-row { display: table; width: 100%; border-bottom: 1px solid #eee; }
        .annual-cell { display: table-cell; padding: 10px 15px; font-size: 10px; }
        .annual-year { font-weight: bold; color: #9a2018; width: 40%; }
        .annual-value { text-align: right; font-family: 'Courier', monospace; font-weight: bold; font-size: 11px; }
        .annual-total { background: #f5f5f5; font-size: 12px; border-top: 2px solid #9a2018; }
    </style>
</head>
<body>
    <header>
        <div class="header-logo">
            <span style="font-size: 18px; font-weight: bold; color: #9a2018;">SIGMU</span><br>
            <span style="font-size: 10px; color: #444; font-weight: bold;">Universidad Católica de El Salvador</span>
        </div>
        <div class="header-info">
            REPORTE GENERAL DE ACTIVOS INSTITUCIONALES<br>
            Generado el: <?= $fecha_generacion ?>
        </div>
    </header>

    <footer>
        <script type="text/php">
            if (isset($pdf)) {
                $font = $fontMetrics->get_font("Helvetica", "normal");
                $size = 8;
                $pageText = "Sistema de Gestión de Mobiliario Universitario — UNICAES | Página " . $PAGE_NUM;
                $width = $pdf->get_width();
                $height = $pdf->get_height();
                $textWidth = $fontMetrics->get_text_width($pageText, $font, $size);
                $x = ($width / 2) - ($textWidth / 2);
                $y = $height - 30;
                $pdf->text($x, $y, $pageText, $font, $size, array(0.6, 0.6, 0.6));
            }
        </script>
    </footer>

    <!-- Portada -->
    <div class="cover">
        <div style="margin-top: 100px;">
            <h1>SIGMU</h1>
            <h2>Control Patrimonial de Activos Fijos</h2>
            <div style="width: 100px; height: 4px; background: #9a2018; margin: 0 auto 40px;"></div>
            
            <div class="cover-details">
                <p><strong>Tipo de Reporte:</strong> Inventario General Consolidado</p>
                <p><strong>Fecha de Emisión:</strong> <?= $fecha_generacion ?></p>
                <p><strong>Generado por:</strong> <?= htmlspecialchars($_SESSION['auth_user']['nombre_completo']) ?></p>
                <p><strong>Filtros aplicados:</strong></p>
                <ul style="margin: 5px 0 0 0; padding-left: 20px; font-size: 11px; line-height: 1.6;">
                    <li>Edificios: <?= !empty($filtros['edificios']) ? count($filtros['edificios']) : 'Todos los accesibles' ?></li>
                    <li>Salas: <?= !empty($filtros['salas']) ? count($filtros['salas']) : 'Todas' ?></li>
                    <li>Estados: <?= !empty($filtros['estados']) ? str_replace('_', ' ', implode(', ', $filtros['estados'])) : 'Todos' ?></li>
                    <li>Rango de registro: <?= $filtros['fecha_inicio'] ?: 'Desde el inicio' ?> — <?= $filtros['fecha_fin'] ?: 'Hoy' ?></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- SECCION: Datos Generales -->
    <?php if ($secciones['datos_generales']): ?>
        <div class="section-title">Detalle de Activos <?= !($filtros['agrupar_ubicacion'] ?? true) ? '(Listado Directo)' : 'por Ubicación' ?></div>
        <?php 
        $edificioActual = '';
        $salaActual = '';
        $agrupar = $filtros['agrupar_ubicacion'] ?? true;

        if (!$agrupar): ?>
            <table>
                <thead>
                    <tr>
                        <th style="width: 10%;">Código</th>
                        <th style="width: 15%;">Nombre</th>
                        <th style="width: 12%;">Tipo</th>
                        <th style="width: 10%;">Valor</th>
                        <th style="width: 14%;">Estado</th>
                        <th style="width: 12%;">F. Registro</th>
                        <th style="width: 15%;">Ubicación</th>
                        <th style="width: 12%;">Descripción</th>
                    </tr>
                </thead>
                <tbody>
        <?php endif;

        foreach($activos as $a): 
            if ($agrupar):
                if ($edificioActual !== $a['edificio_nombre']):
                    $edificioActual = $a['edificio_nombre'];
                    echo "<div class='group-edificio'>EDIFICIO: " . strtoupper(htmlspecialchars($edificioActual)) . "</div>";
                    $salaActual = ''; 
                endif;
                
                if ($salaActual !== $a['sala_nombre']):
                    $salaActual = $a['sala_nombre'];
                    echo "<div class='group-sala'>SALA: " . htmlspecialchars($salaActual) . "</div>";
                    ?>
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 12%;">Código</th>
                                <th style="width: 20%;">Nombre del Activo</th>
                                <th style="width: 12%;">Tipo</th>
                                <th style="width: 10%;">Valor</th>
                                <th style="width: 14%;">Estado</th>
                                <th style="width: 12%;">F. Registro</th>
                                <th style="width: 20%;">Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                    <?php
                endif;
            endif;
            ?>
            <tr>
                <td><strong><?= htmlspecialchars($a['codigo']) ?></strong></td>
                <td><?= htmlspecialchars($a['nombre']) ?></td>
                <td><?= htmlspecialchars($a['tipo_nombre'] ?? 'Sin tipo') ?></td>
                <td style="text-align: right;"><?= $a['valor_adquisicion'] !== null ? '$' . number_format((float)$a['valor_adquisicion'], 2) : '-' ?></td>
                <td style="white-space: nowrap;">
                    <span class="badge badge-<?= str_replace(' ', '_', $a['estado']) ?>">
                        <?= str_replace('_', ' ', $a['estado']) ?>
                    </span>
                </td>
                <td><?= date('d/m/Y', strtotime($a['fecha_creado'])) ?></td>
                <?php if (!$agrupar): ?>
                    <td><small><?= htmlspecialchars($a['sala_nombre']) ?><br><?= htmlspecialchars($a['edificio_nombre']) ?></small></td>
                <?php endif; ?>
                <td><small><?= htmlspecialchars($a['descripcion'] ?? 'N/D') ?></small></td>
            </tr>
            <?php 
        $colTotal = $agrupar ? 7 : 8;
            if ($secciones['historial'] && isset($historiales[$a['id']]) && !empty($historiales[$a['id']])): ?>
                <tr>
                    <td colspan="<?= $colTotal ?>" style="padding: 5px 15px 15px 15px; background: #fff;">
                        <div class="sub-section">
                            <div class="sub-title"> Movimientos Recientes</div>
                            <table class="sub-table">
                                <thead><tr><th style="width: 20%;">Fecha</th><th style="width: 20%;">Acción</th><th style="width: 25%;">Responsable</th><th>Detalles</th></tr></thead>
                                <tbody>
                                    <?php foreach($historiales[$a['id']] as $h): ?>
                                    <tr>
                                        <td><?= $h['fecha'] ?></td>
                                        <td><strong><?= str_replace('_', ' ', strtoupper($h['accion'])) ?></strong></td>
                                        <td><?= htmlspecialchars($h['usuario_nombre'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars(str_replace('→', ' -> ', $h['detalle'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>

            <?php if ($secciones['mantenimientos'] && isset($mantenimientos[$a['id']]) && !empty($mantenimientos[$a['id']])): ?>
                <tr>
                    <td colspan="<?= $colTotal ?>" style="padding: 5px 15px 15px 15px; background: #fff;">
                        <div class="sub-section">
                            <div class="sub-title"> Mantenimientos Realizados</div>
                            <table class="sub-table">
                                <thead><tr><th style="width: 20%;">Fecha</th><th style="width: 40%;">Descripción del Problema</th><th style="width: 15%;">Estado</th><th>Técnico</th></tr></thead>
                                <tbody>
                                    <?php foreach($mantenimientos[$a['id']] as $m): ?>
                                    <tr>
                                        <td><?= $m['fecha_reporte'] ?></td>
                                        <td><?= htmlspecialchars($m['descripcion_problema']) ?></td>
                                        <td><?= str_replace('_', ' ', strtoupper($m['estado'])) ?></td>
                                        <td><?= htmlspecialchars($m['usuario_mantenimiento_nombre'] ?? 'No asignado') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>

            <?php 
            if ($agrupar):
                $currentIdx = array_search($a, $activos);
                $next = $activos[$currentIdx + 1] ?? null;
                if (!$next || $next['sala_nombre'] !== $salaActual || $next['edificio_nombre'] !== $edificioActual):
                    echo "</tbody></table>";
                endif;
            endif;
        endforeach; 
        
        if (!$agrupar) echo "</tbody></table>";
        ?>
    <?php endif; ?>

    <!-- SECCION: Solo Historial (sin datos generales) -->
    <?php if (!$secciones['datos_generales'] && $secciones['historial']): ?>
        <div class="section-title">Historial de Movimientos por Activo</div>
        <?php foreach($activos as $a): 
            if (isset($historiales[$a['id']]) && !empty($historiales[$a['id']])): ?>
                <div class="group-sala">Activo: <?= htmlspecialchars($a['codigo']) ?> - <?= htmlspecialchars($a['nombre']) ?></div>
                <table class="sub-table" style="font-size: 9px;">
                    <thead><tr><th style="width: 20%;">Fecha</th><th style="width: 20%;">Acción</th><th style="width: 25%;">Responsable</th><th>Detalles</th></tr></thead>
                    <tbody>
                        <?php foreach($historiales[$a['id']] as $h): ?>
                        <tr>
                            <td><?= $h['fecha'] ?></td>
                            <td><strong><?= str_replace('_', ' ', strtoupper($h['accion'])) ?></strong></td>
                            <td><?= htmlspecialchars($h['usuario_nombre'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars(str_replace('→', ' -> ', $h['detalle'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- SECCION: Solo Mantenimientos (sin datos generales) -->
    <?php if (!$secciones['datos_generales'] && $secciones['mantenimientos']): ?>
        <div class="section-title">Mantenimientos por Activo</div>
        <?php foreach($activos as $a): 
            if (isset($mantenimientos[$a['id']]) && !empty($mantenimientos[$a['id']])): ?>
                <div class="group-sala">Activo: <?= htmlspecialchars($a['codigo']) ?> - <?= htmlspecialchars($a['nombre']) ?></div>
                <table class="sub-table" style="font-size: 9px;">
                    <thead><tr><th style="width: 20%;">Fecha</th><th style="width: 40%;">Descripción del Problema</th><th style="width: 15%;">Estado</th><th>Técnico</th></tr></thead>
                    <tbody>
                        <?php foreach($mantenimientos[$a['id']] as $m): ?>
                        <tr>
                            <td><?= $m['fecha_reporte'] ?></td>
                            <td><?= htmlspecialchars($m['descripcion_problema']) ?></td>
                            <td><?= str_replace('_', ' ', strtoupper($m['estado'])) ?></td>
                            <td><?= htmlspecialchars($m['usuario_mantenimiento_nombre'] ?? 'No asignado') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Resumen -->
    <?php if ($secciones['resumen'] && $estadisticas): ?>
        <div style="page-break-before: always;"></div>
        <div class="section-title">Resumen Estadístico Consolidado</div>
        
        <table style="border: none; margin-top: 20px;">
            <tr>
                <td style="border: none; padding: 0;">
                    <div class="summary-card" style="margin-right: 10px;">
                        <div class="summary-value"><?= $estadisticas['total'] ?></div>
                        <div class="summary-label">Total de Activos</div>
                    </div>
                </td>
                <?php 
                $topEstados = array_slice($estadisticas['por_estado'], 0, 3, true);
                foreach($topEstados as $est => $count): ?>
                <td style="border: none; padding: 0;">
                    <div class="summary-card" style="margin-right: 10px;">
                        <div class="summary-value"><?= $count ?></div>
                        <div class="summary-label"><?= $est ?></div>
                    </div>
                </td>
                <?php endforeach; ?>
            </tr>
        </table>

        <div style="margin-top: 30px;">
            <div class="sub-title">Distribución por Edificios</div>
            <table style="margin-top: 10px;">
                <thead>
                    <tr>
                        <th>Edificio</th>
                        <th style="text-align: center;">Cantidad</th>
                        <th style="text-align: center;">Porcentaje</th>
                        <th>Representación Visual</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($estadisticas['por_edificio'] as $edif => $count): 
                        $pct = round(($count / $estadisticas['total']) * 100, 1);
                    ?>
                    <tr>
                        <td style="font-weight: bold;"><?= htmlspecialchars($edif) ?></td>
                        <td style="text-align: center;"><?= $count ?></td>
                        <td style="text-align: center;"><?= $pct ?>%</td>
                        <td>
                            <div style="background: #eee; width: 100%; height: 10px; border-radius: 5px;">
                                <div style="background: #9a2018; width: <?= $pct ?>%; height: 10px; border-radius: 5px;"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Resumen Financiero por Año -->
    <?php if ($secciones['resumen']): ?>
        <div style="page-break-before: always;"></div>
        <div class="section-title">Resumen Financiero Acumulado por Año</div>
        
        <?php
        $sumasPorAño = [];
        foreach($activos as $a) {
            $year = date('Y', strtotime($a['fecha_creado']));
            $valor = (float)($a['valor_adquisicion'] ?? 0);
            $sumasPorAño[$year] = ($sumasPorAño[$year] ?? 0) + $valor;
        }
        ksort($sumasPorAño);

        $acumuladoHistorico = [];
        $corriente = 0;
        foreach($sumasPorAño as $year => $suma) {
            $corriente += $suma;
            $acumuladoHistorico[$year] = $corriente;
        }
        ?>

        <div class="annual-summary">
            <div class="annual-summary-header">
                Consolidado de Crecimiento Patrimonial (Histórico)
            </div>
            <?php foreach($acumuladoHistorico as $year => $total): ?>
            <div class="annual-row">
                <div class="annual-cell annual-year">TOTAL ACUMULADO AL FINALIZAR AÑO <?= $year ?></div>
                <div class="annual-cell annual-value">$ <?= number_format($total, 2) ?></div>
            </div>
            <?php endforeach; ?>
            <div class="annual-row annual-total">
                <div class="annual-cell annual-year">VALOR ACTUAL TOTAL DEL INVENTARIO</div>
                <div class="annual-cell annual-value">$ <?= number_format($corriente, 2) ?></div>
            </div>
        </div>
        
        <p style="font-size: 8px; color: #666; margin-top: 15px; font-style: italic;">
            * El valor acumulado representa la inversión total de la institución hasta el cierre del año indicado (incluyendo años anteriores).
        </p>
    <?php endif; ?>
</body>
</html>