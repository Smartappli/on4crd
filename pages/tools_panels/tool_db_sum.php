<article class="card tool-panel" id="tool-db-sum" data-tool-panel>
    <h2><?= e((string) $t['db_sum_calc']) ?></h2>
    <label><?= e((string) $t['dbm_a']) ?>
        <input type="text" inputmode="decimal" id="dbsum-a" step="0.1" value="30">
    </label>
    <label><?= e((string) $t['dbm_b']) ?>
        <input type="text" inputmode="decimal" id="dbsum-b" step="0.1" value="30">
    </label>
    <p class="help"><?= e((string) $t['dbm_sum_result']) ?>: <strong id="dbsum-result">-</strong></p>
</article>
