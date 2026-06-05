<article class="card tool-panel" id="tool-muf" data-tool-panel>
    <h2><?= e((string) $t['muftool_calc']) ?></h2>
    <label><?= e((string) $t['critical_freq_mhz']) ?>
        <input type="text" inputmode="decimal" id="muf-fof2" data-min="0" data-step="0.01" value="6">
    </label>
    <label><?= e((string) $t['incidence_deg']) ?>
        <input type="text" inputmode="decimal" id="muf-angle" data-min="1" data-max="89" data-step="0.1" value="30">
    </label>
    <p class="help"><?= e((string) $t['muf_result']) ?>: <strong id="muf-result">-</strong></p>
</article>
