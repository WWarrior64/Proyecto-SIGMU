<?php
declare(strict_types=1);

$sigmuPageTitle = $sigmuPageTitle ?? 'SIGMU';
$sigmuLayoutAdmin = !empty($sigmuLayoutAdmin);
$sigmuExtraCss = isset($sigmuExtraCss) && is_array($sigmuExtraCss) ? $sigmuExtraCss : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGMU — <?= htmlspecialchars((string) $sigmuPageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/assets/css/sigmu-layout.css">
    <?php foreach ($sigmuExtraCss as $href): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars((string) $href, ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>
</head>
<body class="sigmu-shell<?= $sigmuLayoutAdmin ? ' sigmu-shell--admin' : '' ?>">
<?php require __DIR__ . '/sigmu_topbar.php'; ?>
<main class="sigmu-main">
