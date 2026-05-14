<article id="tool-dbw" class="tool-panel card is-hidden" data-tool-panel>
    <h2>dBm ↔ dBW</h2>
    <div class="tool-grid-form">
        <label for="dbw-dbm"><?= e((string) ($t['dbm_label'] ?? 'dBm')) ?></label>
        <input id="dbw-dbm" type="number" step="0.1" inputmode="decimal" placeholder="<?= e((string) ($t['dbm_ph'] ?? 'Ex: 40')) ?>">

        <label for="dbw-dbw-input">dBW</label>
        <input id="dbw-dbw-input" type="number" step="0.1" inputmode="decimal" placeholder="Ex: 10">

        <label for="dbw-result">dBm (depuis dBW)</label>
        <output id="dbw-result">—</output>
    </div>
</article>
