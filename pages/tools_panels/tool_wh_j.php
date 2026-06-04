<article id="tool-wh-j" class="tool-panel card is-hidden" data-tool-panel>
    <h2><?= e((string) ($t['wh_j_calc'] ?? 'wh_j_calc')) ?></h2>
    <div class="tool-grid-form">
        <label for="tool-wh-j-in"><?= e((string) ($t['value_in'] ?? 'Input value')) ?></label>
        <input id="tool-wh-j-in" type="text" step="any" inputmode="decimal" placeholder="1">

        <label for="tool-wh-j-out"><?= e((string) ($t['value_out'] ?? 'Output value')) ?></label>
        <output id="tool-wh-j-out">-</output>
    </div>
</article>
