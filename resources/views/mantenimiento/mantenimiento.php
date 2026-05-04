<?php
/** @var array $sessionUser */
/** @var array $calendario */
/** @var array $pendientes */
/** @var array $tecnicos */
/** @var array $stats */
/** @var int $mes */
/** @var int $anio */

$nombresMeses = [
    1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL',
    5 => 'MAYO', 6 => 'JUNIO', 7 => 'JULIO', 8 => 'AGOSTO',
    9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE'
];

$diasSemana = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

/**
 * LÓGICA DE CALENDARIO
 * mktime(hour, minute, second, month, day, year)
 */
$primerDiaMesTimestamp = mktime(0, 0, 0, $mes, 1, $anio);
$numeroDias = (int) date('t', $primerDiaMesTimestamp);
$diaInicio = (int) date('w', $primerDiaMesTimestamp); // 0 (Dom) a 6 (Sab)
$hoy = date('Y-m-d');

// Agrupar eventos por día
$eventosPorDia = [];
foreach ($calendario as $evento) {
    if (!empty($evento['fecha_agendada'])) {
        $diaEvento = (int) date('j', strtotime($evento['fecha_agendada']));
        $eventosPorDia[$diaEvento][] = $evento;
    }
}

// Clases de colores para eventos
$colores = ['event-blue', 'event-red', 'event-green', 'event-purple', 'event-orange'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGMU - Mantenimiento</title>
    <link rel="stylesheet" href="/assets/css/mantenimiento.css">
</head>
<body>

    <!-- BARRA SUPERIOR -->
    <header class="header-bar">
        <div class="header-left">
            <button class="menu-btn" id="menuBtn" onclick="openSidebarMenu()">☰</button>
            <img src="/assets/img/unicaes_logo.png" alt="UNICAES" class="logo">
            <h1 class="header-title">MANTENIMIENTO</h1>
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

    <div class="back-btn-container">
        <button class="back-btn" onclick="if(document.referrer.indexOf(window.location.host) !== -1) { history.back(); } else { window.location.href='/sigmu'; }" title="Regresar">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
        </button>
    </div>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-container">
        <div class="layout-grid">
            
            <!-- IZQUIERDA: CALENDARIO -->
            <section class="card">
                <div class="card-header-red">
                    CALENDARIO DE REPARACIONES - <?= $nombresMeses[$mes] ?> <?= $anio ?>
                </div>
                <div class="calendar-container">
                    <div class="calendar-grid">
                        <?php foreach ($diasSemana as $diaNombre): ?>
                            <div class="day-header"><?= $diaNombre ?></div>
                        <?php endforeach; ?>

                        <!-- Espacios vacíos antes del inicio del mes -->
                        <?php for ($i = 0; $i < $diaInicio; $i++): ?>
                            <div class="calendar-day other-month"></div>
                        <?php endfor; ?>

                        <!-- Días del mes -->
                        <?php for ($dia = 1; $dia <= $numeroDias; $dia++): 
                            $fechaActual = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
                            $esHoy = ($fechaActual === $hoy);
                        ?>
                            <div class="calendar-day <?= $esHoy ? 'today' : '' ?>">
                                <span class="day-number"><?= $dia ?></span>
                                <div class="event-list">
                                    <?php if (isset($eventosPorDia[$dia])): ?>
                                        <?php foreach ($eventosPorDia[$dia] as $idx => $evento): ?>
                                            <div class="event-tag <?= $colores[$idx % count($colores)] ?>" 
                                                 title="<?= htmlspecialchars($evento['activo_nombre'] . ': ' . $evento['descripcion_problema']) ?>">
                                                <?= htmlspecialchars($evento['activo_codigo']) ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endfor; ?>

                        <!-- Espacios vacíos después del fin del mes -->
                        <?php 
                        $totalCeldas = $diaInicio + $numeroDias;
                        $restante = (7 - ($totalCeldas % 7)) % 7;
                        if ($restante > 0 && $restante < 7):
                            for ($i = 0; $i < $restante; $i++): ?>
                                <div class="calendar-day other-month"></div>
                            <?php endfor;
                        endif; ?>
                    </div>
                </div>
            </section>

            <!-- DERECHA: LISTA PENDIENTES -->
            <section class="card">
                <div class="card-header-red">
                    <span>PENDIENTES DE REPARACIÓN</span>
                    <a href="/sigmu/mantenimiento/listado" class="view-all-btn" style="color: white; font-size: 11px; text-decoration: underline; font-weight: 500;">
                        VER LISTADO COMPLETO
                    </a>
                </div>
                <div class="card-subheader" style="background: #8b0000; padding: 0 15px 10px 15px;">
                    <div class="list-stats-panel">
                        <div class="stat-item">
                            <span class="stat-dot yellow"></span>
                            Programados: <?= $stats['programados'] ?>
                        </div>
                        <div class="stat-item">
                            <span class="stat-dot green"></span>
                            Técnicos: <?= $stats['tecnicos'] ?>
                        </div>
                    </div>
                </div>

                <div class="pending-list">
                    <?php if (empty($pendientes)): ?>
                        <p style="text-align: center; color: #718096; margin-top: 40px;">No hay activos pendientes de reparación.</p>
                    <?php else: ?>
                        <?php foreach ($pendientes as $item): ?>
                            <article class="pending-item">
                                <div class="asset-img-container">
                                    <?php 
                                        $fotoPath = !empty($item['foto_principal']) 
                                            ? '/' . ltrim($item['foto_principal'], '/') 
                                            : 'https://upload.wikimedia.org/wikipedia/commons/e/e0/PlaceholderLC.png';
                                    ?>
                                    <img src="<?= $fotoPath ?>" 
                                         alt="<?= htmlspecialchars($item['activo_codigo']) ?>" 
                                         class="asset-img"
                                         onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/e/e0/PlaceholderLC.png'">
                                </div>
                                <div class="asset-details">
                                    <h3 class="asset-code"><?= htmlspecialchars($item['activo_codigo']) ?> - <?= htmlspecialchars($item['activo_nombre']) ?></h3>
                                    <p class="asset-location">
                                        <strong><?= htmlspecialchars($item['edificio_nombre']) ?></strong> - <?= htmlspecialchars($item['sala_nombre'] ?? 'Sin sala') ?>
                                    </p>
                                    <p class="problem-desc" title="<?= htmlspecialchars($item['descripcion_problema']) ?>">
                                        <?= htmlspecialchars($item['descripcion_problema']) ?>
                                    </p>
                                </div>
                                <div class="action-container">
                                    <a href="/sigmu/activo/ver?id=<?= (int)$item['activo_id'] ?>" class="view-btn" title="Ver detalle del activo">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </a>
                                    <button class="program-btn" 
                                            data-id="<?= $item['id'] ?>" 
                                            data-code="<?= htmlspecialchars($item['activo_codigo']) ?>"
                                            title="Agendar reparación">
                                        Programar
                                    </button>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <!-- MODAL AGENDAR -->
    <div class="modal-overlay" id="modalProgramar">
        <div class="modal-content">
            <div class="modal-header" id="modalTitle">
                AGENDAR REPARACIÓN
            </div>
            <form id="formProgramar">
                <input type="hidden" name="mantenimiento_id" id="mantenimiento_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="tecnico_id">Usuario técnico:</label>
                        <select name="tecnico_id" id="tecnico_id" class="form-control" required>
                            <option value="">Seleccione un técnico...</option>
                            <?php foreach ($tecnicos as $tec): ?>
                                <option value="<?= $tec['id'] ?>"><?= htmlspecialchars($tec['nombre_completo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="fecha">Fecha:</label>
                            <input type="date" name="fecha" id="fecha" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label for="hora">Hora:</label>
                            <input type="time" name="hora" id="hora" class="form-control" value="08:00">
                        </div>
                        <div class="form-group">
                            <label for="duracion">Duración:</label>
                            <select name="duracion" id="duracion" class="form-control">
                                <option value="00:30">30 min</option>
                                <option value="01:00">1 h</option>
                                <option value="02:00">2 h</option>
                                <option value="04:00">4 h</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notas">Nota de intervención:</label>
                        <textarea name="notas" id="notas" class="form-control" rows="3" placeholder="Notas adicionales..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" id="closeModal">Cancelar</button>
                    <button type="submit" class="btn-primary">Confirmar Programación</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/assets/js/global-menu.js"></script>
    <script src="/assets/js/mantenimiento.js"></script>
</body>
</html>
