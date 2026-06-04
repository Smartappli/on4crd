<article class="card tool-panel" id="tool-swr" data-tool-panel>
    <h2><?= e((string) $t['swr_calc']) ?></h2>
    <label><?= e((string) ($t['forward_power'] ?? 'Puissance directe')) ?>
        <input type="text" inputmode="decimal" id="swr-forward" data-min="0" data-step="0.1" value="50">
    </label>
    <label><?= e((string) ($t['reflected_power'] ?? 'Puissance rÃ©flÃ©chie')) ?>
        <input type="text" inputmode="decimal" id="swr-reflected" data-min="0" data-step="0.1" value="2">
    </label>
    <p class="help"><?= e((string) ($t['swr_result'] ?? ($t['swr'] ?? 'SWR'))) ?>: <strong id="swr-value">-</strong></p>
</article>
