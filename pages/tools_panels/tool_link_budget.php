<?php
declare(strict_types=1);

$t = isset($t) && is_array($t) ? $t : [];
?><article id="tool-link-budget" data-tool-panel class="card tool-panel is-hidden">
    <h2><?= e((string) ($t['link_budget_calc'] ?? 'Link budget')) ?></h2>
    <p class="help"><?= e((string) ($t['link_budget_help'] ?? 'Received power: Prx = Ptx + Gtx + Grx - losses.')) ?></p>
    <div class="grid-auto">
        <label><?= e((string) ($t['tx_power_dbm'] ?? 'Ptx (dBm)')) ?>
            <input id="lb-ptx" type="text" inputmode="decimal" data-step="0.1" placeholder="30">
        </label>
        <label><?= e((string) ($t['tx_gain_dbi'] ?? 'Gtx (dBi)')) ?>
            <input id="lb-gtx" type="text" inputmode="decimal" data-step="0.1" placeholder="6">
        </label>
        <label><?= e((string) ($t['rx_gain_dbi'] ?? 'Grx (dBi)')) ?>
            <input id="lb-grx" type="text" inputmode="decimal" data-step="0.1" placeholder="6">
        </label>
        <label><?= e((string) ($t['total_losses_db'] ?? 'Total losses (dB)')) ?>
            <input id="lb-loss" type="text" inputmode="decimal" data-step="0.1" data-min="0" placeholder="110">
        </label>
    </div>
    <p><strong><?= e((string) ($t['rx_power_est'] ?? 'Estimated Prx')) ?>:</strong> <span id="lb-prx">-</span></p>
</article>
