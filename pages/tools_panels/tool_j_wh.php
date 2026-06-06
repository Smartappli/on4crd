<?php
declare(strict_types=1);

$t = isset($t) && is_array($t) ? $t : [];
?><article id="tool-j-wh" class="tool-panel card is-hidden" data-tool-panel>
    <h2><?= e((string) ($t['j_wh_calc'] ?? 'j_wh_calc')) ?></h2>
    <div class="tool-grid-form">
        <label for="tool-j-wh-in"><?= e((string) ($t['value_in'] ?? 'Input value')) ?></label>
        <input id="tool-j-wh-in" type="text" data-step="any" inputmode="decimal" placeholder="1">

        <label for="tool-j-wh-out"><?= e((string) ($t['value_out'] ?? 'Output value')) ?></label>
        <output id="tool-j-wh-out">-</output>
    </div>
</article>
