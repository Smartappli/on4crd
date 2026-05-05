<?php
declare(strict_types=1);

require_permission('admin.access');
require_permission('events.manage');


$locale = current_locale();
$i18n = [
    'fr' => ['err_invalid_reservation' => 'Veuillez indiquer un nom et au moins une ligne de réservation valide.', 'ok_saved' => 'Réservation enregistrée.', 'notes_placeholder' => 'Remarques allergènes, options, etc.', 'layout' => 'Administration dîner annuel', 'title' => 'Réservations dîner annuel', 'export_csv' => 'Exporter Excel (CSV)', 'reservation_name' => 'Nom de la réservation', 'reservation_name_ph' => 'Ex: Famille Dupont', 'add_line' => 'Ajouter une ligne', 'total_to_pay' => 'Total à payer :', 'notes' => 'Notes', 'save_reservation' => 'Enregistrer la réservation', 'history' => 'Historique', 'no_history' => 'Aucune réservation enregistrée.', 'total' => 'Total :', 'col_starter' => 'Entrée', 'col_meal' => 'Repas', 'col_dessert' => 'Dessert', 'col_qty' => 'Quantité', 'col_line_total' => 'Total ligne', 'yes' => 'Oui', 'no' => 'Non', 'csv_reservation_id' => 'Reservation ID', 'csv_reserved_by' => 'Nom', 'csv_created_at' => 'Date', 'csv_starter_enabled' => 'Entree active', 'csv_starter' => 'Entree', 'csv_starter_price' => 'Prix entree', 'csv_meal_enabled' => 'Plat actif', 'csv_meal' => 'Plat', 'csv_meal_price' => 'Prix plat', 'csv_dessert_enabled' => 'Dessert actif', 'csv_dessert' => 'Dessert', 'csv_dessert_price' => 'Prix dessert', 'csv_quantity' => 'Quantite', 'csv_line_total' => 'Total ligne', 'csv_res_total' => 'Total reservation'],
    'en' => ['err_invalid_reservation' => 'Please provide a name and at least one valid reservation line.', 'ok_saved' => 'Reservation saved.', 'notes_placeholder' => 'Allergen notes, options, etc.', 'layout' => 'Annual dinner administration', 'title' => 'Annual dinner reservations', 'export_csv' => 'Export Excel (CSV)', 'reservation_name' => 'Reservation name', 'reservation_name_ph' => 'Ex: Dupont Family', 'add_line' => 'Add a line', 'total_to_pay' => 'Total to pay:', 'notes' => 'Notes', 'save_reservation' => 'Save reservation', 'history' => 'History', 'no_history' => 'No reservations recorded.', 'total' => 'Total:', 'col_starter' => 'Starter', 'col_meal' => 'Meal', 'col_dessert' => 'Dessert', 'col_qty' => 'Quantity', 'col_line_total' => 'Line total', 'yes' => 'Yes', 'no' => 'No', 'csv_reservation_id' => 'Reservation ID', 'csv_reserved_by' => 'Name', 'csv_created_at' => 'Date', 'csv_starter_enabled' => 'Starter enabled', 'csv_starter' => 'Starter', 'csv_starter_price' => 'Starter price', 'csv_meal_enabled' => 'Meal enabled', 'csv_meal' => 'Meal', 'csv_meal_price' => 'Meal price', 'csv_dessert_enabled' => 'Dessert enabled', 'csv_dessert' => 'Dessert', 'csv_dessert_price' => 'Dessert price', 'csv_quantity' => 'Quantity', 'csv_line_total' => 'Line total', 'csv_res_total' => 'Reservation total'],
    'de' => ['err_invalid_reservation' => 'Bitte geben Sie einen Namen und mindestens eine gültige Reservierungszeile an.', 'ok_saved' => 'Reservierung gespeichert.', 'notes_placeholder' => 'Allergiehinweise, Optionen usw.', 'layout' => 'Verwaltung Jahresessen', 'title' => 'Reservierungen Jahresessen', 'export_csv' => 'Excel exportieren (CSV)', 'reservation_name' => 'Reservierungsname', 'reservation_name_ph' => 'Bsp.: Familie Dupont', 'add_line' => 'Zeile hinzufügen', 'total_to_pay' => 'Zu zahlender Betrag:', 'notes' => 'Notizen', 'save_reservation' => 'Reservierung speichern', 'history' => 'Verlauf', 'no_history' => 'Keine Reservierungen gespeichert.', 'total' => 'Gesamt:', 'col_starter' => 'Vorspeise', 'col_meal' => 'Hauptgericht', 'col_dessert' => 'Dessert', 'col_qty' => 'Menge', 'col_line_total' => 'Zeilensumme', 'yes' => 'Ja', 'no' => 'Nein', 'csv_reservation_id' => 'Reservierungs-ID', 'csv_reserved_by' => 'Name', 'csv_created_at' => 'Datum', 'csv_starter_enabled' => 'Vorspeise aktiv', 'csv_starter' => 'Vorspeise', 'csv_starter_price' => 'Preis Vorspeise', 'csv_meal_enabled' => 'Hauptgericht aktiv', 'csv_meal' => 'Hauptgericht', 'csv_meal_price' => 'Preis Hauptgericht', 'csv_dessert_enabled' => 'Dessert aktiv', 'csv_dessert' => 'Dessert', 'csv_dessert_price' => 'Preis Dessert', 'csv_quantity' => 'Menge', 'csv_line_total' => 'Zeilensumme', 'csv_res_total' => 'Reservierung gesamt'],
    'nl' => ['err_invalid_reservation' => 'Geef een naam en minstens één geldige reserveringsregel op.', 'ok_saved' => 'Reservering opgeslagen.', 'notes_placeholder' => 'Allergenenopmerkingen, opties, enz.', 'layout' => 'Beheer jaarlijks diner', 'title' => 'Reserveringen jaarlijks diner', 'export_csv' => 'Excel exporteren (CSV)', 'reservation_name' => 'Naam van de reservering', 'reservation_name_ph' => 'Bijv.: Familie Dupont', 'add_line' => 'Lijn toevoegen', 'total_to_pay' => 'Totaal te betalen:', 'notes' => 'Notities', 'save_reservation' => 'Reservering opslaan', 'history' => 'Historiek', 'no_history' => 'Geen reserveringen opgeslagen.', 'total' => 'Totaal:', 'col_starter' => 'Voorgerecht', 'col_meal' => 'Hoofdgerecht', 'col_dessert' => 'Dessert', 'col_qty' => 'Aantal', 'col_line_total' => 'Lijntotaal', 'yes' => 'Ja', 'no' => 'Nee', 'csv_reservation_id' => 'Reserverings-ID', 'csv_reserved_by' => 'Naam', 'csv_created_at' => 'Datum', 'csv_starter_enabled' => 'Voorgerecht actief', 'csv_starter' => 'Voorgerecht', 'csv_starter_price' => 'Prijs voorgerecht', 'csv_meal_enabled' => 'Hoofdgerecht actief', 'csv_meal' => 'Hoofdgerecht', 'csv_meal_price' => 'Prijs hoofdgerecht', 'csv_dessert_enabled' => 'Dessert actief', 'csv_dessert' => 'Dessert', 'csv_dessert_price' => 'Prijs dessert', 'csv_quantity' => 'Aantal', 'csv_line_total' => 'Lijntotaal', 'csv_res_total' => 'Reservering totaal'],
];
$t = $i18n[$locale] ?? $i18n['fr'];

