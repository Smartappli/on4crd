<article class="card tool-panel" id="tool-mismatch" data-tool-panel>
    <h2><?= e((string) $t['mismatch_loss_calc']) ?></h2>
    <label><?= e((string) $t['swr']) ?>
        <input type="number" id="mismatch-swr" min="1" step="0.01" value="1.5">
    </label>
    <p class="help"><?= e((string) $t['reflection_coeff']) ?>: <strong id="mismatch-gamma">—</strong></p>
    <p class="help"><?= e((string) $t['mismatch_loss_result']) ?>: <strong id="mismatch-loss">—</strong></p>
</article>
