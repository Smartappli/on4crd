<article class="card tool-panel" id="tool-divider" data-tool-panel>
    <h2><?= e((string) $t['divider_calc']) ?></h2>
    <label><?= e((string) $t['vin_volts']) ?>
        <input type="text" inputmode="decimal" id="divider-vin" min="0" step="0.01" value="13.8">
    </label>
    <label><?= e((string) $t['r1_ohm']) ?>
        <input type="text" inputmode="decimal" id="divider-r1" min="0" step="1" value="10000">
    </label>
    <label><?= e((string) $t['r2_ohm']) ?>
        <input type="text" inputmode="decimal" id="divider-r2" min="0" step="1" value="2200">
    </label>
    <p class="help"><?= e((string) $t['vout_volts']) ?>: <strong id="divider-vout">-</strong></p>
</article>
