<?php
declare(strict_types=1);

$t = isset($t) && is_array($t) ? $t : [];
?><article class="card tool-panel" id="tool-erp" data-tool-panel>
    <h2><?= e((string) $t['erp_calc']) ?></h2>
    <label><?= e((string) $t['tx_power_w']) ?>
        <input type="text" inputmode="decimal" id="erp-power" data-min="0" data-step="0.1" value="10">
    </label>
    <label><?= e((string) $t['feedline_loss_db']) ?>
        <input type="text" inputmode="decimal" id="erp-loss" data-min="0" data-step="0.1" value="1.5">
    </label>
    <label><?= e((string) $t['antenna_gain_dbd']) ?>
        <input type="text" inputmode="decimal" id="erp-gain" data-step="0.1" value="3">
    </label>
    <p class="help"><?= e((string) $t['erp_result']) ?>: <strong id="erp-result">-</strong></p>
</article>
