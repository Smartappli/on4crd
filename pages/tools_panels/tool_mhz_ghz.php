<article id="tool-mhz-ghz" class="tool-panel card is-hidden" data-tool-panel>
    <h2><?= e((string) ($t['mhz_ghz_calc'] ?? 'mhz_ghz_calc')) ?></h2>
    <div class="tool-grid-form">
        <label for="tool-mhz-ghz-in"><?= e((string) ($t['value_in'] ?? 'Input value')) ?></label>
        <input id="tool-mhz-ghz-in" type="text" step="any" inputmode="decimal" placeholder="1">

        <label for="tool-mhz-ghz-out"><?= e((string) ($t['value_out'] ?? 'Output value')) ?></label>
        <output id="tool-mhz-ghz-out">-</output>
    </div>
</article>
