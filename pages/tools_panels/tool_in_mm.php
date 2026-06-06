<?php
declare(strict_types=1);

$t = isset($t) && is_array($t) ? $t : [];
?><article id="tool-in-mm" class="tool-panel card is-hidden" data-tool-panel>
    <h2><?= e((string) ($t['in_mm_calc'] ?? 'in_mm_calc')) ?></h2>
    <div class="tool-grid-form">
        <label for="tool-in-mm-in"><?= e((string) ($t['value_in'] ?? 'Input value')) ?></label>
        <input id="tool-in-mm-in" type="text" data-step="any" inputmode="decimal" placeholder="1">

        <label for="tool-in-mm-out"><?= e((string) ($t['value_out'] ?? 'Output value')) ?></label>
        <output id="tool-in-mm-out">-</output>
    </div>
</article>
