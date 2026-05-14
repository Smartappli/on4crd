<article class="card tool-panel" id="tool-dbuv" data-tool-panel>
    <h2><?= e((string) $t['dbuv_calc']) ?></h2>
    <label><?= e((string) $t['dbm_label']) ?>
        <input type="number" id="dbuv-dbm" step="0.1" value="-73">
    </label>
    <p class="help"><?= e((string) $t['dbm_to_dbuv_result']) ?>: <strong id="dbuv-result">—</strong></p>
</article>
