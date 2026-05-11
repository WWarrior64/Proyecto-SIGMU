<?php
declare(strict_types=1);

$sigmuExtraScripts = isset($sigmuExtraScripts) && is_array($sigmuExtraScripts) ? $sigmuExtraScripts : [];
?>
</main>
<script src="/assets/js/global-menu.js"></script>
<?php foreach ($sigmuExtraScripts as $src): ?>
    <script src="<?= htmlspecialchars((string) $src, ENT_QUOTES, 'UTF-8') ?>"></script>
<?php endforeach; ?>
</body>
</html>
