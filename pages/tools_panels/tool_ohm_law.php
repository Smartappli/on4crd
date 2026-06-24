<?php
declare(strict_types=1);

$t = isset($t) && is_array($t) ? $t : [];
?><article id="tool-ohm-law" data-tool-panel class="card tool-panel is-hidden">
    <h2><?= e((string) $t['ohm_law_calc']) ?></h2>
    <p class="help"><?= e((string) $t['ohm_law_help']) ?></p>
    <div class="grid-auto">
        <label><?= e((string) $t['voltage_v']) ?>
            <input id="ohm-voltage" type="text" inputmode="decimal" pattern="[0-9]+([\.,][0-9]{1,2})?" placeholder="12.00">
        </label>
        <label><?= e((string) $t['current_a']) ?>
            <input id="ohm-current" type="text" inputmode="decimal" pattern="[0-9]+([\.,][0-9]{1,2})?" placeholder="2.00">
        </label>
        <label><?= e((string) $t['resistance_ohm']) ?>
            <input id="ohm-resistance" type="text" inputmode="decimal" pattern="[0-9]+([\.,][0-9]{1,2})?" placeholder="6.00">
        </label>
    </div>
    <p class="help"><?= e((string) $t['ohm_law_hint']) ?></p>
</article>
