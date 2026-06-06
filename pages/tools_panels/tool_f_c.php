<?php
declare(strict_types=1);

$t = isset($t) && is_array($t) ? $t : [];
?><article id="tool-f-c" class="tool-panel card is-hidden" data-tool-panel>
    <h2><?= e((string) ($t['f_c_calc'] ?? 'f_c_calc')) ?></h2>
    <div class="tool-grid-form">
        <label for="tool-f-c-in"><?= e((string) ($t['value_in'] ?? 'Input value')) ?></label>
        <input id="tool-f-c-in" type="text" data-step="any" inputmode="decimal" placeholder="1">

        <label for="tool-f-c-out"><?= e((string) ($t['value_out'] ?? 'Output value')) ?></label>
        <output id="tool-f-c-out">-</output>
    </div>
</article>
