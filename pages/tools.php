<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['title'=>'Outils','grid_title'=>'Calcul du grid depuis une adresse postale','address'=>'Adresse postale','calc_grid'=>'Calculer le grid','found_address'=>'Adresse trouvée','coords'=>'Coordonnées','locator'=>'Locator Maidenhead','conv'=>'Conversions utiles','freq_wave'=>'Fréquence → longueur d’onde','freq_mhz'=>'Fréquence (MHz)','wavelength'=>'Longueur d’onde','power'=>'Puissance (W ↔ dBm)','watts'=>'Watts (W)','distance'=>'Distance entre 2 locators','locator_a'=>'Locator A','locator_b'=>'Locator B','estimated_distance'=>'Distance estimée','err_enter_address'=>'Veuillez saisir une adresse postale.','err_geocode_unavailable'=>'Service de géocodage indisponible.','err_address_not_found'=>'Adresse introuvable. Essayez avec ville et code postal.','err_invalid_coords'=>'Coordonnées invalides reçues.','err_grid_calc'=>'Erreur lors du calcul du grid.'],
    'en' => ['title'=>'Tools','grid_title'=>'Grid calculation from a postal address','address'=>'Postal address','calc_grid'=>'Calculate grid','found_address'=>'Found address','coords'=>'Coordinates','locator'=>'Maidenhead locator','conv'=>'Useful conversions','freq_wave'=>'Frequency → wavelength','freq_mhz'=>'Frequency (MHz)','wavelength'=>'Wavelength','power'=>'Power (W ↔ dBm)','watts'=>'Watts (W)','distance'=>'Distance between 2 locators','locator_a'=>'Locator A','locator_b'=>'Locator B','estimated_distance'=>'Estimated distance','err_enter_address'=>'Please enter a postal address.','err_geocode_unavailable'=>'Geocoding service unavailable.','err_address_not_found'=>'Address not found. Try with city and postal code.','err_invalid_coords'=>'Received invalid coordinates.','err_grid_calc'=>'Error while calculating grid.'],
    'de' => ['title'=>'Werkzeuge','grid_title'=>'Grid-Berechnung aus einer Postadresse','address'=>'Postadresse','calc_grid'=>'Grid berechnen','found_address'=>'Gefundene Adresse','coords'=>'Koordinaten','locator'=>'Maidenhead-Locator','conv'=>'Nützliche Umrechnungen','freq_wave'=>'Frequenz → Wellenlänge','freq_mhz'=>'Frequenz (MHz)','wavelength'=>'Wellenlänge','power'=>'Leistung (W ↔ dBm)','watts'=>'Watt (W)','distance'=>'Entfernung zwischen 2 Locatoren','locator_a'=>'Locator A','locator_b'=>'Locator B','estimated_distance'=>'Geschätzte Entfernung','err_enter_address'=>'Bitte geben Sie eine Postadresse ein.','err_geocode_unavailable'=>'Geokodierungsdienst nicht verfügbar.','err_address_not_found'=>'Adresse nicht gefunden. Versuchen Sie es mit Stadt und Postleitzahl.','err_invalid_coords'=>'Ungültige Koordinaten empfangen.','err_grid_calc'=>'Fehler bei der Grid-Berechnung.'],
    'nl' => ['title'=>'Tools','grid_title'=>'Grid berekenen vanaf een postadres','address'=>'Postadres','calc_grid'=>'Grid berekenen','found_address'=>'Gevonden adres','coords'=>'Coördinaten','locator'=>'Maidenhead-locator','conv'=>'Nuttige conversies','freq_wave'=>'Frequentie → golflengte','freq_mhz'=>'Frequentie (MHz)','wavelength'=>'Golflengte','power'=>'Vermogen (W ↔ dBm)','watts'=>'Watt (W)','distance'=>'Afstand tussen 2 locators','locator_a'=>'Locator A','locator_b'=>'Locator B','estimated_distance'=>'Geschatte afstand','err_enter_address'=>'Voer een postadres in.','err_geocode_unavailable'=>'Geocodingservice niet beschikbaar.','err_address_not_found'=>'Adres niet gevonden. Probeer met stad en postcode.','err_invalid_coords'=>'Ongeldige coördinaten ontvangen.','err_grid_calc'=>'Fout bij het berekenen van het grid.'],
];
$t = $i18n[$locale] ?? $i18n['fr'];
$jsI18n = [
    'err_enter_address' => (string) ($t['err_enter_address'] ?? $i18n['fr']['err_enter_address']),
    'err_geocode_unavailable' => (string) ($t['err_geocode_unavailable'] ?? $i18n['fr']['err_geocode_unavailable']),
    'err_address_not_found' => (string) ($t['err_address_not_found'] ?? $i18n['fr']['err_address_not_found']),
    'err_invalid_coords' => (string) ($t['err_invalid_coords'] ?? $i18n['fr']['err_invalid_coords']),
    'err_grid_calc' => (string) ($t['err_grid_calc'] ?? $i18n['fr']['err_grid_calc']),
];

