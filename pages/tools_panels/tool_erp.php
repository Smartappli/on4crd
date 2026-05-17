<article class="card tool-panel" id="tool-erp" data-tool-panel>
    <h2><?= e((string) $t['erp_calc']) ?></h2>
    <label><?= e((string) $t['tx_power_w']) ?>
        <input type="number" id="erp-power" min="0" step="0.1" value="10">
    </label>
    <label><?= e((string) $t['feedline_loss_db']) ?>
        <input type="number" id="erp-loss" min="0" step="0.1" value="1.5">
    </label>
    <label><?= e((string) $t['antenna_gain_dbd']) ?>
        <input type="number" id="erp-gain" step="0.1" value="3">
    </label>
    <p class="help"><?= e((string) $t['erp_result']) ?>: <strong id="erp-result">—</strong></p>
</article>
