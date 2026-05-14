<article class="card tool-panel" id="tool-muf" data-tool-panel>
    <h2><?= e((string) $t['muftool_calc']) ?></h2>
    <label><?= e((string) $t['critical_freq_mhz']) ?>
        <input type="number" id="muf-fof2" min="0" step="0.01" value="6">
    </label>
    <label><?= e((string) $t['incidence_deg']) ?>
        <input type="number" id="muf-angle" min="1" max="89" step="0.1" value="30">
    </label>
    <p class="help"><?= e((string) $t['muf_result']) ?>: <strong id="muf-result">—</strong></p>
</article>
