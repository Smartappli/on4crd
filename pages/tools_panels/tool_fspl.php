<article class="card tool-panel" id="tool-fspl" data-tool-panel>
    <h2><?= e((string) $t['fspl_calc']) ?></h2>
    <label><?= e((string) $t['distance_km']) ?>
        <input type="number" id="fspl-distance" min="0" step="0.01" value="10">
    </label>
    <label><?= e((string) $t['frequency_mhz']) ?>
        <input type="number" id="fspl-frequency" min="0" step="0.001" value="145.5">
    </label>
    <p class="help"><?= e((string) $t['fspl_result']) ?>: <strong id="fspl-loss">—</strong></p>
</article>
