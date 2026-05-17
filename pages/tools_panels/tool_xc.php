<article id="tool-xc" class="tool-panel card is-hidden" data-tool-panel>
    <h2><?= e((string) ($t['xc_calc'] ?? 'Reactance capacitive (XC)')) ?></h2>
    <div class="tool-grid-form">
        <label for="xc-freq"><?= e((string) ($t['frequency_mhz'] ?? 'Fréquence (MHz)')) ?></label>
        <input id="xc-freq" type="number" step="0.001" inputmode="decimal" placeholder="<?= e((string) ($t['freq_ph'] ?? 'Ex: 145.500')) ?>">

        <label for="xc-capacitance"><?= e((string) ($t['capacitance_pf'] ?? 'Capacité (pF)')) ?></label>
        <input id="xc-capacitance" type="number" step="0.1" inputmode="decimal" placeholder="<?= e((string) ($t['capacitance_pf_ph'] ?? 'Ex: 100')) ?>">

        <label for="xc-result"><?= e((string) ($t['reactance_result_ohm'] ?? 'Réactance (Ω)')) ?></label>
        <output id="xc-result" aria-live="polite">—</output>
    </div>
</article>
