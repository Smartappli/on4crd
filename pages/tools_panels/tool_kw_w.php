<article id="tool-kw-w" class="tool-panel card is-hidden" data-tool-panel>
    <h2><?= e((string) ($t['kw_w_calc'] ?? 'kw_w_calc')) ?></h2>
    <div class="tool-grid-form">
        <label for="tool-kw-w-in"><?= e((string) ($t['value_in'] ?? 'Input value')) ?></label>
        <input id="tool-kw-w-in" type="text" step="any" inputmode="decimal" placeholder="1">

        <label for="tool-kw-w-out"><?= e((string) ($t['value_out'] ?? 'Output value')) ?></label>
        <output id="tool-kw-w-out">-</output>
    </div>
</article>
