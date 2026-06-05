<article class="card tool-panel" id="tool-runtime" data-tool-panel>
    <h2><?= e((string) $t['runtime_calc']) ?></h2>
    <label><?= e((string) ($t['capacity_mah'] ?? 'Battery capacity (mAh)')) ?>
        <input type="text" inputmode="decimal" id="runtime-capacity" data-min="0" data-step="1" value="2200">
    </label>
    <label><?= e((string) $t['current_ma']) ?>
        <input type="text" inputmode="decimal" id="runtime-current" data-min="0" data-step="1" value="500">
    </label>
    <p class="help"><?= e((string) $t['runtime_result']) ?>: <strong id="runtime-hours">-</strong></p>
</article>
