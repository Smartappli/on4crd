<article class="card tool-panel" id="tool-runtime" data-tool-panel>
    <h2><?= e((string) $t['runtime_calc']) ?></h2>
    <label><?= e((string) $t['battery_mah']) ?>
        <input type="number" id="runtime-capacity" min="0" step="1" value="2200">
    </label>
    <label><?= e((string) $t['current_ma']) ?>
        <input type="number" id="runtime-current" min="0" step="1" value="500">
    </label>
    <p class="help"><?= e((string) $t['runtime_result']) ?>: <strong id="runtime-hours">—</strong></p>
</article>
