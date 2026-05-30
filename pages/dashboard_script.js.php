<?php declare(strict_types=1); ?>
<script nonce="<?= e(csp_nonce()) ?>">window.dashboardConfig = <?= json_encode($dashboardConfig, JSON_UNESCAPED_SLASHES) ?>;</script>