db()->exec(
    'CREATE TABLE IF NOT EXISTS dinner_reservations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reserved_by VARCHAR(190) NOT NULL,
        total_cents INT NOT NULL DEFAULT 0,
        notes TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )'
);
db()->exec(
    'CREATE TABLE IF NOT EXISTS dinner_reservation_lines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reservation_id INT NOT NULL,
        starter_code VARCHAR(64) DEFAULT NULL,
        starter_label VARCHAR(190) DEFAULT NULL,
        starter_price_cents INT NOT NULL DEFAULT 0,
        meal_code VARCHAR(64) NOT NULL,
        meal_label VARCHAR(190) NOT NULL,
        meal_price_cents INT NOT NULL,
        dessert_code VARCHAR(64) NOT NULL,
        dessert_label VARCHAR(190) NOT NULL,
        dessert_price_cents INT NOT NULL,
        starter_enabled TINYINT(1) NOT NULL DEFAULT 0,
        meal_enabled TINYINT(1) NOT NULL DEFAULT 1,
        dessert_enabled TINYINT(1) NOT NULL DEFAULT 1,
        quantity INT NOT NULL DEFAULT 1,
        line_total_cents INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_dinner_reservation_id (reservation_id),
        CONSTRAINT fk_dinner_reservation_lines_reservation FOREIGN KEY (reservation_id) REFERENCES dinner_reservations(id) ON DELETE CASCADE
    )'
);

