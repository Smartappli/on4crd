<article id="tool-rps-rpm" class="tool-panel card is-hidden" data-tool-panel>
    <h2><?= e((string) ($t['rps_rpm_calc'] ?? 'rps_rpm_calc')) ?></h2>
    <div class="tool-grid-form">
        <label for="tool-rps-rpm-in"><?= e((string) ($t['value_in'] ?? 'Input value')) ?></label>
        <input id="tool-rps-rpm-in" type="text" data-step="any" inputmode="decimal" placeholder="1">

        <label for="tool-rps-rpm-out"><?= e((string) ($t['value_out'] ?? 'Output value')) ?></label>
        <output id="tool-rps-rpm-out">-</output>
    </div>
</article>
