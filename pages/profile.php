<?php
declare(strict_types=1);

$user = require_login();
$memberId = (int) ($user['id'] ?? 0);

$visibilityOptions = [
    'public' => 'Public',
    'members' => 'Membres',
    'private' => 'Comité',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $allowedVisibilities = array_keys($visibilityOptions);
    $visibilityFields = [
        'visibility_full_name',
        'visibility_email',
        'visibility_phone',
        'visibility_qth',
        'visibility_licence_class',
        'visibility_favourite_bands',
        'visibility_station',
    ];
    $visibilityPayload = [];
    foreach ($visibilityFields as $field) {
        $value = (string) ($_POST[$field] ?? 'members');
        $visibilityPayload[$field] = in_array($value, $allowedVisibilities, true) ? $value : 'members';
    }

    $stmt = db()->prepare(
        'UPDATE members
         SET visibility_full_name = ?,
             visibility_email = ?,
             visibility_phone = ?,
             visibility_qth = ?,
             visibility_licence_class = ?,
             visibility_favourite_bands = ?,
             visibility_station = ?
         WHERE id = ?'
    );
    $stmt->execute([
        $visibilityPayload['visibility_full_name'],
        $visibilityPayload['visibility_email'],
        $visibilityPayload['visibility_phone'],
        $visibilityPayload['visibility_qth'],
        $visibilityPayload['visibility_licence_class'],
        $visibilityPayload['visibility_favourite_bands'],
        $visibilityPayload['visibility_station'],
        $memberId,
    ]);

    set_flash('success', 'Préférences de visibilité mises à jour.');
    redirect('profile');
}

$stmt = db()->prepare(
    'SELECT callsign, full_name, email, phone, qth, licence_class, favourite_bands, station_equipment,
            visibility_full_name, visibility_email, visibility_phone, visibility_qth, visibility_licence_class, visibility_favourite_bands, visibility_station
     FROM members
     WHERE id = ? LIMIT 1'
);
$stmt->execute([$memberId]);
$member = $stmt->fetch() ?: [];

ob_start();
?>
<div class="card">
    <h1>Profil</h1>
    <p><strong>Indicatif :</strong> <?= e((string) ($member['callsign'] ?? '')) ?></p>
    <p><strong>Nom :</strong> <?= e((string) ($member['full_name'] ?? '')) ?></p>
    <p><strong>Email :</strong> <?= e((string) ($member['email'] ?? '')) ?></p>
</div>

<section class="card">
    <h2>Visibilité dans l'annuaire</h2>
    <p class="help">Choisissez qui peut voir chaque information : public, membres connectés ou comité.</p>
    <form method="post" class="stack">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <label>
            Nom complet
            <select name="visibility_full_name">
                <?php foreach ($visibilityOptions as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= ((string) ($member['visibility_full_name'] ?? 'members') === $value) ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Email
            <select name="visibility_email">
                <?php foreach ($visibilityOptions as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= ((string) ($member['visibility_email'] ?? 'members') === $value) ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Téléphone
            <select name="visibility_phone">
                <?php foreach ($visibilityOptions as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= ((string) ($member['visibility_phone'] ?? 'private') === $value) ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            QTH
            <select name="visibility_qth">
                <?php foreach ($visibilityOptions as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= ((string) ($member['visibility_qth'] ?? 'members') === $value) ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Licence
            <select name="visibility_licence_class">
                <?php foreach ($visibilityOptions as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= ((string) ($member['visibility_licence_class'] ?? 'members') === $value) ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Bandes favorites
            <select name="visibility_favourite_bands">
                <?php foreach ($visibilityOptions as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= ((string) ($member['visibility_favourite_bands'] ?? 'members') === $value) ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Station
            <select name="visibility_station">
                <?php foreach ($visibilityOptions as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= ((string) ($member['visibility_station'] ?? 'members') === $value) ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <button type="submit" class="button">Enregistrer</button>
    </form>
</section>
<?php

echo render_layout((string) ob_get_clean(), 'Profil');