$columnStmt = db()->prepare(
    'SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
);
$lineColumnUpdates = [
    'starter_code' => 'ALTER TABLE dinner_reservation_lines ADD COLUMN starter_code VARCHAR(64) DEFAULT NULL AFTER reservation_id',
    'starter_label' => 'ALTER TABLE dinner_reservation_lines ADD COLUMN starter_label VARCHAR(190) DEFAULT NULL AFTER starter_code',
    'starter_price_cents' => 'ALTER TABLE dinner_reservation_lines ADD COLUMN starter_price_cents INT NOT NULL DEFAULT 0 AFTER starter_label',
    'starter_enabled' => 'ALTER TABLE dinner_reservation_lines ADD COLUMN starter_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER dessert_price_cents',
    'meal_enabled' => 'ALTER TABLE dinner_reservation_lines ADD COLUMN meal_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER starter_enabled',
    'dessert_enabled' => 'ALTER TABLE dinner_reservation_lines ADD COLUMN dessert_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER meal_enabled',
];
foreach ($lineColumnUpdates as $columnName => $statement) {
    $columnStmt->execute(['dinner_reservation_lines', $columnName]);
    if ((int) $columnStmt->fetchColumn() <= 0) {
        db()->exec($statement);
    }
}

$starterOptions = [
    'potage' => ['label' => 'Potage maison', 'price_cents' => 650],
    'croquettes' => ['label' => 'Croquettes de fromage', 'price_cents' => 750],
    'salade' => ['label' => 'Petite salade', 'price_cents' => 600],
];
$mainOptions = [
    'vol_au_vent' => ['label' => 'Vol-au-vent', 'price_cents' => 1800],
    'boulettes' => ['label' => 'Boulettes sauce tomate', 'price_cents' => 1700],
    'vegetarien' => ['label' => 'Assiette végétarienne', 'price_cents' => 1650],
];
$dessertOptions = [
    'tiramisu' => ['label' => 'Tiramisu', 'price_cents' => 600],
    'mousse_choco' => ['label' => 'Mousse au chocolat', 'price_cents' => 550],
    'salade_fruits' => ['label' => 'Salade de fruits', 'price_cents' => 500],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $reservedBy = trim((string) ($_POST['reserved_by'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));
    $postedLines = (array) ($_POST['lines'] ?? []);

    $lines = [];
    $totalCents = 0;
    foreach ($postedLines as $line) {
        if (!is_array($line)) {
            continue;
        }

        $starterEnabled = isset($line['starter_enabled']) && (string) $line['starter_enabled'] === '1';
        $mealEnabled = isset($line['meal_enabled']) && (string) $line['meal_enabled'] === '1';
        $dessertEnabled = isset($line['dessert_enabled']) && (string) $line['dessert_enabled'] === '1';
        if (!$starterEnabled && !$mealEnabled && !$dessertEnabled) {
            continue;
        }

        $starterCode = (string) ($line['starter'] ?? '');
        $mealCode = (string) ($line['meal'] ?? '');
        $dessertCode = (string) ($line['dessert'] ?? '');
        $quantity = (int) ($line['quantity'] ?? 0);

        if ($quantity <= 0) {
            continue;
        }
        if (($starterEnabled && !isset($starterOptions[$starterCode]))
            || ($mealEnabled && !isset($mainOptions[$mealCode]))
            || ($dessertEnabled && !isset($dessertOptions[$dessertCode]))
        ) {
            continue;
        }

        $starter = $starterEnabled ? $starterOptions[$starterCode] : ['label' => '', 'price_cents' => 0];
        $meal = $mealEnabled ? $mainOptions[$mealCode] : ['label' => '', 'price_cents' => 0];
        $dessert = $dessertEnabled ? $dessertOptions[$dessertCode] : ['label' => '', 'price_cents' => 0];
        $lineTotal = ((int) $starter['price_cents'] + (int) $meal['price_cents'] + (int) $dessert['price_cents']) * $quantity;
        $totalCents += $lineTotal;

        $lines[] = [
            'starter_code' => $starterEnabled ? $starterCode : '',
            'starter_label' => $starter['label'],
            'starter_price_cents' => (int) $starter['price_cents'],
            'meal_code' => $mealEnabled ? $mealCode : '',
            'meal_label' => $meal['label'],
            'meal_price_cents' => (int) $meal['price_cents'],
            'dessert_code' => $dessertEnabled ? $dessertCode : '',
            'dessert_label' => $dessert['label'],
            'dessert_price_cents' => (int) $dessert['price_cents'],
            'starter_enabled' => $starterEnabled ? 1 : 0,
            'meal_enabled' => $mealEnabled ? 1 : 0,
            'dessert_enabled' => $dessertEnabled ? 1 : 0,
            'quantity' => $quantity,
            'line_total_cents' => $lineTotal,
        ];
    }

    if ($reservedBy === '' || $lines === []) {
        set_flash('error', (string) $t['err_invalid_reservation']);
        redirect('admin_dinner_reservations');
    }

    db()->beginTransaction();
    try {
        $reservationStmt = db()->prepare('INSERT INTO dinner_reservations (reserved_by, total_cents, notes) VALUES (?, ?, ?)');
        $reservationStmt->execute([$reservedBy, $totalCents, $notes !== '' ? $notes : null]);
        $reservationId = (int) db()->lastInsertId();

        $lineStmt = db()->prepare(
            'INSERT INTO dinner_reservation_lines
             (reservation_id, starter_code, starter_label, starter_price_cents, meal_code, meal_label, meal_price_cents, dessert_code, dessert_label, dessert_price_cents, starter_enabled, meal_enabled, dessert_enabled, quantity, line_total_cents)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        foreach ($lines as $line) {
            $lineStmt->execute([
                $reservationId,
                $line['starter_code'] !== '' ? $line['starter_code'] : null,
                $line['starter_label'] !== '' ? $line['starter_label'] : null,
                $line['starter_price_cents'],
                $line['meal_code'],
                $line['meal_label'],
                $line['meal_price_cents'],
                $line['dessert_code'],
                $line['dessert_label'],
                $line['dessert_price_cents'],
                $line['starter_enabled'],
                $line['meal_enabled'],
                $line['dessert_enabled'],
                $line['quantity'],
                $line['line_total_cents'],
            ]);
        }

        db()->commit();
        set_flash('success', (string) $t['ok_saved']);
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        throw $e;
    }

    redirect('admin_dinner_reservations');
}

$reservations = db()->query('SELECT * FROM dinner_reservations ORDER BY id DESC LIMIT 100')->fetchAll() ?: [];
$linesByReservation = [];
if ($reservations !== []) {
    $ids = array_map(static fn(array $row): int => (int) $row['id'], $reservations);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare('SELECT * FROM dinner_reservation_lines WHERE reservation_id IN (' . $placeholders . ') ORDER BY id ASC');
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() ?: [] as $line) {
        $reservationId = (int) ($line['reservation_id'] ?? 0);
        $linesByReservation[$reservationId][] = $line;
    }
}

if ((string) ($_GET['export'] ?? '') === '1') {
    $rows = db()->query(
        'SELECT r.id AS reservation_id,
                r.reserved_by,
                r.created_at,
                r.total_cents AS reservation_total_cents,
                l.starter_enabled,
                l.starter_label,
                l.starter_price_cents,
                l.meal_enabled,
                l.meal_label,
                l.meal_price_cents,
                l.dessert_enabled,
                l.dessert_label,
                l.dessert_price_cents,
                l.quantity,
                l.line_total_cents
         FROM dinner_reservations r
         INNER JOIN dinner_reservation_lines l ON l.reservation_id = r.id
         ORDER BY r.id DESC, l.id ASC'
    )->fetchAll() ?: [];

    $filename = 'reservations_diner_annuel_' . gmdate('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'wb');
    if ($output !== false) {
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, [
            'Reservation ID',
            'Nom reservation',
            'Date',
            'Entree active',
            'Entree',
            'Prix entree',
            'Plat actif',
            'Plat',
            'Prix plat',
            'Dessert actif',
            'Dessert',
            'Prix dessert',
            'Quantite',
            'Total ligne',
            'Total reservation',
        ], ';');

        foreach ($rows as $row) {
            fputcsv($output, [
                (int) ($row['reservation_id'] ?? 0),
                (string) ($row['reserved_by'] ?? ''),
                (string) ($row['created_at'] ?? ''),
                (int) ($row['starter_enabled'] ?? 0) === 1 ? (string) $t['yes'] : (string) $t['no'],
                (string) ($row['starter_label'] ?? ''),
                number_format(((int) ($row['starter_price_cents'] ?? 0)) / 100, 2, ',', ''),
                (int) ($row['meal_enabled'] ?? 0) === 1 ? (string) $t['yes'] : (string) $t['no'],
                (string) ($row['meal_label'] ?? ''),
                number_format(((int) ($row['meal_price_cents'] ?? 0)) / 100, 2, ',', ''),
                (int) ($row['dessert_enabled'] ?? 0) === 1 ? (string) $t['yes'] : (string) $t['no'],
                (string) ($row['dessert_label'] ?? ''),
                number_format(((int) ($row['dessert_price_cents'] ?? 0)) / 100, 2, ',', ''),
                (int) ($row['quantity'] ?? 0),
                number_format(((int) ($row['line_total_cents'] ?? 0)) / 100, 2, ',', ''),
                number_format(((int) ($row['reservation_total_cents'] ?? 0)) / 100, 2, ',', ''),
            ], ';');
        }
        fclose($output);
    }
    exit;
}

ob_start();
?>
<section class="card">
    <h1><?= e((string) $t['title']) ?></h1>
    <div class="row-between"><span></span><a class="button secondary" href="<?= e(route_url('admin_dinner_reservations', ['export' => 1])) ?>"><?= e((string) $t['export_csv']) ?></a></div>
    <form method="post" class="stack" id="dinner-reservation-form">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <label>
            <?= e((string) $t['reservation_name']) ?>
            <input type="text" name="reserved_by" required maxlength="190" placeholder="<?= e((string) $t['reservation_name_ph']) ?>">
        </label>

        <div class="stack" id="dinner-lines"></div>

        <div class="row-between">
            <button type="button" class="button secondary" id="add-dinner-line"><?= e((string) $t['add_line']) ?></button>
            <strong><?= e((string) $t['total_to_pay']) ?> <span id="dinner-total">0,00 €</span></strong>
        </div>

        <label>
            <?= e((string) $t['notes']) ?>
            <textarea name="notes" rows="3" placeholder="<?= e((string) $t['notes_placeholder']) ?>"></textarea>
        </label>

        <button type="submit" class="button"><?= e((string) $t['save_reservation']) ?></button>
    </form>
</section>

<section class="card mt-4">
    <h2><?= e((string) $t['history']) ?></h2>
    <?php if ($reservations === []): ?>
        <p><?= e((string) $t['no_history']) ?></p>
    <?php else: ?>
        <?php foreach ($reservations as $reservation): ?>
            <?php $reservationId = (int) $reservation['id']; ?>
            <article class="inner-card mt-4">
                <h3><?= e((string) $reservation['reserved_by']) ?> — <?= e((string) $reservation['created_at']) ?></h3>
                <p><strong><?= e((string) $t['total']) ?></strong>  <?= e(format_price_eur((int) $reservation['total_cents'])) ?></p>
                <?php if (trim((string) ($reservation['notes'] ?? '')) !== ''): ?>
                    <p class="help"><?= nl2br(e((string) $reservation['notes'])) ?></p>
                <?php endif; ?>
                <table>
                    <thead>
                        <tr>
                            <th><?= e((string) $t['col_starter']) ?></th>
                            <th><?= e((string) $t['col_meal']) ?></th>
                            <th><?= e((string) $t['col_dessert']) ?></th>
                            <th><?= e((string) $t['col_qty']) ?></th>
                            <th><?= e((string) $t['col_line_total']) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach (($linesByReservation[$reservationId] ?? []) as $line): ?>
                        <tr>
                            <td><?= (int) ($line['starter_enabled'] ?? 0) === 1 ? e((string) $line['starter_label']) . ' (' . e(format_price_eur((int) $line['starter_price_cents'])) . ')' : '—' ?></td>
                            <td><?= (int) ($line['meal_enabled'] ?? 0) === 1 ? e((string) $line['meal_label']) . ' (' . e(format_price_eur((int) $line['meal_price_cents'])) . ')' : '—' ?></td>
                            <td><?= (int) ($line['dessert_enabled'] ?? 0) === 1 ? e((string) $line['dessert_label']) . ' (' . e(format_price_eur((int) $line['dessert_price_cents'])) . ')' : '—' ?></td>
                            <td><?= (int) $line['quantity'] ?></td>
                            <td><?= e(format_price_eur((int) $line['line_total_cents'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<script nonce="<?= e(csp_nonce()) ?>">
(() => {
    const starterOptions = <?= json_encode($starterOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const mainOptions = <?= json_encode($mainOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const dessertOptions = <?= json_encode($dessertOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const linesContainer = document.getElementById('dinner-lines');
    const addButton = document.getElementById('add-dinner-line');
    const totalEl = document.getElementById('dinner-total');
    let lineIndex = 0;

    const localeMap = { fr: 'fr-BE', en: 'en-GB', de: 'de-DE', nl: 'nl-BE' };
    const numberLocale = localeMap[<?= json_encode($locale) ?>] || 'fr-BE';
    const formatEur = (cents) => new Intl.NumberFormat(numberLocale, { style: 'currency', currency: 'EUR' }).format((Number(cents) || 0) / 100);

    const renderSelectOptions = (options) => Object.entries(options)
        .map(([key, value]) => `<option value="${key}" data-price="${value.price_cents}">${value.label} (${formatEur(value.price_cents)})</option>`)
        .join('');

    const updateTotals = () => {
        let total = 0;
        linesContainer.querySelectorAll('.dinner-line').forEach((line) => {
            const starterEnabled = line.querySelector('.starter-enabled')?.checked;
            const mealEnabled = line.querySelector('.meal-enabled')?.checked;
            const dessertEnabled = line.querySelector('.dessert-enabled')?.checked;
            const starterPrice = starterEnabled ? Number(line.querySelector('.starter-select option:checked')?.dataset.price || 0) : 0;
            const mealPrice = mealEnabled ? Number(line.querySelector('.meal-select option:checked')?.dataset.price || 0) : 0;
            const dessertPrice = dessertEnabled ? Number(line.querySelector('.dessert-select option:checked')?.dataset.price || 0) : 0;
            const quantity = Number(line.querySelector('.quantity-input').value || 0);
            const lineTotal = (starterPrice + mealPrice + dessertPrice) * quantity;
            line.querySelector('.line-total').textContent = formatEur(lineTotal);
            total += lineTotal;

            const starterSelect = line.querySelector('.starter-select');
            const mealSelect = line.querySelector('.meal-select');
            const dessertSelect = line.querySelector('.dessert-select');
            if (starterSelect) {
                starterSelect.disabled = !starterEnabled;
            }
            if (mealSelect) {
                mealSelect.disabled = !mealEnabled;
            }
            if (dessertSelect) {
                dessertSelect.disabled = !dessertEnabled;
            }
        });
        totalEl.textContent = formatEur(total);
    };

    const addLine = () => {
        const wrapper = document.createElement('div');
        wrapper.className = 'stack inner-card dinner-line';
        wrapper.innerHTML = `
            <div class="grid-3">
                <label><input class="starter-enabled" type="checkbox" name="lines[${lineIndex}][starter_enabled]" value="1"> Activer entrée</label>
                <label><input class="meal-enabled" type="checkbox" name="lines[${lineIndex}][meal_enabled]" value="1" checked> Activer plat</label>
                <label><input class="dessert-enabled" type="checkbox" name="lines[${lineIndex}][dessert_enabled]" value="1" checked> Activer dessert</label>
            </div>
            <div class="grid-3">
                <label>Entrée
                    <select class="starter-select" name="lines[${lineIndex}][starter]">${renderSelectOptions(starterOptions)}</select>
                </label>
                <label>Plat
                    <select class="meal-select" name="lines[${lineIndex}][meal]">${renderSelectOptions(mainOptions)}</select>
                </label>
                <label>Dessert
                    <select class="dessert-select" name="lines[${lineIndex}][dessert]">${renderSelectOptions(dessertOptions)}</select>
                </label>
            </div>
            <div class="grid-3">
                <label>Nombre
                    <input class="quantity-input" type="number" name="lines[${lineIndex}][quantity]" min="0" step="1" value="1">
                </label>
                <p class="help">Total ligne : <strong class="line-total">${formatEur(0)}</strong></p>
                <p><button type="button" class="button secondary remove-line">Supprimer</button></p>
            </div>
        `;
        lineIndex += 1;
        linesContainer.appendChild(wrapper);
        wrapper.querySelectorAll('select,input').forEach((input) => input.addEventListener('input', updateTotals));
        wrapper.querySelector('.remove-line')?.addEventListener('click', () => {
            wrapper.remove();
            updateTotals();
        });
        updateTotals();
    };

    addButton?.addEventListener('click', addLine);
    addLine();
})();
</script>
<?php

echo render_layout((string) ob_get_clean(), (string) $t['layout']);
