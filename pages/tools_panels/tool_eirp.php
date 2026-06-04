<article class="card tool-panel" id="tool-eirp" data-tool-panel>
    <h2><?= e((string) $t['eirp_calc']) ?></h2>
    <label><?= e((string) $t['erp_input_w']) ?>
        <input type="text" inputmode="decimal" id="eirp-erp" data-min="0" data-step="0.1" value="10">
    </label>
    <p class="help"><?= e((string) $t['eirp_result_w']) ?>: <strong id="eirp-result">-</strong></p>
</article>
