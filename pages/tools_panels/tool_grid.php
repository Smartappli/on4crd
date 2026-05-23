<article class="card tool-panel" id="tool-grid" data-tool-panel>
    <h2><?= e((string) $t['grid_title']) ?></h2>
    <form id="grid-tool-form" class="stack">
        <label><?= e((string) $t['address']) ?>
            <input type="text" id="grid-address" placeholder="<?= e((string) $t['addr_ph']) ?>" required>
        </label>
        <div class="actions">
            <button type="submit" class="button"><?= e((string) $t['calc_grid']) ?></button>
        </div>
    </form>
    <div id="grid-tool-result" class="card is-hidden" style="margin-top:1rem;">
        <p><strong><?= e((string) $t['found_address']) ?> :</strong> <span id="grid-found-address">-</span></p>
        <p><strong><?= e((string) $t['coords']) ?> :</strong> <span id="grid-found-coords">-</span></p>
        <p><strong><?= e((string) $t['locator']) ?> :</strong> <span id="grid-found-locator">-</span></p>
    </div>
</article>
