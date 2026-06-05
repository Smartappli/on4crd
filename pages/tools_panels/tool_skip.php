<article class="card tool-panel" id="tool-skip" data-tool-panel>
    <h2><?= e((string) $t['skip_calc']) ?></h2>
    <label><?= e((string) $t['virtual_height_km']) ?>
        <input type="text" inputmode="decimal" id="skip-height" data-min="1" data-step="1" value="300">
    </label>
    <label><?= e((string) $t['incidence_deg']) ?>
        <input type="text" inputmode="decimal" id="skip-angle" data-min="1" data-max="89" data-step="0.1" value="30">
    </label>
    <p class="help"><?= e((string) $t['skip_result_km']) ?>: <strong id="skip-result">-</strong></p>
</article>
