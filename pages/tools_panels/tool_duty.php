<article class="card tool-panel" id="tool-duty" data-tool-panel>
    <h2><?= e((string) $t['duty_cycle_calc']) ?></h2>
    <label><?= e((string) $t['tx_time_sec']) ?>
        <input type="number" id="duty-tx" min="0" step="0.1" value="30">
    </label>
    <label><?= e((string) $t['period_sec']) ?>
        <input type="number" id="duty-period" min="0" step="0.1" value="120">
    </label>
    <p class="help"><?= e((string) $t['duty_cycle_result']) ?>: <strong id="duty-result">-</strong></p>
</article>
