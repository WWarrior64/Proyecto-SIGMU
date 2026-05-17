<?php
/**
 * @var array $activo
 * @var array $secciones
 * @var array $historial
 * @var array $mantenimientos
 * @var string $fecha_generacion
 * @var array $authUser
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Individual de Activo - SIGMU</title>
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
        
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #212121; font-size: 11px; line-height: 1.5; }
        
        .title-box { text-align: center; margin-bottom: 30px; margin-top: 20px; }
        .title-box h1 { margin: 0; color: #9a2018; font-size: 22px; text-transform: uppercase; letter-spacing: 1px; }
        .title-box p { margin: 5px 0 0 0; color: #555; font-size: 14px; font-weight: bold; }
        
        .section { margin-bottom: 30px; clear: both; }
        .section-title { 
            background: #f4f4f4; 
            padding: 8px 15px; 
            font-weight: bold; 
            border-left: 5px solid #9a2018; 
            margin-bottom: 15px; 
            font-size: 13px; 
            color: #333;
            text-transform: uppercase;
        }
        
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .data-table th { background-color: #f9f9f9; width: 25%; text-align: left; padding: 10px; border: 1px solid #e0e0e0; color: #666; font-size: 10px; text-transform: uppercase; }
        .data-table td { padding: 10px; border: 1px solid #e0e0e0; color: #212121; }
        
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 10px; text-transform: uppercase; font-weight: bold; }
        .status-disponible { background: #e8f5e9; color: #2e7d32; }
        .status-en_uso { background: #e3f2fd; color: #1565c0; }
        .status-reparacion { background: #fff3e0; color: #ef6c00; }
        .status-descartado { background: #f5f5f5; color: #616161; }

        .history-table { width: 100%; border-collapse: collapse; font-size: 10px; }
        .history-table th { background: #2c3e50; color: white; padding: 10px; text-align: left; }
        .history-table td { padding: 10px; border-bottom: 1px solid #eee; }
        .history-table tr:nth-child(even) { background: #fcfcfc; }

        .maint-card { border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; margin-bottom: 15px; page-break-inside: avoid; background: #fff; }
        .maint-header { border-bottom: 2px solid #9a2018; padding-bottom: 8px; margin-bottom: 12px; font-weight: bold; color: #9a2018; display: flex; justify-content: space-between; }
        .maint-row { margin-bottom: 8px; display: table; width: 100%; }
        .maint-label { display: table-cell; width: 30%; font-weight: bold; color: #666; font-size: 9px; text-transform: uppercase; }
        .maint-value { display: table-cell; width: 70%; }
    </style>
</head>
<body>
    <header>
        <div class="header-logo">
            <span style="font-size: 18px; font-weight: bold; color: #9a2018;">SIGMU</span><br>
            <span style="font-size: 10px; color: #444; font-weight: bold;">Universidad Católica de El Salvador</span>
        </div>
        <div class="header-info">
            REPORTE INDIVIDUAL DE ACTIVO FIJO<br>
            Expediente Digital de Control
        </div>
    </header>

    <footer>
        SIGMU — Documento Oficial de Control Patrimonial Institucional | Emisión: <?= $fecha_generacion ?>
    </footer>

    <div class="title-box">
        <h1>Ficha Técnica del Activo</h1>
        <p><?= htmlspecialchars($activo['nombre']) ?> — [ <?= htmlspecialchars($activo['codigo']) ?> ]</p>
    </div>

    <?php if ($secciones['datos_generales']): ?>
    <div class="section">
        <div class="section-title">1. Información General</div>
        <table class="data-table">
            <tr>
                <th>Código Institucional</th>
                <td><strong><?= htmlspecialchars($activo['codigo']) ?></strong></td>
                <th>Nombre del Activo</th>
                <td><?= htmlspecialchars($activo['nombre']) ?></td>
            </tr>
            <tr>
                <th>Tipo de Activo</th>
                <td><?= htmlspecialchars($activo['tipo_nombre'] ?? 'Sin tipo') ?></td>
                <th>Valor Adquisición</th>
                <td><?= $activo['valor_adquisicion'] !== null ? '$' . number_format((float)$activo['valor_adquisicion'], 2) : 'No registrado' ?></td>
            </tr>
            <tr>
                <th>Estado Operativo</th>
                <td><span class="badge status-<?= str_replace(' ', '_', $activo['estado']) ?>"><?= strtoupper($activo['estado']) ?></span></td>
                <th>Fecha de Registro</th>
                <td><?= $activo['fecha_creado'] ?></td>
            </tr>
            <tr>
                <th>Ubicación (Sala)</th>
                <td><?= htmlspecialchars($activo['sala_nombre']) ?></td>
                <th>Edificio</th>
                <td><?= htmlspecialchars($activo['edificio_nombre']) ?></td>
            </tr>
            <tr>
                <th>Registrado por</th>
                <td colspan="3"><?= htmlspecialchars($activo['usuario_creador_nombre'] ?? 'Sistema Central') ?></td>
            </tr>
            <tr>
                <th>Descripción Detallada</th>
                <td colspan="3"><?= nl2br(htmlspecialchars($activo['descripcion'] ?? 'Sin descripción adicional registrada.')) ?></td>
            </tr>
        </table>
    </div>
    <?php endif; ?>

    <?php if ($secciones['historial'] && !empty($historial)): ?>
    <div class="section">
        <div class="section-title">2. Historial de Trazabilidad y Movimientos</div>
        <table class="history-table">
            <thead>
                <tr>
                    <th style="width: 18%;">Fecha</th>
                    <th style="width: 18%;">Acción</th>
                    <th style="width: 20%;">Responsable</th>
                    <th>Detalles del Cambio / Ubicación</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($historial as $h): ?>
                <tr>
                    <td><?= $h['fecha'] ?></td>
                    <td><strong><?= strtoupper($h['accion']) ?></strong></td>
                    <td><?= htmlspecialchars($h['usuario_nombre'] ?? 'N/A') ?></td>
                    <td>
                        <?= htmlspecialchars($h['detalle']) ?>
                        <?php if ($h['accion'] === 'traslado' || $h['sala_anterior_id']): ?>
                            <div style="font-size: 9px; color: #666; margin-top: 3px;">
                                <em>Origen:</em> <?= htmlspecialchars($h['sala_anterior_nombre'] ?? 'N/D') ?> 
                                <span style="color: #9a2018;">&rarr;</span> 
                                <em>Destino:</em> <?= htmlspecialchars($h['sala_nueva_nombre'] ?? 'N/D') ?>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if ($secciones['mantenimientos'] && !empty($mantenimientos)): ?>
    <div class="section">
        <div class="section-title">3. Registro de Intervenciones y Mantenimiento</div>
        <?php foreach($mantenimientos as $m): ?>
        <div class="maint-card">
            <div class="maint-header">
                <span>Falla: <?= $m['fecha_reporte'] ?></span>
                <span style="text-transform: uppercase;">Estado: <?= $m['estado'] ?></span>
            </div>
            
            <div class="maint-row">
                <div class="maint-label">Reportado por:</div>
                <div class="maint-value"><?= htmlspecialchars($m['usuario_reporte_nombre']) ?></div>
            </div>
            
            <div class="maint-row">
                <div class="maint-label">Descripción Problema:</div>
                <div class="maint-value"><?= nl2br(htmlspecialchars($m['descripcion_problema'])) ?></div>
            </div>

            <?php if ($m['estado'] === 'completado' || $m['fecha_completada']): ?>
            <div class="maint-row">
                <div class="maint-label">Resolución:</div>
                <div class="maint-value">
                    <strong>Fecha:</strong> <?= $m['fecha_completada'] ?> | 
                    <strong>Técnico:</strong> <?= htmlspecialchars($m['usuario_mantenimiento_nombre'] ?? 'No asignado') ?>
                </div>
            </div>
            <div class="maint-row">
                <div class="maint-label">Notas de Intervención:</div>
                <div class="maint-value" style="font-style: italic; color: #444;">
                    <?= nl2br(htmlspecialchars($m['notas_intervencion'] ?? 'No se registraron notas de la intervención.')) ?>
                </div>
            </div>
            <?php else: ?>
            <div class="maint-row">
                <div class="maint-label">Estatus:</div>
                <div class="maint-value">Pendiente de intervención técnica.</div>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</body>
</html>
