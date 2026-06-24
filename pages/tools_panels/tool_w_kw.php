<?php
declare(strict_types=1);

$t = isset($t) && is_array($t) ? $t : [];
?><article id="tool-w-kw" class="tool-panel card is-hidden" data-tool-panel>
    <h2><?= e((string) $t['w_kw_calc']) ?></h2>
    <div class="tool-grid-form">
        <label for="tool-w-kw-in"><?= e((string) $t['value_in']) ?></label>
        <input id="tool-w-kw-in" type="text" data-step="any" inputmode="decimal" placeholder="1">

        <label for="tool-w-kw-out"><?= e((string) $t['value_out']) ?></label>
        <output id="tool-w-kw-out">-</output>
    </div>
</article>
