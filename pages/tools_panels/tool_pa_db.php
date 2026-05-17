<article id="tool-pa-db" class="tool-panel card is-hidden" data-tool-panel>
    <h2><?= e((string) ($t['pa_db_calc'] ?? 'pa_db_calc')) ?></h2>
    <div class="tool-grid-form">
        <label for="tool-pa-db-in"><?= e((string) ($t['value_in'] ?? 'Valeur entrée')) ?></label>
        <input id="tool-pa-db-in" type="number" step="any" inputmode="decimal" placeholder="1">

        <label for="tool-pa-db-out"><?= e((string) ($t['value_out'] ?? 'Valeur sortie')) ?></label>
        <output id="tool-pa-db-out">—</output>
    </div>
</article>
