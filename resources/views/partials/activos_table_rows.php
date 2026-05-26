<?php if (empty($activos)): ?>
    <div class="empty-state">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
        </svg>
        <p>No se encontraron activos</p>
    </div>
<?php else: ?>
    <?php foreach ($activos as $activo): ?>
        <div class="table-row">
            <div class="table-cell cell-id" data-label="Código"><?= htmlspecialchars((string) ($activo['codigo'] ?? $activo['id']), ENT_QUOTES, 'UTF-8') ?></div>
            <div class="table-cell cell-name" data-label="Nombre"><?= htmlspecialchars((string) ($activo['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            <div class="table-cell cell-type" data-label="Tipo Activo" data-tipo-id="<?= (int) ($activo['tipo_activo_id'] ?? 0) ?>"><?= htmlspecialchars((string) ($activo['tipo'] ?? 'Sin tipo'), ENT_QUOTES, 'UTF-8') ?></div>
            <div class="table-cell cell-descripcion" data-label="Descripción"><?= htmlspecialchars((string) ($activo['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            <div class="table-cell cell-status" data-label="Estado">
                <span class="status-badge status-<?= htmlspecialchars((string) ($activo['estado'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <?= str_replace(['_', '-'], ' ', htmlspecialchars(\App\Models\Activo::ESTADOS[$activo['estado']] ?? ($activo['estado'] ?? ''), ENT_QUOTES, 'UTF-8')) ?>
                </span>
            </div>
            <div class="table-cell cell-ubicacion" data-label="Ubicación"><?= htmlspecialchars((string) ($activo['sala_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            <div class="table-cell cell-actions" data-label="Acciones">
                <a href="/sigmu/activo/ver?id=<?= (int) ($activo['id'] ?? 0) ?>" class="action-btn action-view" title="Ver detalle">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </a>
                <a href="/sigmu/activo/editar?id=<?= (int) ($activo['id'] ?? 0) ?>" class="action-btn action-edit" title="Editar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </a>
                <form method="POST" action="/sigmu/activo/eliminar" style="display: inline;" class="delete-form">
                    <?= \App\Support\Csrf::field() ?>
                    <input type="hidden" name="id" value="<?= (int) ($activo['id'] ?? 0) ?>">
                    <input type="hidden" name="sala_id" value="<?= (int) ($salaId ?? 0) ?>">
                    <button type="submit" class="action-btn action-delete" title="Eliminar">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            <line x1="10" y1="11" x2="10" y2="17"></line>
                            <line x1="14" y1="11" x2="14" y2="17"></line>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>