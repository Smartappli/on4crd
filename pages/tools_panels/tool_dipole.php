<article class="card tool-panel" id="tool-dipole" data-tool-panel>
    <h2><?= e((string) $t['dipole_calc']) ?></h2>
    <label><?= e((string) $t['frequency_mhz']) ?>
        <input type="text" inputmode="decimal" id="dipole-frequency" min="0" step="0.001" value="145.5">
    </label>
    <p class="help"><?= e((string) $t['dipole_total_length']) ?>: <strong id="dipole-length">-</strong></p>
</article>
