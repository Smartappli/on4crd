<?php declare(strict_types=1); ?>
<script nonce="<?= e(csp_nonce()) ?>">
<?php include __DIR__ . '/dashboard_script_offcanvas.js.php'; ?>
<?php include __DIR__ . '/dashboard_script_fullscreen.js.php'; ?>
<?php include __DIR__ . '/dashboard_script_loader.js.php'; ?>
</script>
<script nonce="<?= e(csp_nonce()) ?>">window.dashboardConfig = <?= json_encode($dashboardConfig, JSON_UNESCAPED_SLASHES) ?>;</script>
