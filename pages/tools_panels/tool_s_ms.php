<article id="tool-s-ms" class="tool-panel card is-hidden" data-tool-panel>
    <h2><?= e((string) ($t['s_ms_calc'] ?? 's_ms_calc')) ?></h2>
    <div class="tool-grid-form">
        <label for="tool-s-ms-in"><?= e((string) ($t['value_in'] ?? 'Input value')) ?></label>
        <input id="tool-s-ms-in" type="number" step="any" inputmode="decimal" placeholder="1">

        <label for="tool-s-ms-out"><?= e((string) ($t['value_out'] ?? 'Output value')) ?></label>
        <output id="tool-s-ms-out">-</output>
    </div>
</article>
