<article class="card tool-panel" id="tool-bandwidth" data-tool-panel>
    <h2><?= e((string) $t['bandwidth_calc']) ?></h2>
    <label><?= e((string) $t['mode_rate']) ?>
        <input type="text" inputmode="decimal" id="bandwidth-rate" data-min="0" data-step="1" value="1200">
    </label>
    <label><?= e((string) $t['rolloff_factor']) ?>
        <input type="text" inputmode="decimal" id="bandwidth-rolloff" data-min="0" data-step="0.01" value="0.35">
    </label>
    <p class="help"><?= e((string) $t['bandwidth_result']) ?>: <strong id="bandwidth-result">-</strong></p>
</article>
