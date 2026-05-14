<article class="card tool-panel" id="tool-erp" data-tool-panel>
    <h2><?= e($labelErpCalc) ?></h2>
    <label><?= e($labelTxPowerW) ?>
        <input type="number" id="erp-power" min="0" step="0.1" value="10">
    </label>
    <label><?= e($labelFeedlineLossDb) ?>
        <input type="number" id="erp-loss" min="0" step="0.1" value="1.5">
    </label>
    <label><?= e($labelAntennaGainDbd) ?>
        <input type="number" id="erp-gain" step="0.1" value="3">
    </label>
    <p class="help"><?= e($labelErpResult) ?>: <strong id="erp-result">—</strong></p>
</article>
