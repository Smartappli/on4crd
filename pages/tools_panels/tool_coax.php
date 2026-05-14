<article class="card tool-panel" id="tool-coax" data-tool-panel>
    <h2><?= e((string) $t['coax_calc']) ?></h2>
    <label><?= e((string) $t['length_m']) ?>
        <input type="number" id="coax-length" min="0" step="0.1" value="20">
    </label>
    <label><?= e((string) $t['atten_100m']) ?>
        <input type="number" id="coax-atten" min="0" step="0.01" value="6.7">
    </label>
    <p class="help"><?= e((string) $t['coax_loss']) ?>: <strong id="coax-loss">—</strong></p>
</article>
