<?php
declare(strict_types=1);

$t = isset($t) && is_array($t) ? $t : [];
?><article id="tool-c-f" class="tool-panel card is-hidden" data-tool-panel>
    <h2><?= e((string) ($t['c_f_calc'] ?? 'c_f_calc')) ?></h2>
    <div class="tool-grid-form">
        <label for="tool-c-f-in"><?= e((string) ($t['value_in'] ?? 'Input value')) ?></label>
        <input id="tool-c-f-in" type="text" data-step="any" inputmode="decimal" placeholder="1">

        <label for="tool-c-f-out"><?= e((string) ($t['value_out'] ?? 'Output value')) ?></label>
        <output id="tool-c-f-out">-</output>
    </div>
</article>
