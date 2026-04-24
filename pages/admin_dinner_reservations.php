<?php
declare(strict_types=1);

require_permission('admin.access');
require_permission('events.manage');

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
        meal_code VARCHAR(64) NOT NULL,
        meal_label VARCHAR(190) NOT NULL,
        meal_price_cents INT NOT NULL,
        dessert_code VARCHAR(64) NOT NULL,
        dessert_label VARCHAR(190) NOT NULL,
        dessert_price_cents INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        line_total_cents INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_dinner_reservation_id (reservation_id),
        CONSTRAINT fk_dinner_reservation_lines_reservation FOREIGN KEY (reservation_id) REFERENCES dinner_reservations(id) ON DELETE CASCADE
    )'
);

$mealOptions = [
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

        $mealCode = (string) ($line['meal'] ?? '');
        $dessertCode = (string) ($line['dessert'] ?? '');
        $quantity = (int) ($line['quantity'] ?? 0);

        if ($quantity <= 0) {
            continue;
        }
        if (!isset($mealOptions[$mealCode]) || !isset($dessertOptions[$dessertCode])) {
            continue;
        }

        $meal = $mealOptions[$mealCode];
        $dessert = $dessertOptions[$dessertCode];
        $lineTotal = ($meal['price_cents'] + $dessert['price_cents']) * $quantity;
        $totalCents += $lineTotal;

        $lines[] = [
            'meal_code' => $mealCode,
            'meal_label' => $meal['label'],
            'meal_price_cents' => (int) $meal['price_cents'],
            'dessert_code' => $dessertCode,
            'dessert_label' => $dessert['label'],
            'dessert_price_cents' => (int) $dessert['price_cents'],
            'quantity' => $quantity,
            'line_total_cents' => $lineTotal,
        ];
    }

    if ($reservedBy === '' || $lines === []) {
        set_flash('error', 'Veuillez indiquer un nom et au moins une ligne de réservation valide.');
        redirect('admin_dinner_reservations');
    }

    db()->beginTransaction();
    try {
        $reservationStmt = db()->prepare('INSERT INTO dinner_reservations (reserved_by, total_cents, notes) VALUES (?, ?, ?)');
        $reservationStmt->execute([$reservedBy, $totalCents, $notes !== '' ? $notes : null]);
        $reservationId = (int) db()->lastInsertId();

        $lineStmt = db()->prepare(
            'INSERT INTO dinner_reservation_lines
             (reservation_id, meal_code, meal_label, meal_price_cents, dessert_code, dessert_label, dessert_price_cents, quantity, line_total_cents)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        foreach ($lines as $line) {
            $lineStmt->execute([
                $reservationId,
                $line['meal_code'],
                $line['meal_label'],
                $line['meal_price_cents'],
                $line['dessert_code'],
                $line['dessert_label'],
                $line['dessert_price_cents'],
                $line['quantity'],
                $line['line_total_cents'],
            ]);
        }

        db()->commit();
        set_flash('success', 'Réservation enregistrée.');
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

ob_start();
?>
<section class="card">
    <h1>Réservations dîner annuel</h1>
    <form method="post" class="stack" id="dinner-reservation-form">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <label>
            Nom de la réservation
            <input type="text" name="reserved_by" required maxlength="190" placeholder="Ex: Famille Dupont">
        </label>

        <div class="stack" id="dinner-lines"></div>

        <div class="row-between">
            <button type="button" class="button secondary" id="add-dinner-line">Ajouter une ligne</button>
            <strong>Total à payer : <span id="dinner-total">0,00 €</span></strong>
        </div>

        <label>
            Notes
            <textarea name="notes" rows="3" placeholder="Remarques allergènes, options, etc."></textarea>
        </label>

        <button type="submit" class="button">Enregistrer la réservation</button>
    </form>
</section>

<section class="card mt-4">
    <h2>Historique</h2>
    <?php if ($reservations === []): ?>
        <p>Aucune réservation enregistrée.</p>
    <?php else: ?>
        <?php foreach ($reservations as $reservation): ?>
            <?php $reservationId = (int) $reservation['id']; ?>
            <article class="inner-card mt-4">
                <h3><?= e((string) $reservation['reserved_by']) ?> — <?= e((string) $reservation['created_at']) ?></h3>
                <p><strong>Total :</strong> <?= e(format_price_eur((int) $reservation['total_cents'])) ?></p>
                <?php if (trim((string) ($reservation['notes'] ?? '')) !== ''): ?>
                    <p class="help"><?= nl2br(e((string) $reservation['notes'])) ?></p>
                <?php endif; ?>
                <table>
                    <thead>
                        <tr>
                            <th>Repas</th>
                            <th>Dessert</th>
                            <th>Quantité</th>
                            <th>Total ligne</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach (($linesByReservation[$reservationId] ?? []) as $line): ?>
                        <tr>
                            <td><?= e((string) $line['meal_label']) ?> (<?= e(format_price_eur((int) $line['meal_price_cents'])) ?>)</td>
                            <td><?= e((string) $line['dessert_label']) ?> (<?= e(format_price_eur((int) $line['dessert_price_cents'])) ?>)</td>
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
    const mealOptions = <?= json_encode($mealOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const dessertOptions = <?= json_encode($dessertOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const linesContainer = document.getElementById('dinner-lines');
    const addButton = document.getElementById('add-dinner-line');
    const totalEl = document.getElementById('dinner-total');
    let lineIndex = 0;

    const formatEur = (cents) => new Intl.NumberFormat('fr-BE', { style: 'currency', currency: 'EUR' }).format((Number(cents) || 0) / 100);

    const renderSelectOptions = (options) => Object.entries(options)
        .map(([key, value]) => `<option value="${key}" data-price="${value.price_cents}">${value.label} (${formatEur(value.price_cents)})</option>`)
        .join('');

    const updateTotals = () => {
        let total = 0;
        linesContainer.querySelectorAll('.dinner-line').forEach((line) => {
            const mealPrice = Number(line.querySelector('.meal-select option:checked')?.dataset.price || 0);
            const dessertPrice = Number(line.querySelector('.dessert-select option:checked')?.dataset.price || 0);
            const quantity = Number(line.querySelector('.quantity-input').value || 0);
            const lineTotal = (mealPrice + dessertPrice) * quantity;
            line.querySelector('.line-total').textContent = formatEur(lineTotal);
            total += lineTotal;
        });
        totalEl.textContent = formatEur(total);
    };

    const addLine = () => {
        const wrapper = document.createElement('div');
        wrapper.className = 'grid-3 dinner-line';
        wrapper.innerHTML = `
            <label>Repas
                <select class="meal-select" name="lines[${lineIndex}][meal]">${renderSelectOptions(mealOptions)}</select>
            </label>
            <label>Dessert
                <select class="dessert-select" name="lines[${lineIndex}][dessert]">${renderSelectOptions(dessertOptions)}</select>
            </label>
            <label>Nombre
                <input class="quantity-input" type="number" name="lines[${lineIndex}][quantity]" min="0" step="1" value="1">
            </label>
            <p class="help">Total ligne : <strong class="line-total">${formatEur(0)}</strong></p>
            <p><button type="button" class="button secondary remove-line">Supprimer</button></p>
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

echo render_layout((string) ob_get_clean(), 'Administration dîner annuel');
