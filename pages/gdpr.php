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

    $stmt = db()->prepare(
        'UPDATE members
         SET visibility_photo = ?,
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
<div class="gdpr-page stack">
<div class="card gdpr-hero">
    <div class="gdpr-identity">
    <?php $avatarSrc = member_avatar_src($member); ?>
        <img class="gdpr-avatar" src="<?= e($avatarSrc) ?>" alt="<?= e($t('avatar_alt')) ?>">
        <div>
            <h1>Vie privée</h1>
            <dl class="gdpr-profile-summary">
                <div><dt><?= e($t('callsign')) ?></dt><dd><?= e((string) ($member['callsign'] ?? '')) ?></dd></div>
                <div><dt><?= e($t('name')) ?></dt><dd><?= e((string) ($member['full_name'] ?? '')) ?></dd></div>
                <div><dt><?= e($t('email')) ?></dt><dd><?= e((string) ($member['email'] ?? '')) ?></dd></div>
            </dl>
        </div>
    </div>
</div>

<section class="card gdpr-privacy-card" id="privacy">
    <div class="gdpr-section-heading">
        <div>
            <h2><?= e($t('directory_visibility')) ?></h2>
            <p class="help"><?= e($t('visibility_help')) ?></p>
        </div>
    </div>
    <form method="post" class="stack">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <div class="gdpr-visibility-table" role="table" aria-label="<?= e($t('directory_visibility')) ?>">
            <div class="gdpr-visibility-header" role="row">
                <span role="columnheader"><?= e($t('profile_settings')) ?></span>
                <?php foreach ($visibilityOptions as $visibilityLabel): ?>
                    <span role="columnheader"><?= e($visibilityLabel) ?></span>
                <?php endforeach; ?>
            </div>
            <?php foreach ($visibilityFields as $fieldName => $fieldMeta): ?>
                <?php $currentValue = (string) ($member[$fieldName] ?? (string) $fieldMeta['default']); ?>
                <div class="gdpr-visibility-row" role="row">
                    <span class="gdpr-field-label" role="cell"><?= e((string) $fieldMeta['label']) ?></span>
                    <?php foreach ($visibilityOptions as $visibilityValue => $visibilityLabel): ?>
                        <label class="gdpr-choice" role="cell">
                            <input type="radio" name="<?= e($fieldName) ?>" value="<?= e($visibilityValue) ?>" <?= $currentValue === $visibilityValue ? 'checked' : '' ?>>
                            <span><?= e($visibilityLabel) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="gdpr-actions">
            <button type="submit" class="button"><?= e($t('save')) ?></button>
        </div>
    </form>
</section>
</div>
<?php

echo render_layout((string) ob_get_clean(), 'Vie privée');
