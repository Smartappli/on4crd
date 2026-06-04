<article class="card tool-panel" id="tool-resistor-combo" data-tool-panel>
    <h2><?= e((string) $t['resistor_combo_calc']) ?></h2>
    <label><?= e((string) $t['target_resistance_ohm']) ?>
        <input type="text" inputmode="decimal" id="resistor-target" data-min="0.1" data-step="0.1" value="1000">
    </label>
    <label><?= e((string) $t['resistor_count_max']) ?>
        <input type="number" id="resistor-max-count" min="1" max="3" step="1" value="2">
    </label>
    <p class="help"><?= e((string) $t['resistor_combo_result']) ?>: <strong id="resistor-best">-</strong></p>
</article>
