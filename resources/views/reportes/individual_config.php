<?php
/**
 * @var array $activo
 */
declare(strict_types=1);

$sigmuPageTitle = 'CONFIGURAR REPORTE';
$sigmuLayoutAdmin = false;
$sigmuExtraCss = ['/assets/css/reportes.css'];
$sigmuExtraJs = ['/assets/js/reportes.js'];
require __DIR__ . '/../partials/sigmu_shell_start.php';

$user = \App\Support\Session::get('auth_user');
?>

<div class="report-config-card" style="max-width: 900px; margin: 0 auto;">
    <div class="sigmu-back-row">
        <button class="sigmu-back-btn" onclick="window.location.href='/sigmu/activo/ver?id=<?= (int) ($activo['id'] ?? 0) ?>'">
            <i class="fas fa-arrow-left"></i> Volver al Activo
        </button>
    </div>

    <div class="section-header">
        <h1 class="section-title"><i class="fas fa-file-pdf"></i> Reporte Individual de Activo</h1>
    </div>

    <div class="sigmu-alert sigmu-alert--info">
        <i class="fas fa-info-circle"></i> Configurando reporte para: <strong><?= htmlspecialchars($activo['nombre']) ?> (<?= htmlspecialchars($activo['codigo']) ?>)</strong>
    </div>

    <form action="/sigmu/reporte/individual/exportar" method="POST" id="reportForm">
        <input type="hidden" name="activo_id" value="<?= $activo['id'] ?>">
        
        <div class="detail-group">
            <label class="detail-label"><i class="fas fa-tasks"></i> Secciones a incluir en el documento</label>
            
            <div class="sections-list">
                <label class="list-group-item">
                    <input class="form-check-input" type="checkbox" name="datos_generales" checked value="1">
                    <div>
                        <strong>Datos Generales</strong>
                        <small class="text-muted">Información básica, código, tipo, estado actual y ubicación exacta.</small>
                    </div>
                </label>
                
                <label class="list-group-item">
                    <input class="form-check-input" type="checkbox" name="historial" checked value="1">
                    <div>
                        <strong>Historial de Movimientos</strong>
                        <small class="text-muted">Registro cronológico de traslados entre salas y cambios de estado.</small>
                    </div>
                </label>
                
                <label class="list-group-item">
                    <input class="form-check-input" type="checkbox" name="mantenimientos" checked value="1">
                    <div>
                        <strong>Historial de Mantenimientos</strong>
                        <small class="text-muted">Detalle de fallas reportadas, técnicos asignados y acciones correctivas.</small>
                    </div>
                </label>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn-reporte">
                <i class="fas fa-download"></i>
                GENERAR Y DESCARGAR REPORTE PDF
            </button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../partials/sigmu_shell_end.php'; ?>
