<article id="tool-link-budget" data-tool-panel class="card is-hidden">
    <h3><?= e((string) ($t['link_budget_calc'] ?? 'Link budget')) ?></h3>
    <p class="help"><?= e((string) ($t['link_budget_help'] ?? 'Calcul de puissance reçue: Prx = Ptx + Gtx + Grx - pertes.')) ?></p>
    <div class="grid-auto">
        <label><?= e((string) ($t['tx_power_dbm'] ?? 'Ptx (dBm)')) ?>
            <input id="lb-ptx" type="number" inputmode="decimal" step="0.1" placeholder="30">
        </label>
        <label><?= e((string) ($t['tx_gain_dbi'] ?? 'Gtx (dBi)')) ?>
            <input id="lb-gtx" type="number" inputmode="decimal" step="0.1" placeholder="6">
        </label>
        <label><?= e((string) ($t['rx_gain_dbi'] ?? 'Grx (dBi)')) ?>
            <input id="lb-grx" type="number" inputmode="decimal" step="0.1" placeholder="6">
        </label>
        <label><?= e((string) ($t['total_losses_db'] ?? 'Pertes totales (dB)')) ?>
            <input id="lb-loss" type="number" inputmode="decimal" step="0.1" min="0" placeholder="110">
        </label>
    </div>
    <p><strong><?= e((string) ($t['rx_power_est'] ?? 'Prx estimée')) ?>:</strong> <span id="lb-prx">—</span></p>
</article>
