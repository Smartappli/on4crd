<article class="card tool-panel" id="tool-solar" data-tool-panel>
    <h2><?= e((string) $t['solar_calc']) ?></h2>
    <label><?= e((string) $t['panel_watts']) ?>
        <input type="number" id="solar-watts" min="0" step="1" value="100">
    </label>
    <label><?= e((string) $t['sun_hours']) ?>
        <input type="number" id="solar-hours" min="0" step="0.1" value="4">
    </label>
    <p class="help"><?= e((string) $t['daily_energy_wh']) ?>: <strong id="solar-energy">—</strong></p>
</article>
