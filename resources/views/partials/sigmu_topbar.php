<?php
declare(strict_types=1);

$sigmuPageTitle = $sigmuPageTitle ?? 'SIGMU';
$sigmuLayoutAdmin = !empty($sigmuLayoutAdmin);
?>
<header class="sigmu-topbar">
    <div class="sigmu-topbar__left">
        <button type="button" class="sigmu-menu-btn" id="menuBtn" aria-label="Abrir menú lateral">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
        <img src="/assets/img/unicaes_logo.png" alt="Universidad Católica de El Salvador" class="sigmu-logo" height="40">
        <h1 class="sigmu-topbar__title"><?= htmlspecialchars((string) $sigmuPageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
    </div>
    <div class="sigmu-topbar__right">
        <?php if ($sigmuLayoutAdmin): ?>
            <span class="sigmu-admin-badge" title="Vista de administración">🔑</span>
        <?php endif; ?>
        <button type="button" class="sigmu-icon-btn sigmu-logout-btn" title="Cerrar sesión" onclick="window.location.href='/sigmu/logout'">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
        </button>
    </div>
</header>