ob_start();
?>
<section class="card">
    <h1><?= e((string) $t['title']) ?></h1>
    <div class="grid-2">
        <article class="card">
            <h2><?= e((string) $t['grid_title']) ?></h2>
            <form id="grid-tool-form" class="stack">
                <label><?= e((string) $t['address']) ?>
                    <input type="text" id="grid-address" placeholder="Ex: Rue des Écoles 1, 5530 Purnode, Belgique" required>
                </label>
                <div class="actions">
                    <button type="submit" class="button"><?= e((string) $t['calc_grid']) ?></button>
                </div>
            </form>
            <div id="grid-tool-result" class="card is-hidden" style="margin-top:1rem;">
                <p><strong><?= e((string) $t['found_address']) ?> :</strong> <span id="grid-found-address">—</span></p>
                <p><strong><?= e((string) $t['coords']) ?> :</strong> <span id="grid-found-coords">—</span></p>
                <p><strong><?= e((string) $t['locator']) ?> :</strong> <span id="grid-found-locator">—</span></p>
            </div>
        </article>
        <article class="card">
            <h2><?= e((string) $t['conv']) ?></h2>
            <div class="stack">
                <section class="card">
                    <h3><?= e((string) $t['freq_wave']) ?></h3>
                    <label><?= e((string) $t['freq_mhz']) ?>
                        <input type="number" id="freq-mhz" min="0" step="0.001" placeholder="Ex: 145.500">
                    </label>
                    <p class="help"><?= e((string) $t['wavelength']) ?>: <strong id="freq-wavelength">—</strong></p>
                </section>
                <section class="card">
                    <h3><?= e((string) $t['power']) ?></h3>
                    <label><?= e((string) $t['watts']) ?>
                        <input type="number" id="power-watts" min="0" step="0.001" placeholder="Ex: 10">
                    </label>
                    <p class="help">dBm: <strong id="power-dbm">—</strong></p>
                    <label>dBm
                        <input type="number" id="power-dbm-input" step="0.1" placeholder="Ex: 40">
                    </label>
                    <p class="help">Watts: <strong id="power-watts-out">—</strong></p>
                </section>
                <section class="card">
                    <h3><?= e((string) $t['distance']) ?></h3>
                    <label><?= e((string) $t['locator_a']) ?>
                        <input type="text" id="locator-a" maxlength="6" placeholder="Ex: JO20LI">
                    </label>
                    <label><?= e((string) $t['locator_b']) ?>
                        <input type="text" id="locator-b" maxlength="6" placeholder="Ex: JN18EU">
                    </label>
                    <p class="help"><?= e((string) $t['estimated_distance']) ?>: <strong id="locator-distance">—</strong></p>
                </section>
            </div>
        </article>
    </div>
    <p id="grid-tool-error" class="flash flash-error is-hidden" style="margin-top:1rem;"></p>
</section>

