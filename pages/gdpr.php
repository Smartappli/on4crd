<?php
declare(strict_types=1);

$user = require_login();
$memberId = (int) ($user['id'] ?? 0);
$locale = current_locale();
$t = i18n_domain_translator('profile', $locale);

set_page_meta([
    'title' => 'Vie privée',
    'description' => $t('meta_desc'),
    'schema_type' => 'ProfilePage',
]);

$visibilityOptions = [
    'public' => $t('public'),
    'members' => $t('members'),
    'private' => $t('private'),
];
$visibilityFields = [
    'visibility_photo' => ['label' => $t('photo'), 'default' => 'members'],
    'visibility_full_name' => ['label' => $t('full_name'), 'default' => 'members'],
    'visibility_email' => ['label' => $t('email'), 'default' => 'members'],
    'visibility_phone' => ['label' => $t('phone'), 'default' => 'private'],
    'visibility_country' => ['label' => $t('country'), 'default' => 'members'],
    'visibility_qth' => ['label' => $t('qth'), 'default' => 'members'],
    'visibility_locator' => ['label' => $t('grid'), 'default' => 'members'],
    'visibility_bio' => ['label' => $t('bio'), 'default' => 'members'],
    'visibility_licence_class' => ['label' => $t('licence'), 'default' => 'members'],
    'visibility_qsl' => ['label' => $t('qsl_info'), 'default' => 'members'],
    'visibility_qrz' => ['label' => $t('qrz_url'), 'default' => 'members'],
    'visibility_uba' => ['label' => $t('uba_member'), 'default' => 'members'],
    'visibility_favourite_bands' => ['label' => $t('bands'), 'default' => 'members'],
    'visibility_favourite_modes' => ['label' => $t('favourite_modes'), 'default' => 'members'],
    'visibility_station' => ['label' => $t('station'), 'default' => 'members'],
    'visibility_antennas' => ['label' => $t('antennas'), 'default' => 'members'],
    'visibility_interests' => ['label' => $t('interests'), 'default' => 'members'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $allowedVisibilities = array_keys($visibilityOptions);
    $visibilityPayload = [];
    foreach (array_keys($visibilityFields) as $field) {
        $value = (string) ($_POST[$field] ?? 'members');
        $visibilityPayload[$field] = in_array($value, $allowedVisibilities, true) ? $value : 'members';
    }

    $photoPathStmt = db()->prepare('SELECT photo_path FROM members WHERE id = ? LIMIT 1');
    $photoPathStmt->execute([$memberId]);
    $existingPhotoPath = trim((string) ($photoPathStmt->fetchColumn() ?: ''));
    $newPhotoPath = $existingPhotoPath;
    $avatarStmt = db()->prepare('SELECT avatar_path FROM members WHERE id = ? LIMIT 1');
    $avatarStmt->execute([$memberId]);
    $newAvatarPath = trim((string) ($avatarStmt->fetchColumn() ?: ''));
    if (isset($_FILES['photo']) && is_array($_FILES['photo']) && (int) ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $savedFilename = secure_move_uploaded_file(
            $_FILES['photo'],
            dirname(__DIR__) . '/storage/uploads/members',
            'member_' . $memberId,
            ['jpg', 'jpeg', 'png', 'webp'],
            6 * 1024 * 1024
        );
        $newPhotoPath = 'storage/uploads/members/' . $savedFilename;
        $generatedAvatarPath = generate_member_avatar_from_photo($newPhotoPath, $memberId);
        if ($generatedAvatarPath !== null && $generatedAvatarPath !== '') {
            $newAvatarPath = $generatedAvatarPath;
        }
    }

    $stmt = db()->prepare(
        'UPDATE members
         SET photo_path = ?,
             avatar_path = ?,
             visibility_photo = ?,
             visibility_full_name = ?,
             visibility_email = ?,
             visibility_phone = ?,
             visibility_country = ?,
             visibility_qth = ?,
             visibility_locator = ?,
             visibility_bio = ?,
             visibility_licence_class = ?,
             visibility_qsl = ?,
             visibility_qrz = ?,
             visibility_uba = ?,
             visibility_favourite_bands = ?,
             visibility_favourite_modes = ?,
             visibility_station = ?,
             visibility_antennas = ?,
             visibility_interests = ?
         WHERE id = ?'
    );
    $stmt->execute([
        $newPhotoPath,
        $newAvatarPath,
        $visibilityPayload['visibility_photo'],
        $visibilityPayload['visibility_full_name'],
        $visibilityPayload['visibility_email'],
        $visibilityPayload['visibility_phone'],
        $visibilityPayload['visibility_country'],
        $visibilityPayload['visibility_qth'],
        $visibilityPayload['visibility_locator'],
        $visibilityPayload['visibility_bio'],
        $visibilityPayload['visibility_licence_class'],
        $visibilityPayload['visibility_qsl'],
        $visibilityPayload['visibility_qrz'],
        $visibilityPayload['visibility_uba'],
        $visibilityPayload['visibility_favourite_bands'],
        $visibilityPayload['visibility_favourite_modes'],
        $visibilityPayload['visibility_station'],
        $visibilityPayload['visibility_antennas'],
        $visibilityPayload['visibility_interests'],
        $memberId,
    ]);

    set_flash('success', $t('saved'));
    redirect('gdpr');
}

$stmt = db()->prepare(
    'SELECT callsign, full_name, email, phone, qth, licence_class, favourite_bands, station_equipment, photo_path, avatar_path,
            visibility_photo, visibility_full_name, visibility_email, visibility_phone, visibility_country, visibility_qth, visibility_locator, visibility_bio,
            visibility_licence_class, visibility_qsl, visibility_qrz, visibility_uba, visibility_favourite_bands, visibility_favourite_modes,
            visibility_station, visibility_antennas, visibility_interests
     FROM members
     WHERE id = ? LIMIT 1'
);
$stmt->execute([$memberId]);
$member = $stmt->fetch() ?: [];

ob_start();
?>
<div class="stack">
<div class="card">
    <h1>Vie privée</h1>
    <?php $avatarSrc = member_avatar_src($member); ?>
    <p><img src="<?= e($avatarSrc) ?>" alt="<?= e($t('avatar_alt')) ?>" style="max-width:180px;border-radius:12px;"></p>
    <p><strong><?= e($t('callsign')) ?> :</strong> <?= e((string) ($member['callsign'] ?? '')) ?></p>
    <p><strong><?= e($t('name')) ?> :</strong> <?= e((string) ($member['full_name'] ?? '')) ?></p>
    <p><strong><?= e($t('email')) ?> :</strong> <?= e((string) ($member['email'] ?? '')) ?></p>
</div>

<section class="card" id="privacy">
    <h2><?= e($t('directory_visibility')) ?></h2>
    <p class="help"><?= e($t('visibility_help')) ?></p>
    <form method="post" class="stack" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <label>
            <?= e($t('change_photo')) ?>
            <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
            <small class="help"><?= e($t('photo_help')) ?></small>
        </label>

        <div class="profile-visibility-grid">
            <?php foreach ($visibilityOptions as $visibilityValue => $visibilityLabel): ?>
                <section class="profile-visibility-panel">
                    <h3><?= e($visibilityLabel) ?></h3>
                    <div class="profile-visibility-options">
                        <?php foreach ($visibilityFields as $fieldName => $fieldMeta): ?>
                            <?php $currentValue = (string) ($member[$fieldName] ?? (string) $fieldMeta['default']); ?>
                            <label class="profile-visibility-option">
                                <input type="radio" name="<?= e($fieldName) ?>" value="<?= e($visibilityValue) ?>" <?= $currentValue === $visibilityValue ? 'checked' : '' ?>>
                                <span><?= e((string) $fieldMeta['label']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>

        <button type="submit" class="button"><?= e($t('save')) ?></button>
    </form>
</section>
</div>
<?php

echo render_layout((string) ob_get_clean(), 'Vie privée');
