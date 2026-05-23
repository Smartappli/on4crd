<article class="card tool-panel" id="tool-swr" data-tool-panel>
    <h2><?= e((string) $t['swr_calc']) ?></h2>
    <label><?= e((string) $t['forward_power']) ?>
        <input type="number" id="swr-forward" min="0" step="0.1" value="50">
    </label>
    <label><?= e((string) $t['reflected_power']) ?>
        <input type="number" id="swr-reflected" min="0" step="0.1" value="2">
    </label>
    <p class="help"><?= e((string) $t['swr_result']) ?>: <strong id="swr-value">-</strong></p>
</article>
