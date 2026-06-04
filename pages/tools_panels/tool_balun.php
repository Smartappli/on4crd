<article class="card tool-panel" id="tool-balun" data-tool-panel>
    <h2><?= e((string) $t['balun_calc']) ?></h2>
    <label><?= e((string) $t['source_imp']) ?>
        <input type="text" inputmode="decimal" id="balun-source" min="1" step="0.1" value="50">
    </label>
    <label><?= e((string) $t['load_imp']) ?>
        <input type="text" inputmode="decimal" id="balun-load" min="1" step="0.1" value="200">
    </label>
    <p class="help"><?= e((string) $t['turns_ratio']) ?>: <strong id="balun-ratio">-</strong></p>
</article>
