<?php if (isset($totalPaginas) && $totalPaginas > 1): ?>
    <div class="pagination-info">
        Mostrando <?= count($items) ?> de <?= $total ?> <?= $label ?>
    </div>
    <div class="pagination">
        <?php if ($pagina > 1): ?>
            <a href="#" data-pagina="<?= ($pagina - 1) ?>" class="pagination-btn <?= $ajaxClass ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                Anterior
            </a>
        <?php endif; ?>

        <div class="pagination-pages">
            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                <?php if ($i == $pagina): ?>
                    <span class="pagination-btn active"><?= $i ?></span>
                <?php else: ?>
                    <a href="#" data-pagina="<?= $i ?>" class="pagination-btn <?= $ajaxClass ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>

        <?php if ($pagina < $totalPaginas): ?>
            <a href="#" data-pagina="<?= ($pagina + 1) ?>" class="pagination-btn <?= $ajaxClass ?>">
                Siguiente
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>