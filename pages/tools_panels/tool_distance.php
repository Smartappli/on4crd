<?php
declare(strict_types=1);

$t = isset($t) && is_array($t) ? $t : [];
?><article class="card tool-panel" id="tool-distance" data-tool-panel>
    <h2><?= e((string) $t['distance_calc']) ?></h2>
    <label><?= e((string) $t['locator_a']) ?>
        <input type="text" id="locator-a" maxlength="6" placeholder="<?= e((string) $t['locator_a_ph']) ?>">
    </label>
    <label><?= e((string) $t['locator_b']) ?>
        <input type="text" id="locator-b" maxlength="6" placeholder="<?= e((string) $t['locator_b_ph']) ?>">
    </label>
    <p class="help"><?= e((string) $t['estimated_distance']) ?>: <strong id="locator-distance">-</strong></p>
</article>
