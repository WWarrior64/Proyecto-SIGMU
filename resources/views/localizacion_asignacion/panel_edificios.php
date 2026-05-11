<?php
declare(strict_types=1);

/** @var array<string, mixed> $sessionUser */
$sessionUser = (isset($sessionUser) && is_array($sessionUser)) ? $sessionUser : [];
/** @var array<int, array<string, mixed>> $edificios */
$edificios = (isset($edificios) && is_array($edificios)) ? $edificios : [];
/** @var string|null $error */
$error = isset($error) ? (string) $error : null;

$sigmuPageTitle = 'EDIFICIOS';
$sigmuLayoutAdmin = (($sessionUser['rol_nombre'] ?? '') === 'Administrador');
$sigmuExtraCss = [];
require __DIR__ . '/../partials/sigmu_shell_start.php';
?>

    <?php if (!empty($sessionUser['rol_nombre']) && $sessionUser['rol_nombre'] === 'Administrador'): ?>
    <div class="sigmu-back-row">
        <button type="button" class="sigmu-back-btn" onclick="window.location.href='/sigmu'" title="Volver al panel administrador">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
        </button>
    </div>
    <?php endif; ?>

    <div class="sigmu-card" style="margin-bottom: 1.25rem;">
        <h2 class="sigmu-page-title" style="margin-bottom: 0.5rem;">Localización y asignación</h2>
        <p style="margin: 0; color: var(--sigmu-muted); font-size: 0.95rem;">
            Jerarquía: edificio → sala → activos.
            <?php if (!empty($sessionUser['nombre_completo'])): ?>
                Sesión: <strong><?= htmlspecialchars((string) $sessionUser['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></strong>
                (<?= htmlspecialchars((string) ($sessionUser['rol_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>).
            <?php endif; ?>
        </p>
    </div>

    <?php if (!empty($_GET['error'])): ?>
        <div class="sigmu-alert sigmu-alert--error"><?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!empty($_GET['info'])): ?>
        <div class="sigmu-alert sigmu-alert--info"><?= htmlspecialchars((string) $_GET['info'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="sigmu-alert sigmu-alert--error">Error BD: <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <h3 class="sigmu-page-title" style="font-size: 1.2rem;">Edificios accesibles</h3>

    <?php if (!$edificios): ?>
        <div class="sigmu-card">
            <p style="margin: 0; color: var(--sigmu-muted);">No hay edificios asignados para este usuario.</p>
        </div>
    <?php else: ?>
        <div class="sigmu-grid-cards">
            <?php foreach ($edificios as $edificio): ?>
                <article class="sigmu-entity-card">
                    <div class="sigmu-entity-card__media">
                        <?php if (!empty($edificio['foto'])): ?>
                            <img src="/<?= htmlspecialchars((string) $edificio['foto'], ENT_QUOTES, 'UTF-8') ?>"
                                 alt="<?= htmlspecialchars((string) $edificio['nombre'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php else: ?>
                            <div style="text-align: center; color: #999;">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                                </svg>
                                <p style="margin: 0.5rem 0 0; font-size: 0.85rem;">Sin foto</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="sigmu-entity-card__body">
                        <h3>
                            <a href="/sigmu/edificio?edificio_id=<?= (int) $edificio['id'] ?>">
                                <?= htmlspecialchars((string) $edificio['nombre'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </h3>
                        <p style="font-size: 0.9rem; color: var(--sigmu-muted); margin: 0 0 0.75rem;">
                            Pisos: <?= (int) $edificio['cantidad_pisos'] ?> · Salas: <?= (int) $edificio['total_salas'] ?>
                        </p>

                        <?php if (in_array($sessionUser['rol_nombre'] ?? '', ['Administrador', 'Responsable de Area'], true)): ?>
                            <div style="text-align: right;">
                                <button type="button" onclick="toggleUploadForm(<?= (int) $edificio['id'] ?>)" style="background: none; border: 1px solid var(--sigmu-red); color: var(--sigmu-red); padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.8rem;">
                                    <?= !empty($edificio['foto']) ? 'Cambiar foto' : 'Agregar foto' ?>
                                </button>
                            </div>

                            <form id="form-upload-<?= (int) $edificio['id'] ?>" action="/sigmu/edificio/actualizar-foto" method="POST" enctype="multipart/form-data" style="margin-top: 12px; border-top: 1px solid var(--sigmu-border); padding-top: 12px; display: none;">
                                <label style="font-size: 0.85rem; display: block; margin-bottom: 6px;">Seleccionar imagen</label>
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <input type="hidden" name="edificio_id" value="<?= (int) $edificio['id'] ?>">
                                    <input type="file" name="foto" accept="image/*" required style="font-size: 0.85rem; width: 100%;">
                                    <div style="display: flex; gap: 8px;">
                                        <button type="submit" style="background: #2e7d32; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; flex: 1;">Subir</button>
                                        <button type="button" onclick="toggleUploadForm(<?= (int) $edificio['id'] ?>)" style="background: #757575; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-size: 0.85rem;">Cancelar</button>
                                    </div>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php require __DIR__ . '/../partials/sigmu_shell_end.php';
