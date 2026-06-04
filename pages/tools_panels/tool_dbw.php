<article id="tool-dbw" class="tool-panel card is-hidden" data-tool-panel>
    <h2>dBm ↔ dBW</h2>
    <div class="tool-grid-form">
        <label for="dbw-dbm"><?= e((string) ($t['dbm_label'] ?? 'dBm')) ?></label>
        <input id="dbw-dbm" type="text" step="0.1" inputmode="decimal" placeholder="<?= e((string) ($t['dbm_ph'] ?? 'Ex: 40')) ?>">

        <label for="dbw-dbw-input"><?= e((string) ($t['dbw_label'] ?? 'dBW')) ?></label>
        <input id="dbw-dbw-input" type="text" step="0.1" inputmode="decimal" placeholder="<?= e((string) ($t['dbw_ph'] ?? 'Ex: 10')) ?>">

        <label for="dbw-result"><?= e((string) ($t['dbm_from_dbw'] ?? 'dBm (from dBW)')) ?></label>
        <output id="dbw-result">-</output>
    </div>
</article>
