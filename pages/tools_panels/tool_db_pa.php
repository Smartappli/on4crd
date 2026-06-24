<?php
declare(strict_types=1);

$t = isset($t) && is_array($t) ? $t : [];
?><article id="tool-db-pa" class="tool-panel card is-hidden" data-tool-panel>
    <h2><?= e((string) $t['db_pa_calc']) ?></h2>
    <div class="tool-grid-form">
        <label for="tool-db-pa-in"><?= e((string) $t['value_in']) ?></label>
        <input id="tool-db-pa-in" type="text" data-step="any" inputmode="decimal" placeholder="1">

        <label for="tool-db-pa-out"><?= e((string) $t['value_out']) ?></label>
        <output id="tool-db-pa-out">-</output>
    </div>
</article>
