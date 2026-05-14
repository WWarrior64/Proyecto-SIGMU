<?php
declare(strict_types=1);

/** @var array<string, mixed> $sessionUser */
$sessionUser = (isset($sessionUser) && is_array($sessionUser)) ? $sessionUser : [];
/** @var array<string, mixed> $edificio */
$edificio = (isset($edificio) && is_array($edificio)) ? $edificio : [];
/** @var array<int, array<string, mixed>> $salas */
$salas = (isset($salas) && is_array($salas)) ? $salas : [];

$sigmuPageTitle = 'SALAS - ' . htmlspecialchars($edificio['nombre'] ?? '');
$sigmuLayoutAdmin = false;
$sigmuExtraCss = ['/assets/css/gestion-espacios.css'];
require __DIR__ . '/../partials/sigmu_shell_start.php';
?>

    <div class="sigmu-back-row" style="margin-bottom: 1rem;">
        <button type="button" class="sigmu-back-btn" onclick="window.location.href='/sigmu/edificios'" title="Volver a edificios">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            <span style="margin-left: 8px; font-weight: 600; font-size: 1rem; color: var(--sigmu-muted);">VOLVER A EDIFICIOS</span>
        </button>
    </div>

    <div class="sigmu-card" style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 class="sigmu-page-title" style="margin-bottom: 0.25rem; text-transform: uppercase;">
                <?= htmlspecialchars((string) ($edificio['nombre'] ?? 'SALAS'), ENT_QUOTES, 'UTF-8') ?>
            </h2>
            <p style="margin: 0; color: var(--sigmu-muted); font-size: 0.95rem;">
                Salas y laboratorios en este edificio.
            </p>
        </div>
        <?php if (in_array($sessionUser['rol_nombre'] ?? '', ['Administrador', 'Responsable de Area'], true)): ?>
            <button type="button" class="sigmu-btn sigmu-btn--primary" onclick="abrirModalSala()">
                + NUEVA SALA
            </button>
        <?php endif; ?>
    </div>

    <?php if (!empty($_GET['success'])): ?>
        <div class="sigmu-alert sigmu-alert--success" style="background: #e8f5e9; color: #2e7d32; padding: 12px; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid #c8e6c9;">
            <?= htmlspecialchars((string) $_GET['success'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="espacios-grid">
        <?php foreach ($salas as $sala): ?>
            <article class="sala-card" onclick="if(!event.target.closest('.card-actions')) window.location.href='/sigmu/sala?sala_id=<?= (int) $sala['id'] ?>'">
                <div class="card-media" style="height: 100px; background: linear-gradient(135deg, #fff 0%, #f0f0f0 100%);">
                    <div style="text-align: center; color: var(--sigmu-red); opacity: 0.25;">
                        <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 3h18v18H3zM9 3v18M3 11h18M3 7h6M3 15h6M9 7h12M9 15h12"/>
                        </svg>
                    </div>
                    <span style="position: absolute; top: 12px; right: 12px; background: var(--sigmu-red); color: white; padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 800; letter-spacing: 0.05em; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        PISO <?= (int) $sala['numero_piso'] ?>
                    </span>
                </div>
                <div class="card-body">
                    <h3 class="card-title">
                        <a href="/sigmu/sala?sala_id=<?= (int) $sala['id'] ?>">
                            <?= htmlspecialchars((string) $sala['nombre'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </h3>
                    <div class="card-divider"></div>
                    <p class="card-stats">
                        <?= (int) $sala['total_activos'] ?> activos
                    </p>
                </div>
                <?php if (in_array($sessionUser['rol_nombre'] ?? '', ['Administrador', 'Responsable de Area'], true)): ?>
                    <div class="card-actions">
                        <button type="button" class="btn-icon" onclick='abrirModalSala(<?= json_encode($sala) ?>)' title="Editar">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                        </button>
                        <button type="button" class="btn-icon" style="color: var(--sigmu-red); border-color: #ffcdd2;" onclick="abrirModalEliminar(<?= (int) $sala['id'] ?>, 'sala', <?= (int) $edificio['id'] ?>)" title="Eliminar">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                        </button>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>

    <!-- Modal Sala -->
    <div id="modalSala" class="modal-overlay" data-max-pisos="<?= (int) ($edificio['cantidad_pisos'] ?? 1) ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">NUEVA SALA</h3>
                <button type="button" class="modal-close">&times;</button>
            </div>
            <form action="/sigmu/edificios/guardar-sala" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="sala_id">
                    <input type="hidden" name="edificio_id" id="sala_edificio_id" value="<?= (int) ($edificio['id'] ?? 0) ?>">
                    <div class="form-group">
                        <label>Nombre de la Sala</label>
                        <input type="text" name="nombre" id="sala_nombre" class="form-control" required placeholder="Ej: Laboratorio de Redes">
                    </div>
                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea name="descripcion" id="sala_descripcion" class="form-control" rows="3" placeholder="Opcional..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Ubicación (Piso)</label>
                        <select name="numero_piso" id="sala_piso" class="form-control" required>
                            <!-- Opciones generadas por JS -->
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="sigmu-btn sigmu-btn--secondary btn-cancel">CANCELAR</button>
                    <button type="submit" class="sigmu-btn sigmu-btn--primary">GUARDAR</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Eliminar Seguro -->
    <div id="modalEliminar" class="modal-overlay">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h3 class="modal-title">ELIMINAR</h3>
                <button type="button" class="modal-close">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p style="color: #666; font-size: 0.95rem; margin-bottom: 1.25rem;">
                        Esta acción es permanente. Por seguridad, ingrese su contraseña para confirmar.
                    </p>
                    <input type="hidden" name="id" id="eliminar_id">
                    <input type="hidden" name="edificio_id" id="eliminar_edificio_id">
                    <div class="form-group">
                        <label>Contraseña de Usuario</label>
                        <input type="password" name="password" class="form-control" required placeholder="Su contraseña actual">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="sigmu-btn sigmu-btn--secondary btn-cancel">CANCELAR</button>
                    <button type="submit" class="sigmu-btn sigmu-btn--primary" style="background: #dc3545;">CONFIRMAR ELIMINACIÓN</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/assets/js/gestion-espacios.js"></script>

<?php require __DIR__ . '/../partials/sigmu_shell_end.php';
