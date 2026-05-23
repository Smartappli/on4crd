<article id="tool-khz-mhz" class="tool-panel card is-hidden" data-tool-panel>
    <h2><?= e((string) ($t['khz_mhz_calc'] ?? 'khz_mhz_calc')) ?></h2>
    <div class="tool-grid-form">
        <label for="tool-khz-mhz-in"><?= e((string) ($t['value_in'] ?? 'Input value')) ?></label>
        <input id="tool-khz-mhz-in" type="number" step="any" inputmode="decimal" placeholder="1">

        <label for="tool-khz-mhz-out"><?= e((string) ($t['value_out'] ?? 'Output value')) ?></label>
        <output id="tool-khz-mhz-out">-</output>
    </div>
</article>
