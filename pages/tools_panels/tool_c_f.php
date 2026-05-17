<article id="tool-c-f" class="tool-panel card is-hidden" data-tool-panel>
    <h2><?= e((string) ($t['c_f_calc'] ?? 'c_f_calc')) ?></h2>
    <div class="tool-grid-form">
        <label for="tool-c-f-in"><?= e((string) ($t['value_in'] ?? 'Valeur entrée')) ?></label>
        <input id="tool-c-f-in" type="number" step="any" inputmode="decimal" placeholder="1">

        <label for="tool-c-f-out"><?= e((string) ($t['value_out'] ?? 'Valeur sortie')) ?></label>
        <output id="tool-c-f-out">—</output>
    </div>
</article>
