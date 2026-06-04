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
$visibilityFields = array_filter(
    member_profile_visibility_fields($t),
    static fn(array $fieldMeta, string $fieldName): bool => table_has_column('members', $fieldName),
    ARRAY_FILTER_USE_BOTH
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $allowedVisibilities = array_keys($visibilityOptions);
    $visibilityPayload = [];
    foreach ($visibilityFields as $field => $fieldMeta) {
        $defaultVisibility = (string) ($fieldMeta['default'] ?? 'members');
        $value = (string) ($_POST[$field] ?? $defaultVisibility);
        $visibilityPayload[$field] = in_array($value, $allowedVisibilities, true) ? $value : $defaultVisibility;
    }

    $assignments = implode(', ', array_map(
        static fn(string $field): string => $field . ' = ?',
        array_keys($visibilityFields)
    ));
    $stmt = db()->prepare('UPDATE members SET ' . $assignments . ' WHERE id = ?');
    $stmt->execute([...array_values($visibilityPayload), $memberId]);

    set_flash('success', $t('saved'));
    redirect('gdpr');
}

$stmt = db()->prepare('SELECT ' . member_profile_select_columns_sql() . ' FROM members WHERE id = ? LIMIT 1');
$stmt->execute([$memberId]);
$member = $stmt->fetch() ?: [];
$member = member_backfill_missing_qrz_url($memberId, is_array($member) ? $member : []);
$member = member_with_name_parts($member);

$profileViews = [
    'public' => ['title' => 'Vue ' . strtolower($t('public'))],
    'members' => ['title' => 'Vue ' . strtolower($t('members'))],
    'private' => ['title' => 'Vue ' . strtolower($t('private'))],
];
$profilePreviewRows = [];
$profileAllPreviewRows = [];
foreach (array_keys($profileViews) as $viewer) {
    $profileAllPreviewRows[$viewer] = member_profile_preview_rows($member, (string) $viewer, $t, true);
    $profilePreviewRows[$viewer] = array_values(array_filter(
        $profileAllPreviewRows[$viewer],
        static fn(array $previewRow): bool => (bool) $previewRow['visible']
    ));
}

ob_start();
?>
<div class="gdpr-page stack">
<div class="card gdpr-hero">
    <?php $avatarSrc = member_avatar_src($member); ?>
    <div class="gdpr-profile-views">
        <?php foreach ($profileViews as $viewer => $view): ?>
            <?php $canSeePhoto = member_profile_visibility_allows((string) $viewer, (string) ($member['visibility_photo'] ?? 'members')); ?>
            <section class="gdpr-profile-view" data-gdpr-view="<?= e((string) $viewer) ?>">
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
                            <dd><?= (string) $previewRow['html'] ?></dd>
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
