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
    'SELECT callsign, full_name, email, phone, country, qth, locator, bio, licence_class, qsl_via, qrz_url, is_uba_member, favourite_bands, favourite_modes, station_equipment, antennas, interests, photo_path, avatar_path,
            visibility_photo, visibility_full_name, visibility_email, visibility_phone, visibility_country, visibility_qth, visibility_locator, visibility_bio,
            visibility_licence_class, visibility_qsl, visibility_qrz, visibility_uba, visibility_favourite_bands, visibility_favourite_modes,
            visibility_station, visibility_antennas, visibility_interests
     FROM members
     WHERE id = ? LIMIT 1'
);
$stmt->execute([$memberId]);
$member = $stmt->fetch() ?: [];

$visibilityAllows = static function (string $viewer, string $visibility): bool {
    if ($viewer === 'private') {
        return true;
    }
    if ($viewer === 'members') {
        return in_array($visibility, ['public', 'members'], true);
    }
    return $visibility === 'public';
};
$profilePreviewFields = [
    'full_name' => ['label' => $t('full_name'), 'visibility' => 'visibility_full_name'],
    'email' => ['label' => $t('email'), 'visibility' => 'visibility_email'],
    'phone' => ['label' => $t('phone'), 'visibility' => 'visibility_phone'],
    'country' => ['label' => $t('country'), 'visibility' => 'visibility_country'],
    'qth' => ['label' => $t('qth'), 'visibility' => 'visibility_qth'],
    'locator' => ['label' => $t('grid'), 'visibility' => 'visibility_locator'],
    'bio' => ['label' => $t('bio'), 'visibility' => 'visibility_bio'],
    'licence_class' => ['label' => $t('licence'), 'visibility' => 'visibility_licence_class'],
    'qsl_via' => ['label' => $t('qsl_info'), 'visibility' => 'visibility_qsl'],
    'qrz_url' => ['label' => $t('qrz_url'), 'visibility' => 'visibility_qrz'],
    'is_uba_member' => ['label' => $t('uba_member'), 'visibility' => 'visibility_uba'],
    'favourite_bands' => ['label' => $t('bands'), 'visibility' => 'visibility_favourite_bands'],
    'favourite_modes' => ['label' => $t('favourite_modes'), 'visibility' => 'visibility_favourite_modes'],
    'station_equipment' => ['label' => $t('station'), 'visibility' => 'visibility_station'],
    'antennas' => ['label' => $t('antennas'), 'visibility' => 'visibility_antennas'],
    'interests' => ['label' => $t('interests'), 'visibility' => 'visibility_interests'],
];
$profileViews = [
    'public' => ['title' => 'Vue ' . strtolower($t('public'))],
    'members' => ['title' => 'Vue ' . strtolower($t('members'))],
    'private' => ['title' => 'Vue ' . strtolower($t('private'))],
];
$profilePreviewRows = [];
$profileAllPreviewRows = [];
foreach (array_keys($profileViews) as $viewer) {
    $profilePreviewRows[$viewer] = [];
    $profileAllPreviewRows[$viewer] = [];
    foreach ($profilePreviewFields as $fieldName => $fieldMeta) {
        $visibility = (string) ($member[(string) $fieldMeta['visibility']] ?? 'members');
        $value = trim((string) ($member[$fieldName] ?? ''));
        if ($fieldName === 'is_uba_member') {
            $value = (int) ($member[$fieldName] ?? 0) === 1 ? 'Oui' : '';
        }
        if ($value === '') {
            continue;
        }
        $previewRow = [
            'label' => (string) $fieldMeta['label'],
            'value' => $value,
            'visibility_field' => (string) $fieldMeta['visibility'],
            'visible' => $visibilityAllows($viewer, $visibility),
        ];
        $profileAllPreviewRows[$viewer][] = $previewRow;
        if ((bool) $previewRow['visible']) {
            $profilePreviewRows[$viewer][] = $previewRow;
        }
    }
}

ob_start();
?>
<div class="gdpr-page stack">
<div class="card gdpr-hero">
    <?php $avatarSrc = member_avatar_src($member); ?>
    <div class="gdpr-profile-views">
        <?php foreach ($profileViews as $viewer => $view): ?>
            <?php $canSeePhoto = $visibilityAllows((string) $viewer, (string) ($member['visibility_photo'] ?? 'members')); ?>
            <section class="gdpr-profile-view">
                <header>
                    <img class="gdpr-avatar" src="<?= e($avatarSrc) ?>" alt="<?= e($t('avatar_alt')) ?>" data-gdpr-photo data-gdpr-visibility-field="visibility_photo" <?= $canSeePhoto ? '' : 'hidden' ?>>
                    <div>
                        <h2><?= e((string) $view['title']) ?></h2>
                        <p class="gdpr-callsign"><?= e((string) ($member['callsign'] ?? '')) ?></p>
                    </div>
                </header>
                <p class="help" data-gdpr-empty <?= $profilePreviewRows[(string) $viewer] === [] ? '' : 'hidden' ?>>Aucune information visible.</p>
                <dl class="gdpr-profile-summary">
                    <?php foreach ($profileAllPreviewRows[(string) $viewer] as $previewRow): ?>
                        <div data-gdpr-preview-row data-gdpr-visibility-field="<?= e((string) $previewRow['visibility_field']) ?>" <?= (bool) $previewRow['visible'] ? '' : 'hidden' ?>>
                            <dt><?= e((string) $previewRow['label']) ?></dt>
                            <dd><?= e((string) $previewRow['value']) ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            </section>
        <?php endforeach; ?>
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
