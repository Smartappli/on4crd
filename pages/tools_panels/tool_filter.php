<article class="card tool-panel" id="tool-filter" data-tool-panel>
    <h2><?= e((string) $t['filter_calc']) ?></h2>
    <label><?= e((string) $t['cutoff_freq']) ?>
        <input type="number" id="filter-freq" min="0" step="0.001" placeholder="<?= e((string) $t['freq_ph']) ?>">
    </label>
    <label><?= e((string) $t['impedance']) ?>
        <input type="number" id="filter-impedance" min="1" step="0.1" value="50">
    </label>
    <p class="help"><?= e((string) $t['inductance']) ?>: <strong id="filter-l">—</strong></p>
    <p class="help"><?= e((string) $t['capacitance']) ?>: <strong id="filter-c">—</strong></p>
</article>
