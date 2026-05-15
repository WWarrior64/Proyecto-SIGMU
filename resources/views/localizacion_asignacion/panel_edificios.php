<?php
declare(strict_types=1);

/** @var array<string, mixed> $sessionUser */
$sessionUser = (isset($sessionUser) && is_array($sessionUser)) ? $sessionUser : [];
/** @var array<int, array<string, mixed>> $edificios */
$edificios = (isset($edificios) && is_array($edificios)) ? $edificios : [];
/** @var string|null $error */
$error = isset($error) ? (string) $error : null;

$sigmuPageTitle = 'EDIFICIOS';
$sigmuLayoutAdmin = (\App\Support\Roles::is($sessionUser['rol_id'] ?? 0, \App\Support\Roles::ADMIN));
$sigmuExtraCss = ['/assets/css/gestion-espacios.css'];
require __DIR__ . '/../partials/sigmu_shell_start.php';
?>

    <?php if (\App\Support\Roles::is($sessionUser['rol_id'] ?? 0, \App\Support\Roles::ADMIN)): ?>
    <div class="sigmu-back-row">
        <button type="button" class="sigmu-back-btn" onclick="window.location.href='/sigmu'" title="Volver al panel administrador">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
        </button>
    </div>
    <?php endif; ?>

    <div class="sigmu-card" style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 class="sigmu-page-title" style="margin-bottom: 0.25rem;">ESPACIOS</h2>
            <p style="margin: 0; color: var(--sigmu-muted); font-size: 0.95rem;">
                Jerarquía: edificio → sala → activos.
            </p>
        </div>
        <?php if (\App\Support\Roles::in($sessionUser['rol_id'] ?? 0, [\App\Support\Roles::ADMIN, \App\Support\Roles::RESPONSABLE_AREA])): ?>
            <button type="button" class="sigmu-btn sigmu-btn--primary" onclick="abrirModalEdificio()">
                + NUEVO EDIFICIO
            </button>
        <?php endif; ?>
    </div>

    <?php if (!empty($_GET['success'])): ?>
        <div class="sigmu-alert sigmu-alert--success" style="background: #e8f5e9; color: #2e7d32; padding: 12px; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid #c8e6c9;">
            <?= htmlspecialchars((string) $_GET['success'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['error']) || $error): ?>
        <div class="sigmu-alert sigmu-alert--error" style="margin-bottom: 1.5rem;">
            <?= htmlspecialchars((string) ($_GET['error'] ?? $error), ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="espacios-grid">
        <?php foreach ($edificios as $edificio): ?>
            <article class="edificio-card" onclick="if(!event.target.closest('.card-actions')) window.location.href='/sigmu/edificio?edificio_id=<?= (int) $edificio['id'] ?>'">
                <div class="card-media">
                    <?php if (!empty($edificio['foto'])): ?>
                        <img src="/<?= htmlspecialchars((string) $edificio['foto'], ENT_QUOTES, 'UTF-8') ?>"
                             alt="<?= htmlspecialchars((string) $edificio['nombre'], ENT_QUOTES, 'UTF-8') ?>">
                    <?php else: ?>
                        <div style="text-align: center; color: #999;">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                            <p style="margin: 0.5rem 0 0; font-size: 0.85rem; font-weight: 500;">SIN FOTO</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <h3 class="card-title">
                        <a href="/sigmu/edificio?edificio_id=<?= (int) $edificio['id'] ?>">
                            <?= htmlspecialchars((string) $edificio['nombre'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </h3>
                    <?php if (\App\Support\Roles::is($sessionUser['rol_id'] ?? 0, \App\Support\Roles::ADMIN)): ?>
                        <p style="margin: 4px 0; font-size: 0.85rem; color: var(--sigmu-muted);">
                            Responsable: <strong><?= htmlspecialchars($edificio['responsable_nombre'] ?? 'Sin asignar') ?></strong>
                        </p>
                    <?php endif; ?>
                    <div class="card-divider"></div>
                    <p class="card-stats">
                        <?= (int) $edificio['total_activos'] ?> activos
                    </p>
                </div>
                <?php if (\App\Support\Roles::in($sessionUser['rol_id'] ?? 0, [\App\Support\Roles::ADMIN, \App\Support\Roles::RESPONSABLE_AREA])): ?>
                    <div class="card-actions">
                        <button type="button" class="btn-icon" onclick="abrirModalFoto(<?= (int) $edificio['id'] ?>)" title="Cambiar foto">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                        </button>
                        <button type="button" class="btn-icon" onclick='abrirModalEdificio(<?= json_encode($edificio) ?>)' title="Editar">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                        </button>
                        <button type="button" class="btn-icon" style="color: var(--sigmu-red); border-color: #ffcdd2;" onclick="abrirModalEliminar(<?= (int) $edificio['id'] ?>, 'edificio')" title="Eliminar">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                        </button>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>

    <!-- Modal Edificio -->
    <div id="modalEdificio" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">NUEVO EDIFICIO</h3>
                <button type="button" class="modal-close">&times;</button>
            </div>
            <form action="/sigmu/edificios/guardar" method="POST" enctype="multipart/form-data">
                <?= \App\Support\Csrf::field() ?>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edificio_id">
                    <div class="form-group">
                        <label>Nombre del Edificio</label>
                        <input type="text" name="nombre" id="edificio_nombre" class="form-control" required placeholder="Ej: Biblioteca Central">
                    </div>
                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea name="descripcion" id="edificio_descripcion" class="form-control" rows="3" placeholder="Opcional..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Cantidad de Pisos</label>
                        <input type="number" name="cantidad_pisos" id="edificio_pisos" class="form-control" value="1" min="1">
                    </div>

                    <?php if (\App\Support\Roles::is($sessionUser['rol_id'] ?? 0, \App\Support\Roles::ADMIN)): ?>
                        <div class="form-group">
                            <label>Responsable de Área</label>
                            <select name="responsable_id" id="edificio_responsable_id" class="form-control">
                                <option value="0">-- Sin asignar --</option>
                                <?php foreach ($responsables ?? [] as $resp): ?>
                                    <option value="<?= (int)$resp['id'] ?>">
                                        <?= htmlspecialchars($resp['nombre_completo']) ?> (<?= htmlspecialchars($resp['username']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Foto representativa (Opcional)</label>
                        <input type="file" name="foto" class="form-control" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="sigmu-btn sigmu-btn--secondary btn-cancel">CANCELAR</button>
                    <button type="submit" class="sigmu-btn sigmu-btn--primary">GUARDAR</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Foto Rápida -->
    <div id="modalFoto" class="modal-overlay">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h3>CAMBIAR FOTO</h3>
                <button type="button" class="modal-close">&times;</button>
            </div>
            <form action="/sigmu/edificio/actualizar-foto" method="POST" enctype="multipart/form-data">
                <?= \App\Support\Csrf::field() ?>
                <div class="modal-body">
                    <input type="hidden" name="edificio_id" id="foto_edificio_id">
                    <div class="form-group">
                        <label>Seleccionar nueva imagen</label>
                        <input type="file" name="foto" class="form-control" accept="image/*" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="sigmu-btn sigmu-btn--secondary btn-cancel">CANCELAR</button>
                    <button type="submit" class="sigmu-btn sigmu-btn--primary">SUBIR</button>
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
                <?= \App\Support\Csrf::field() ?>
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
