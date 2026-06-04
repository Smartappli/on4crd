<article id="tool-xl" class="tool-panel card is-hidden" data-tool-panel>
    <h2><?= e((string) ($t['xl_calc'] ?? 'Reactance inductive (XL)')) ?></h2>
    <div class="tool-grid-form">
        <label for="xl-freq"><?= e((string) ($t['frequency_mhz'] ?? 'Frequency (MHz)')) ?></label>
        <input id="xl-freq" type="text" step="0.001" inputmode="decimal" placeholder="<?= e((string) ($t['freq_ph'] ?? 'Ex: 145.500')) ?>">

        <label for="xl-inductance"><?= e((string) ($t['inductance_uh'] ?? 'Inductance (uH)')) ?></label>
        <input id="xl-inductance" type="text" step="0.01" inputmode="decimal" placeholder="<?= e((string) ($t['inductance_uh_ph'] ?? 'Ex: 2.2')) ?>">

        <label for="xl-result"><?= e((string) ($t['reactance_result_ohm'] ?? 'Reactance (Ohm)')) ?></label>
        <output id="xl-result" aria-live="polite">-</output>
    </div>
</article>
