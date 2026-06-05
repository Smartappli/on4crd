<article class="card tool-panel" id="tool-duty" data-tool-panel>
    <h2><?= e((string) $t['duty_cycle_calc']) ?></h2>
    <label><?= e((string) $t['tx_time_sec']) ?>
        <input type="text" inputmode="decimal" id="duty-tx" data-min="0" data-step="0.1" value="30">
    </label>
    <label><?= e((string) $t['period_sec']) ?>
        <input type="text" inputmode="decimal" id="duty-period" data-min="0" data-step="0.1" value="120">
    </label>
    <p class="help"><?= e((string) $t['duty_cycle_result']) ?>: <strong id="duty-result">-</strong></p>
</article>
