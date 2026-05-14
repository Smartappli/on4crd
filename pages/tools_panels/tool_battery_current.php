<article class="card tool-panel" id="tool-battery-current" data-tool-panel>
    <h2><?= e((string) $t['battery_current_calc']) ?></h2>
    <label><?= e((string) $t['battery_voltage_v']) ?>
        <input type="number" id="battery-voltage" min="0.1" step="0.1" value="13.8">
    </label>
    <label><?= e((string) $t['load_power_w']) ?>
        <input type="number" id="battery-load" min="0" step="0.1" value="50">
    </label>
    <p class="help"><?= e((string) $t['battery_current_a']) ?>: <strong id="battery-current">—</strong></p>
</article>
