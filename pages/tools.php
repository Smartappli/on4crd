<?php
declare(strict_types=1);

ob_start();
?>
<section class="card">
    <h1>Outils radioamateur</h1>
    <p class="help">Utilitaires publics du club. Commencez par convertir votre adresse postale en locator (grid Maidenhead).</p>
</section>

<section class="card">
    <h2>Calcul du grid depuis une adresse postale</h2>
    <form id="grid-tool-form" class="stack">
        <label>Adresse postale
            <input type="text" id="grid-address" placeholder="Ex: Rue des Écoles 1, 5530 Purnode, Belgique" required>
        </label>
        <div class="actions">
            <button type="submit" class="button">Calculer le grid</button>
        </div>
    </form>
    <div id="grid-tool-result" class="card is-hidden" style="margin-top:1rem;">
        <p><strong>Adresse trouvée :</strong> <span id="grid-found-address">—</span></p>
        <p><strong>Coordonnées :</strong> <span id="grid-found-coords">—</span></p>
        <p><strong>Locator Maidenhead :</strong> <span id="grid-found-locator">—</span></p>
    </div>
    <p id="grid-tool-error" class="flash flash-error is-hidden" style="margin-top:1rem;"></p>
</section>

<script nonce="<?= e(csp_nonce()) ?>">
(() => {
    const form = document.getElementById('grid-tool-form');
    const addressInput = document.getElementById('grid-address');
    const result = document.getElementById('grid-tool-result');
    const foundAddress = document.getElementById('grid-found-address');
    const foundCoords = document.getElementById('grid-found-coords');
    const foundLocator = document.getElementById('grid-found-locator');
    const errorBox = document.getElementById('grid-tool-error');
    if (!(form instanceof HTMLFormElement) || !(addressInput instanceof HTMLInputElement)) {
        return;
    }

    const toMaidenhead = (latitude, longitude, precision = 6) => {
        let lon = longitude + 180;
        let lat = latitude + 90;
        const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        let locator = '';

        locator += letters[Math.floor(lon / 20)];
        locator += letters[Math.floor(lat / 10)];
        lon %= 20; lat %= 10;
        locator += Math.floor(lon / 2).toString();
        locator += Math.floor(lat).toString();
        lon = (lon % 2) * 12;
        lat = (lat % 1) * 24;
        locator += letters[Math.floor(lon)];
        locator += letters[Math.floor(lat)];

        return locator.slice(0, Math.max(4, Math.min(precision, 6)));
    };

    const setError = (message) => {
        if (!errorBox) return;
        errorBox.textContent = message;
        errorBox.classList.remove('is-hidden');
    };
    const clearError = () => errorBox?.classList.add('is-hidden');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearError();
        result?.classList.add('is-hidden');
        const query = addressInput.value.trim();
        if (query === '') {
            setError('Veuillez saisir une adresse postale.');
            return;
        }
        try {
            const url = `https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(query)}`;
            const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!response.ok) {
                throw new Error('Service de géocodage indisponible.');
            }
            const rows = await response.json();
            if (!Array.isArray(rows) || rows.length === 0) {
                throw new Error('Adresse introuvable. Essayez avec ville et code postal.');
            }
            const row = rows[0] || {};
            const lat = Number(row.lat);
            const lon = Number(row.lon);
            if (!Number.isFinite(lat) || !Number.isFinite(lon)) {
                throw new Error('Coordonnées invalides reçues.');
            }
            const locator = toMaidenhead(lat, lon, 6);
            if (foundAddress) foundAddress.textContent = row.display_name || query;
            if (foundCoords) foundCoords.textContent = `${lat.toFixed(5)}, ${lon.toFixed(5)}`;
            if (foundLocator) foundLocator.textContent = locator;
            result?.classList.remove('is-hidden');
        } catch (error) {
            setError(error instanceof Error ? error.message : 'Erreur lors du calcul du grid.');
        }
    });
})();
</script>
<?php
echo render_layout((string) ob_get_clean(), 'Outils');

