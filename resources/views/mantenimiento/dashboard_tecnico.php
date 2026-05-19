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

$sigmuPageTitle = 'PANEL TÉCNICO';
$sigmuLayoutAdmin = false;
$sigmuExtraCss = ['/assets/css/mantenimiento.css', '/assets/css/dashboard-tecnico.css'];
$sigmuExtraScripts = ['/assets/js/dashboard-tecnico.js'];
require __DIR__ . '/../partials/sigmu_shell_start.php';
?>

    <div class="tech-dashboard">
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
                                        <span class="status-badge status-<?= htmlspecialchars((string) ($m['estado'] ?? '')) ?>"><?= str_replace(['_', '-'], ' ', (string) ($m['estado'] ?? '')) ?></span>
                                        <?php if (!empty($m['fecha_agendada'])): ?>
                                            <span style="margin-left: 10px; font-weight: 600;"><?= date('d/m/Y', strtotime((string) $m['fecha_agendada'])) ?></span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="maint-tech-actions">
                                    <a href="/sigmu/mantenimiento/activo/ver?id=<?= (int) ($m['activo_id'] ?? 0) ?>" class="view-btn" title="Ver detalle del activo">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </a>
                                    <?php if (($m['estado'] ?? '') === 'en_proceso' || ($m['estado'] ?? '') === 'pendiente'): ?>
                                        <button
                                            type="button"
                                            class="btn-finish"
                                            data-mantenimiento-id="<?= (int) $m['id'] ?>"
                                            data-activo-codigo="<?= htmlspecialchars((string) ($m['activo_codigo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            onclick="abrirModalCompletarDesdeBoton(this)"
                                        >
                                            COMPLETAR
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>

    <!-- MODAL FINALIZAR (Reusado de listado_mantenimientos) -->
    <div class="modal-overlay" id="modalCompletar">
        <div class="modal-content" style="width: 500px;">
            <div class="modal-header">
                FINALIZAR REPARACIÓN - <span id="modalActivoCodigo"></span>
            </div>
            <form id="formCompletar">
                <?= \App\Support\Csrf::field() ?>
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

<?php require __DIR__ . '/../partials/sigmu_shell_end.php';
