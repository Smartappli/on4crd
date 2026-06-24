<?php
declare(strict_types=1);

require_permission('admin.access');
require_permission('events.manage');


$locale = current_locale();
$i18n = i18n_load_array_file_once(__DIR__ . '/../app/i18n/admin_dinner_reservations.php');
$i18n = i18n_expand_supported_locales($i18n);
$t = [];
foreach (array_keys($i18n['fr']) as $key) {
    $t[$key] = i18n_localized_value($i18n, $locale, (string) $key);
}

set_page_meta([
    'title' => (string) $t['layout'],
    'description' => (string) $t['meta_desc'],
    'robots' => 'noindex,nofollow',
]);

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
    'potage' => ['label' => (string) $t['starter_potage'], 'price_cents' => 650],
    'croquettes' => ['label' => (string) $t['starter_croquettes'], 'price_cents' => 750],
    'salade' => ['label' => (string) $t['starter_salade'], 'price_cents' => 600],
];
$mainOptions = [
    'vol_au_vent' => ['label' => (string) $t['meal_vol_au_vent'], 'price_cents' => 1800],
    'boulettes' => ['label' => (string) $t['meal_boulettes'], 'price_cents' => 1700],
    'vegetarien' => ['label' => (string) $t['meal_vegetarien'], 'price_cents' => 1650],
];
$dessertOptions = [
    'tiramisu' => ['label' => (string) $t['dessert_tiramisu'], 'price_cents' => 600],
    'mousse_choco' => ['label' => (string) $t['dessert_mousse_choco'], 'price_cents' => 550],
    'salade_fruits' => ['label' => (string) $t['dessert_salade_fruits'], 'price_cents' => 500],
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
            (string) $t['csv_reservation_id'],
            (string) $t['csv_reserved_by'],
            (string) $t['csv_created_at'],
            (string) $t['csv_starter_enabled'],
            (string) $t['csv_starter'],
            (string) $t['csv_starter_price'],
            (string) $t['csv_meal_enabled'],
            (string) $t['csv_meal'],
            (string) $t['csv_meal_price'],
            (string) $t['csv_dessert_enabled'],
            (string) $t['csv_dessert'],
            (string) $t['csv_dessert_price'],
            (string) $t['csv_quantity'],
            (string) $t['csv_line_total'],
            (string) $t['csv_res_total'],
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

<script type="application/json" id="dinner-reservation-config"><?= json_encode([
    'locale' => $locale,
    'starterOptions' => $starterOptions,
    'mainOptions' => $mainOptions,
    'dessertOptions' => $dessertOptions,
    'ui' => [
        'starter' => $t['col_starter'],
        'meal' => $t['col_meal'],
        'dessert' => $t['col_dessert'],
        'qty' => $t['col_qty'],
        'remove' => $t['line_remove'],
        'enableStarter' => $t['line_enable_starter'],
        'enableMeal' => $t['line_enable_meal'],
        'enableDessert' => $t['line_enable_dessert'],
        'lineTotal' => $t['col_line_total'],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '{}' ?></script>
<?php

echo render_layout((string) ob_get_clean(), (string) $t['layout']);
