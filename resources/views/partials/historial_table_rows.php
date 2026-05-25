<?php if (empty($historial)): ?>
    <div class="empty-state">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M9 11l3 3l8-8M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <?php if (isset($general) && $general): ?>
            <p>No hay registros en el historial general</p>
        <?php else: ?>
            <p>No hay historial disponible para este activo</p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <?php foreach ($historial as $registro): ?>
        <div class="table-row historial-row">

            <?php if (isset($general) && $general): ?>
                <!-- USUARIO (solo historial general) -->
                <div class="table-cell cell-user" data-label="Usuario">
                    <div class="user-inline">
                        <div class="user-avatar-small">
                            <?= strtoupper(substr($registro['usuario_nombre'] ?? 'U', 0, 1)) ?>
                        </div>
                        <div class="user-info">
                            <span class="user-fullname"><?= htmlspecialchars((string) ($registro['usuario_nombre'] ?? 'Usuario desconocido'), ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="user-username">@<?= htmlspecialchars((string) ($registro['usuario_username'] ?? 'usuario'), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ID -->
            <div class="table-cell cell-id" data-label="ID">
                <?= (int) ($registro['id'] ?? 0) ?>
            </div>

            <?php if (isset($general) && $general): ?>
                <!-- ACTIVO (solo historial general) -->
                <div class="table-cell" data-label="Activo">
                    <div style="display: flex; flex-direction: column; gap: 4px;">
                        <span style="font-weight: 600;"><?= htmlspecialchars((string) ($registro['activo_codigo'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></span>
                        <span style="font-size: 0.85rem; color: #6c757d;"><?= htmlspecialchars((string) ($registro['activo_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ACCIÓN / DETALLE -->
            <div class="table-cell cell-name" data-label="Acción / Detalle">
                <span class="action-badge action-<?= htmlspecialchars((string) ($registro['accion'] ?? 'desconocida'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= str_replace(['_', '-'], ' ', htmlspecialchars(ucfirst((string) ($registro['accion'] ?? 'N/A')), ENT_QUOTES, 'UTF-8')) ?>
                </span>
                <span class="detail-text">
                    <?= htmlspecialchars((string) ($registro['detalle'] ?? 'Sin detalle'), ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>

            <!-- ESTADO -->
            <div class="table-cell cell-status" data-label="Estado">
                <?php if (!empty($registro['estado_anterior']) && !empty($registro['estado_nuevo'])): ?>
                    <div class="status-changes">
                        <span class="status-old"><?= str_replace('_', ' ', htmlspecialchars((string)$registro['estado_anterior'], ENT_QUOTES, 'UTF-8')) ?></span>
                        <span class="status-arrow">→</span>
                        <span class="status-new"><?= str_replace('_', ' ', htmlspecialchars((string)$registro['estado_nuevo'], ENT_QUOTES, 'UTF-8')) ?></span>
                    </div>
                <?php elseif (!empty($registro['estado_nuevo'])): ?>
                    <span class="status-only"><?= str_replace('_', ' ', htmlspecialchars((string)$registro['estado_nuevo'], ENT_QUOTES, 'UTF-8')) ?></span>
                <?php else: ?>
                    <span class="empty-value">-</span>
                <?php endif; ?>
            </div>

            <!-- SALA ANTERIOR -->
            <div class="table-cell" data-label="Sala Anterior">
                <?php if (!empty($registro['sala_anterior_nombre'])): ?>
                    <span class="sala-anterior"><?= htmlspecialchars($registro['sala_anterior_nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php else: ?>
                    <span class="empty-value">Ninguna</span>
                <?php endif; ?>
            </div>

            <!-- SALA ACTUAL -->
            <div class="table-cell" data-label="<?= isset($general) && $general ? 'Sala Actual' : 'Ubicación' ?>">
                <?php if (!empty($registro['sala_nueva_nombre'])): ?>
                    <span class="sala-nueva"><?= htmlspecialchars($registro['sala_nueva_nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php else: ?>
                    <span class="empty-value">-</span>
                <?php endif; ?>
            </div>

            <!-- FECHA -->
            <div class="table-cell cell-date" data-label="Fecha">
                <?php if (!empty($registro['fecha']) && strtotime($registro['fecha']) !== false): ?>
                    <span><?= date('d-m-Y', strtotime($registro['fecha'])) ?></span>
                    <span class="time-text"><?= date('H:i', strtotime($registro['fecha'])) ?></span>
                <?php else: ?>
                    <span class="empty-value">Fecha no disponible</span>
                <?php endif; ?>
            </div>

            <?php if (!isset($general) || !$general): ?>
                <!-- USUARIO (solo historial individual) -->
                <div class="table-cell cell-user" data-label="Usuario">
                    <div class="user-inline">
                        <div class="user-avatar-small">
                            <?= strtoupper(substr($registro['usuario_nombre'] ?? 'U', 0, 1)) ?>
                        </div>
                        <div class="user-info">
                            <span class="user-fullname"><?= htmlspecialchars((string) ($registro['usuario_nombre'] ?? 'Usuario desconocido'), ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="user-username">@<?= htmlspecialchars((string) ($registro['usuario_username'] ?? 'usuario'), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    <?php endforeach; ?>
<?php endif; ?>