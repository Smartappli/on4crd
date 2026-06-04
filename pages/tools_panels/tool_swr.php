<article class="card tool-panel" id="tool-swr" data-tool-panel>
    <h2><?= e((string) $t['swr_calc']) ?></h2>
    <label><?= e((string) ($t['forward_power'] ?? 'Puissance directe')) ?>
        <input type="text" inputmode="decimal" id="swr-forward" min="0" step="0.1" value="50">
    </label>
    <label><?= e((string) ($t['reflected_power'] ?? 'Puissance réfléchie')) ?>
        <input type="text" inputmode="decimal" id="swr-reflected" min="0" step="0.1" value="2">
    </label>
    <p class="help"><?= e((string) ($t['swr_result'] ?? ($t['swr'] ?? 'SWR'))) ?>: <strong id="swr-value">-</strong></p>
</article>
