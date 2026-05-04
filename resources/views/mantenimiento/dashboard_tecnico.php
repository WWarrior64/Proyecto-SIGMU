<?php
/** @var array $sessionUser */
/** @var array $asignados */
/** @var array $calendario */
/** @var int $mes */
/** @var int $anio */

$nombresMeses = [
    1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL',
    5 => 'MAYO', 6 => 'JUNIO', 7 => 'JULIO', 8 => 'AGOSTO',
    9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE'
];

$diasSemana = ['Dom', 'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab'];

$primerDiaMesTimestamp = mktime(0, 0, 0, $mes, 1, $anio);
$numeroDias = (int) date('t', $primerDiaMesTimestamp);
$diaInicio = (int) date('w', $primerDiaMesTimestamp);
$hoy = date('Y-m-d');

$eventosPorDia = [];
foreach ($calendario as $evento) {
    if (!empty($evento['fecha_agendada'])) {
        $diaEvento = (int) date('j', strtotime($evento['fecha_agendada']));
        $eventosPorDia[$diaEvento][] = $evento;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGMU - Panel Técnico</title>
    <link rel="stylesheet" href="/assets/css/mantenimiento.css">
    <style>
        .tech-dashboard { padding: 20px; max-width: 1200px; margin: 0 auto; }
        .welcome-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .report-btn { background: #8b0000; color: white; padding: 12px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: transform 0.2s; }
        .report-btn:hover { transform: translateY(-2px); background: #a50000; }
        .grid-tech { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .maint-list-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
        .maint-header { background: #8b0000; color: white; padding: 15px; font-weight: 600; display: flex; justify-content: space-between; }
        .maint-body { padding: 10px; max-height: 500px; overflow-y: auto; }
        .maint-item { padding: 12px; border-bottom: 1px solid #edf2f7; }
        .maint-tech-actions { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; }
        .status-badge { font-size: 10px; padding: 2px 8px; border-radius: 10px; font-weight: 700; text-transform: uppercase; }
        .status-pendiente { background: #fef3c7; color: #92400e; }
        .status-en_proceso { background: #dcfce7; color: #166534; }
        .btn-finish { background: #059669; color: white; border: none; padding: 6px 12px; border-radius: 6px; font-size: 11px; cursor: pointer; }
        @media (max-width: 900px) { .grid-tech { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <header class="header-bar">
        <div class="header-left">
            <button class="menu-btn" onclick="openSidebarMenu()">☰</button>
            <img src="/assets/img/unicaes_logo.png" alt="UNICAES" class="logo">
            <h1 class="header-title">PANEL DE MANTENIMIENTO</h1>
        </div>
        <div class="header-right">
            <button class="icon-btn logout-btn" title="Cerrar Sesión" onclick="window.location.href='/sigmu/logout'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </button>
        </div>
    </header>

    <main class="tech-dashboard">
        <div class="welcome-section">
            <div>
                <h2 style="margin: 0; color: #2d3748;">Hola, <?= htmlspecialchars($sessionUser['nombre_completo']) ?></h2>
                <p style="margin: 5px 0 0; color: #718096;">Técnico de Mantenimiento</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="/sigmu/mantenimiento/listado" class="report-btn" style="background: #4a5568;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    LISTADO
                </a>
                <a href="/sigmu/mantenimiento/reportar" class="report-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    REPORTAR FALLA
                </a>
            </div>
        </div>

        <div class="grid-tech">
            <!-- CALENDARIO -->
            <section class="card">
                <div class="card-header-red">MI CALENDARIO - <?= $nombresMeses[$mes] ?></div>
                <div class="calendar-container">
                    <div class="calendar-grid">
                        <?php foreach ($diasSemana as $dia): ?>
                            <div class="day-header" style="font-size: 11px;"><?= $dia ?></div>
                        <?php endforeach; ?>
                        <?php for ($i = 0; $i < $diaInicio; $i++): ?>
                            <div class="calendar-day other-month"></div>
                        <?php endfor; ?>
                        <?php for ($dia = 1; $dia <= $numeroDias; $dia++): 
                            $fechaActual = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
                            $esHoy = ($fechaActual === $hoy);
                        ?>
                            <div class="calendar-day <?= $esHoy ? 'today' : '' ?>" style="min-height: 60px;">
                                <span class="day-number"><?= $dia ?></span>
                                <div class="event-list">
                                    <?php if (isset($eventosPorDia[$dia])): ?>
                                        <?php foreach ($eventosPorDia[$dia] as $evento): ?>
                                            <div class="event-tag event-blue" style="font-size: 9px;" title="<?= htmlspecialchars($evento['activo_nombre']) ?>">
                                                <?= htmlspecialchars($evento['activo_codigo']) ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </section>

            <!-- LISTADO ASIGNADOS -->
            <section class="maint-list-card">
                <div class="maint-header">
                    MIS MANTENIMIENTOS ASIGNADOS
                </div>
                <div class="maint-body">
                    <?php if (empty($asignados)): ?>
                        <p style="text-align: center; color: #a0aec0; margin-top: 30px;">No tienes mantenimientos asignados.</p>
                    <?php else: ?>
                        <?php foreach ($asignados as $m): ?>
                            <?php
                            $fotoPath = !empty($m['foto_principal'])
                                ? '/' . ltrim((string) $m['foto_principal'], '/')
                                : 'https://upload.wikimedia.org/wikipedia/commons/e/e0/PlaceholderLC.png';
                            $desc = (string) ($m['descripcion_problema'] ?? '');
                            $descSnippet = mb_strlen($desc) > 80 ? mb_substr($desc, 0, 80) . '…' : $desc;
                            ?>
                            <article class="pending-item maint-item">
                                <div class="asset-img-container">
                                    <img src="<?= htmlspecialchars($fotoPath) ?>"
                                         alt="<?= htmlspecialchars((string) ($m['activo_codigo'] ?? '')) ?>"
                                         class="asset-img"
                                         onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/e/e0/PlaceholderLC.png'">
                                </div>
                                <div class="asset-details">
                                    <h3 class="asset-code"><?= htmlspecialchars((string) ($m['activo_codigo'] ?? '')) ?> — <?= htmlspecialchars((string) ($m['activo_nombre'] ?? '')) ?></h3>
                                    <p class="asset-location">
                                        <strong><?= htmlspecialchars((string) ($m['edificio_nombre'] ?? '')) ?></strong>
                                        — <?= htmlspecialchars((string) ($m['sala_nombre'] ?? '')) ?>
                                    </p>
                                    <p class="problem-desc" title="<?= htmlspecialchars($desc) ?>"><?= htmlspecialchars($descSnippet) ?></p>
                                    <p style="margin: 6px 0 0;">
                                        <span class="status-badge status-<?= htmlspecialchars((string) ($m['estado'] ?? '')) ?>"><?= str_replace('_', ' ', (string) ($m['estado'] ?? '')) ?></span>
                                        <?php if (!empty($m['fecha_agendada'])): ?>
                                            <span style="margin-left: 10px; font-weight: 600;"><?= date('d/m/Y', strtotime((string) $m['fecha_agendada'])) ?></span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="maint-tech-actions">
                                    <a href="/sigmu/activo/ver?id=<?= (int) ($m['activo_id'] ?? 0) ?>" class="view-btn" title="Ver activo">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </a>
                                    <?php if (($m['estado'] ?? '') === 'en_proceso' || ($m['estado'] ?? '') === 'pendiente'): ?>
                                        <button type="button" class="btn-finish" onclick="abrirModalCompletar(<?= (int) $m['id'] ?>, <?= json_encode((string) ($m['activo_codigo'] ?? ''), JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>)">COMPLETAR</button>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <!-- MODAL FINALIZAR (Reusado de listado_mantenimientos) -->
    <div class="modal-overlay" id="modalCompletar">
        <div class="modal-content" style="width: 500px;">
            <div class="modal-header">
                FINALIZAR REPARACIÓN - <span id="modalActivoCodigo"></span>
            </div>
            <form id="formCompletar">
                <input type="hidden" name="mantenimiento_id" id="mantenimiento_id_completar">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="trabajo_realizado">Descripción del trabajo realizado:</label>
                        <textarea name="notas" id="trabajo_realizado" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fecha_real">Fecha intervención:</label>
                            <input type="date" name="fecha_real" id="fecha_real" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label for="resultado">Resultado:</label>
                            <select name="resultado" id="resultado" class="form-control" required>
                                <option value="resuelto">Resuelto (Vuelve a Activo)</option>
                                <option value="parcial">Parcial (Sigue en Reparación)</option>
                                <option value="no_resuelto">No Resuelto</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="observaciones">Observaciones:</label>
                        <textarea name="observaciones" id="observaciones" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="cerrarModalCompletar()">Cancelar</button>
                    <button type="submit" class="btn-primary" style="background: #059669;">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/assets/js/global-menu.js"></script>
    <script>
        function abrirModalCompletar(id, codigo) {
            document.getElementById('mantenimiento_id_completar').value = id;
            document.getElementById('modalActivoCodigo').textContent = codigo;
            document.getElementById('modalCompletar').style.display = 'flex';
        }
        function cerrarModalCompletar() {
            document.getElementById('modalCompletar').style.display = 'none';
        }
        document.getElementById('formCompletar').addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('/sigmu/mantenimiento/completar', {
                method: 'POST',
                body: new FormData(this)
            }).then(r => r.json()).then(data => {
                if (data.success) { alert('Guardado con éxito'); location.reload(); }
                else alert('Error: ' + data.message);
            });
        });
    </script>
</body>
</html>
