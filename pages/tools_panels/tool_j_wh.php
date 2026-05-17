<article id="tool-j-wh" class="tool-panel card is-hidden" data-tool-panel>
    <h2><?= e((string) ($t['j_wh_calc'] ?? 'j_wh_calc')) ?></h2>
    <div class="tool-grid-form">
        <label for="tool-j-wh-in"><?= e((string) ($t['value_in'] ?? 'Valeur entrée')) ?></label>
        <input id="tool-j-wh-in" type="number" step="any" inputmode="decimal" placeholder="1">

        <label for="tool-j-wh-out"><?= e((string) ($t['value_out'] ?? 'Valeur sortie')) ?></label>
        <output id="tool-j-wh-out">—</output>
    </div>
</article>