<script nonce="<?= e(csp_nonce()) ?>">
(() => {
    const i18n = <?= json_encode($jsI18n, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const form = document.getElementById('grid-tool-form');
    const addressInput = document.getElementById('grid-address');
    const result = document.getElementById('grid-tool-result');
    const foundAddress = document.getElementById('grid-found-address');
    const foundCoords = document.getElementById('grid-found-coords');
    const foundLocator = document.getElementById('grid-found-locator');
    const errorBox = document.getElementById('grid-tool-error');
    const freqInput = document.getElementById('freq-mhz');
    const freqOut = document.getElementById('freq-wavelength');
    const wattsInput = document.getElementById('power-watts');
    const dbmInput = document.getElementById('power-dbm-input');
    const dbmOut = document.getElementById('power-dbm');
    const wattsOut = document.getElementById('power-watts-out');
    const locatorA = document.getElementById('locator-a');
    const locatorB = document.getElementById('locator-b');
    const locatorDistance = document.getElementById('locator-distance');
    const receiverSelect = document.getElementById('sdr-receiver');
    const receiverOpenBtn = document.getElementById('sdr-open-btn');
    if (!(form instanceof HTMLFormElement) || !(addressInput instanceof HTMLInputElement)) {
        return;
    }

    const toMaidenhead = (latitude, longitude, precision = 6) => {
        const safeLat = Math.max(-89.999999, Math.min(89.999999, latitude));
        const safeLon = Math.max(-179.999999, Math.min(179.999999, longitude));
        let lon = safeLon + 180;
        let lat = safeLat + 90;
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

        return locator.slice(0, Math.max(4, Math.min(precision, 6))).toUpperCase();
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
            setError(i18n.err_enter_address);
            return;
        }
        try {
            const url = `index.php?route=tools_geocode&q=${encodeURIComponent(query)}`;
            const response = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) {
                throw new Error(i18n.err_geocode_unavailable);
            }
            const payload = await response.json();
            if (!payload?.ok) {
                throw new Error(payload?.error || i18n.err_address_not_found);
            }
            const lat = Number(payload.lat);
            const lon = Number(payload.lon);
            if (!Number.isFinite(lat) || !Number.isFinite(lon)) {
                throw new Error(i18n.err_invalid_coords);
            }
            const locator = toMaidenhead(lat, lon, 6);
            if (foundAddress) foundAddress.textContent = payload.display_name || query;
            if (foundCoords) foundCoords.textContent = `${lat.toFixed(5)}, ${lon.toFixed(5)}`;
            if (foundLocator) foundLocator.textContent = locator;
            result?.classList.remove('is-hidden');
        } catch (error) {
            setError(error instanceof Error ? error.message : i18n.err_grid_calc);
        }
    });

    freqInput?.addEventListener('input', () => {
        if (!(freqInput instanceof HTMLInputElement) || !freqOut) return;
        const mhz = Number(freqInput.value);
        if (!Number.isFinite(mhz) || mhz <= 0) {
            freqOut.textContent = '—';
            return;
        }
        const meters = 299.792458 / mhz;
        freqOut.textContent = `${meters.toFixed(3)} m`;
    });

    wattsInput?.addEventListener('input', () => {
        if (!(wattsInput instanceof HTMLInputElement) || !dbmOut) return;
        const watts = Number(wattsInput.value);
        if (!Number.isFinite(watts) || watts <= 0) {
            dbmOut.textContent = '—';
            return;
        }
        const dbm = 10 * Math.log10(watts * 1000);
        dbmOut.textContent = dbm.toFixed(2);
    });

    dbmInput?.addEventListener('input', () => {
        if (!(dbmInput instanceof HTMLInputElement) || !wattsOut) return;
        const dbm = Number(dbmInput.value);
        if (!Number.isFinite(dbm)) {
            wattsOut.textContent = '—';
            return;
        }
        const watts = Math.pow(10, dbm / 10) / 1000;
        wattsOut.textContent = `${watts.toFixed(4)} W`;
    });

    const locatorToLatLon = (locator) => {
        const normalized = locator.toUpperCase().trim();
        if (!/^[A-R]{2}[0-9]{2}([A-X]{2})?$/.test(normalized)) {
            return null;
        }
        let lon = -180 + (normalized.charCodeAt(0) - 65) * 20 + Number(normalized[2]) * 2 + 1;
        let lat = -90 + (normalized.charCodeAt(1) - 65) * 10 + Number(normalized[3]) + 0.5;
        if (normalized.length === 6) {
            lon += (normalized.charCodeAt(4) - 65) * (5 / 60) + (2.5 / 60);
            lat += (normalized.charCodeAt(5) - 65) * (2.5 / 60) + (1.25 / 60);
        }
        return { lat, lon };
    };
    const haversineKm = (a, b) => {
        const r = 6371;
        const toRad = (v) => v * Math.PI / 180;
        const dLat = toRad(b.lat - a.lat);
        const dLon = toRad(b.lon - a.lon);
        const x = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(a.lat)) * Math.cos(toRad(b.lat)) * Math.sin(dLon / 2) ** 2;
        return 2 * r * Math.asin(Math.sqrt(x));
    };
    const syncDistance = () => {
        if (!(locatorA instanceof HTMLInputElement) || !(locatorB instanceof HTMLInputElement) || !locatorDistance) return;
        const p1 = locatorToLatLon(locatorA.value);
        const p2 = locatorToLatLon(locatorB.value);
        if (!p1 || !p2) {
            locatorDistance.textContent = '—';
            return;
        }
        locatorDistance.textContent = `${haversineKm(p1, p2).toFixed(1)} km`;
    };
    locatorA?.addEventListener('input', syncDistance);
    locatorB?.addEventListener('input', syncDistance);

    receiverOpenBtn?.addEventListener('click', () => {
        if (!(receiverSelect instanceof HTMLSelectElement)) return;
        const url = receiverSelect.value.trim();
        if (url === '') return;
        window.open(url, '_blank', 'noopener,noreferrer');
    });
})();
</script>
<?php
echo render_layout((string) ob_get_clean(), (string) ($t['title'] ?? 'Outils'));
