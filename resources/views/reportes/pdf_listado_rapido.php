<?php
/**
 * @var array $activos
 * @var string $fecha_generacion
 * @var array $authUser
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario SIGMU - UNICAES</title>
    <style>
        @page { margin: 100px 40px; }
        
        header { 
            position: fixed; 
            top: -70px; 
            left: 0; 
            right: 0; 
            height: 50px; 
            border-bottom: 3px solid #9a2018; 
            display: table;
            width: 100%;
        }
        
        .header-logo { display: table-cell; vertical-align: middle; width: 50%; }
        .header-info { display: table-cell; vertical-align: middle; text-align: right; font-size: 9px; color: #666; width: 50%; }
        
        footer { 
            position: fixed; 
            bottom: -60px; 
            left: 0; 
            right: 0; 
            height: 30px; 
            text-align: center; 
            font-size: 8px; 
            color: #999; 
            border-top: 1px solid #eee;
            padding-top: 5px;
        }
        
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #212121; font-size: 9px; line-height: 1.4; }
        
        .institutional-header { text-align: center; margin-bottom: 25px; margin-top: 10px; }
        .institutional-header h1 { margin: 0; font-size: 16px; color: #9a2018; text-transform: uppercase; letter-spacing: 1px; }
        .institutional-header p { margin: 2px 0; font-weight: bold; font-size: 10px; color: #555; }
        
        .room-group { background: #f4f4f4; padding: 8px 12px; border-left: 4px solid #9a2018; margin: 20px 0 10px 0; font-size: 10px; font-weight: bold; color: #333; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; table-layout: fixed; }
        table th { background-color: #2c3e50; color: white; padding: 8px; text-align: left; font-size: 8px; text-transform: uppercase; }
        table td { border: 1px solid #eee; padding: 8px; text-align: left; vertical-align: top; word-wrap: break-word; }
        table tr:nth-child(even) { background-color: #fafafa; }
        
        .badge { padding: 2px 4px; border-radius: 3px; font-size: 7px; font-weight: bold; text-transform: uppercase; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <header>
        <div class="header-logo">
            <span style="font-size: 14px; font-weight: bold; color: #9a2018;">SIGMU</span>
        </div>
        <div class="header-info">
            Universidad Católica de El Salvador
        </div>
    </header>
    
    <footer>
        Listado generado por: <?= htmlspecialchars($_SESSION['auth_user']['nombre_completo'] ?? 'Sistema') ?> | Fecha: <?= $fecha_generacion ?> | Página {PAGENO} de {nb}
    </footer>

    <div class="institutional-header">
        <h1>SISTEMA DE GESTIÓN DE MOBILIARIO UNIVERSITARIO (SIGMU)</h1>
        <p>LISTADO RÁPIDO DE INVENTARIO POR SALA</p>
    </div>

    <?php 
    $salaActual = '';
    foreach($activos as $a): 
        $salaLabel = $a['sala_nombre'] . " — " . $a['edificio_nombre'];
        if ($salaActual !== $salaLabel):
            if ($salaActual !== '') echo "</tbody></table>";
            $salaActual = $salaLabel;
            echo "<div class='room-group'>UBICACIÓN: " . htmlspecialchars($salaActual) . "</div>";
            ?>
            <table>
                <thead>
                    <tr>
                        <th style="width: 15%;">Código</th>
                        <th style="width: 25%;">Activo</th>
                        <th style="width: 15%;">Categoría</th>
                        <th style="width: 12%;">Valor</th>
                        <th style="width: 12%;">Estado</th>
                        <th style="width: 21%;">Descripción</th>
                    </tr>
                </thead>
                <tbody>
            <?php
        endif;
        ?>
        <tr>
            <td><strong><?= htmlspecialchars($a['codigo']) ?></strong></td>
            <td><?= htmlspecialchars($a['nombre']) ?></td>
            <td><?= htmlspecialchars($a['tipo_nombre'] ?? $a['tipo']) ?></td>
            <td style="text-align: right;"><?= $a['valor_adquisicion'] !== null ? '$' . number_format((float)$a['valor_adquisicion'], 2) : '-' ?></td>
            <td>
                <span class="badge"><?= strtoupper($a['estado']) ?></span>
            </td>
            <td><small><?= nl2br(htmlspecialchars($a['descripcion'] ?? 'Sin descripción.')) ?></small></td>
        </tr>
    <?php endforeach; ?>
    <?php if ($salaActual !== '') echo "</tbody></table>"; ?>

</body>
</html>
