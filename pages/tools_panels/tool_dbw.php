<?php
declare(strict_types=1);

$t = isset($t) && is_array($t) ? $t : [];
?><article id="tool-dbw" class="tool-panel card is-hidden" data-tool-panel>
    <h2><?= e((string) $t['dbw_calc']) ?></h2>
    <div class="tool-grid-form">
        <label for="dbw-dbm"><?= e((string) $t['dbm_label']) ?></label>
        <input id="dbw-dbm" type="text" data-step="0.1" inputmode="decimal" placeholder="<?= e((string) $t['dbm_ph']) ?>">

        <label for="dbw-dbw-input"><?= e((string) $t['dbw_label']) ?></label>
        <input id="dbw-dbw-input" type="text" data-step="0.1" inputmode="decimal" placeholder="<?= e((string) $t['dbw_ph']) ?>">

        <label for="dbw-result"><?= e((string) $t['dbm_from_dbw']) ?></label>
        <output id="dbw-result">-</output>
    </div>
</article>
