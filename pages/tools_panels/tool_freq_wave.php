<article class="card tool-panel" id="tool-freq-wave" data-tool-panel>
    <h2><?= e((string) $t['conv']) ?> - <?= e((string) $t['freq_wave']) ?></h2>
    <label><?= e((string) $t['freq_mhz']) ?>
        <input type="text" inputmode="decimal" id="freq-mhz" min="0" step="0.001" placeholder="<?= e((string) $t['freq_ph']) ?>">
    </label>
    <p class="help"><?= e((string) $t['wavelength']) ?>: <strong id="freq-wavelength">-</strong></p>
</article>
