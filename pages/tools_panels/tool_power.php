<article class="card tool-panel" id="tool-power" data-tool-panel>
    <h2><?= e((string) $t['conv']) ?> - <?= e((string) $t['power']) ?></h2>
    <label><?= e((string) $t['watts']) ?>
        <input type="number" id="power-watts" min="0" step="0.001" placeholder="<?= e((string) $t['watts_ph']) ?>">
    </label>
    <p class="help"><?= e((string) $t['dbm_label']) ?>: <strong id="power-dbm">-</strong></p>
    <label><?= e((string) $t['dbm_label']) ?>
        <input type="number" id="power-dbm-input" step="0.1" placeholder="<?= e((string) $t['dbm_ph']) ?>">
    </label>
    <p class="help"><?= e((string) $t['watts_out_label']) ?>: <strong id="power-watts-out">-</strong></p>
</article>
