<article class="card tool-panel" id="tool-gain-conv" data-tool-panel>
    <h2><?= e((string) $t['gain_conv_calc']) ?></h2>
    <label><?= e((string) $t['dbd_label']) ?>
        <input type="text" inputmode="decimal" id="gain-dbd" step="0.1" value="3">
    </label>
    <p class="help"><?= e((string) $t['dbi_result']) ?>: <strong id="gain-dbi">-</strong></p>
</article>
