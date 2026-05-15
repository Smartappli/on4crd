<article id="tool-ohm-law" data-tool-panel class="card is-hidden">
    <h3><?= e((string) ($t['ohm_law_calc'] ?? "Loi d'Ohm")) ?></h3>
    <p class="help"><?= e((string) ($t['ohm_law_help'] ?? 'Entrez deux valeurs pour calculer la troisième.')) ?></p>
    <div class="grid-auto">
        <label><?= e((string) ($t['voltage_v'] ?? 'Tension (V)')) ?>
            <input id="ohm-voltage" type="number" inputmode="decimal" step="0.01" min="0" placeholder="12">
        </label>
        <label><?= e((string) ($t['current_a'] ?? 'Courant (A)')) ?>
            <input id="ohm-current" type="number" inputmode="decimal" step="0.01" min="0" placeholder="2">
        </label>
        <label><?= e((string) ($t['resistance_ohm'] ?? 'Résistance (Ω)')) ?>
            <input id="ohm-resistance" type="number" inputmode="decimal" step="0.01" min="0" placeholder="6">
        </label>
    </div>
    <p class="help"><?= e((string) ($t['ohm_law_hint'] ?? 'Si exactement 2 champs sont renseignés, le 3e est calculé automatiquement.')) ?></p>
</article>
