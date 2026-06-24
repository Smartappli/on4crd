<?php
declare(strict_types=1);

$t = isset($t) && is_array($t) ? $t : [];
?><article class="card tool-panel" id="tool-swr" data-tool-panel>
    <h2><?= e((string) $t['swr_calc']) ?></h2>
    <label><?= e((string) $t['forward_power']) ?>
        <input type="text" inputmode="decimal" id="swr-forward" data-min="0.01" data-step="0.1" value="50">
    </label>
    <label><?= e((string) $t['reflected_power']) ?>
        <input type="text" inputmode="decimal" id="swr-reflected" data-min="0" data-step="0.1" value="2">
    </label>
    <p class="help"><?= e((string) $t['swr_result']) ?>: <strong id="swr-value">-</strong></p>
    <p class="help"><?= e((string) $t['return_loss']) ?>: <strong id="swr-return-loss">-</strong></p>
</article>
