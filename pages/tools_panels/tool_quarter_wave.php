<article class="card tool-panel" id="tool-quarter-wave" data-tool-panel>
    <h2><?= e((string) $t['quarter_wave_calc']) ?></h2>
    <label><?= e((string) $t['frequency_mhz']) ?>
        <input type="text" inputmode="decimal" id="quarter-wave-frequency" min="0" step="0.001" value="145.5">
    </label>
    <label><?= e((string) $t['velocity_factor']) ?>
        <input type="text" inputmode="decimal" id="quarter-wave-vf" min="0" max="1" step="0.01" value="0.95">
    </label>
    <p class="help"><?= e((string) $t['quarter_wave_result']) ?>: <strong id="quarter-wave-length">-</strong></p>
</article>
