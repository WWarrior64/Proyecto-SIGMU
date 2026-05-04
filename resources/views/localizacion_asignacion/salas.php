<?php
declare(strict_types=1);

$edificioId = isset($edificioId) ? (int) $edificioId : 0;
$salas = (isset($salas) && is_array($salas)) ? $salas : [];

$sigmuPageTitle = 'SALAS';
$sigmuLayoutAdmin = false;
require __DIR__ . '/../partials/sigmu_shell_start.php';
?>

    <div class="sigmu-back-row">
        <button type="button" class="sigmu-back-btn" onclick="window.location.href='/sigmu/edificios'" title="Volver a edificios">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
        </button>
    </div>

    <div class="sigmu-card" style="margin-bottom: 1.25rem;">
        <h2 class="sigmu-page-title" style="margin-bottom: 0.35rem;">Salas del edificio</h2>
        <p style="margin: 0; color: var(--sigmu-muted); font-size: 0.95rem;">Edificio ID <?= $edificioId ?> · Seleccione una sala para ver los activos.</p>
    </div>

    <?php if (!$salas): ?>
        <div class="sigmu-card">
            <p style="margin: 0; color: var(--sigmu-muted);">No hay salas para este edificio o no tiene acceso.</p>
        </div>
    <?php else: ?>
        <ul style="list-style: none; padding: 0; margin: 0; display: grid; gap: 12px;">
            <?php foreach ($salas as $sala): ?>
                <li>
                    <a href="/sigmu/sala?sala_id=<?= (int) $sala['id'] ?>" class="sigmu-card" style="display: block; text-decoration: none; color: inherit; transition: box-shadow 0.2s;">
                        <strong style="color: var(--sigmu-red);"><?= htmlspecialchars((string) $sala['nombre'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span style="color: var(--sigmu-muted); margin-left: 8px;">· Piso <?= (int) $sala['numero_piso'] ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

<?php require __DIR__ . '/../partials/sigmu_shell_end.php';
