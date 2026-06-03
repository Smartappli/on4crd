<?php
declare(strict_types=1);

$members = [];
$locale = current_locale();
$t = i18n_domain_translator('directory', $locale);
$profileT = i18n_domain_translator('profile', $locale);

$activeMembersCount = 0;
$ubaMembersCount = 0;
$committeeMembersCount = 0;
$search = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($search) > 100) {
    $search = mb_substr($search, 0, 100);
}

$licenceFilter = trim((string) ($_GET['licence'] ?? ''));
if (mb_strlen($licenceFilter) > 64) {
    $licenceFilter = '';
}

if (table_exists('members')) {
    $viewer = current_user();
    $allowedVisibilityLevels = member_profile_allowed_visibility_levels(is_array($viewer) ? $viewer : null);
    $visibilityPlaceholders = implode(',', array_fill(0, count($allowedVisibilityLevels), '?'));

    $sql = 'SELECT ' . member_profile_select_columns_sql() . ', is_committee, committee_role
        FROM members
        WHERE is_active = 1
          AND UPPER(callsign) <> "ON4CRD"';
    $params = [];

    if ($search !== '') {
        $sql .= ' AND (callsign LIKE ?
            OR (first_name LIKE ? AND visibility_first_name IN (' . $visibilityPlaceholders . '))
            OR (last_name LIKE ? AND visibility_last_name IN (' . $visibilityPlaceholders . '))
            OR (address LIKE ? AND visibility_address IN (' . $visibilityPlaceholders . '))
            OR (postal_code LIKE ? AND visibility_postal_code IN (' . $visibilityPlaceholders . ')))';
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
        foreach ($allowedVisibilityLevels as $visibilityLevel) {
            $params[] = $visibilityLevel;
        }
        $params[] = $like;
        foreach ($allowedVisibilityLevels as $visibilityLevel) {
            $params[] = $visibilityLevel;
        }
        $params[] = $like;
        foreach ($allowedVisibilityLevels as $visibilityLevel) {
            $params[] = $visibilityLevel;
        }
        $params[] = $like;
        foreach ($allowedVisibilityLevels as $visibilityLevel) {
            $params[] = $visibilityLevel;
        }
    }
    if ($licenceFilter !== '') {
        $sql .= ' AND licence_class = ? AND visibility_licence_class IN (' . $visibilityPlaceholders . ')';
        $params[] = $licenceFilter;
        foreach ($allowedVisibilityLevels as $visibilityLevel) {
            $params[] = $visibilityLevel;
        }
    }

    $sql .= ' ORDER BY callsign ASC LIMIT 300';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $members = $stmt->fetchAll() ?: [];

    $directoryVisibilityFieldMeta = member_profile_visibility_fields($profileT);
    $directoryVisibilityFields = array_keys($directoryVisibilityFieldMeta);
    $visibleProfileConditions = implode(
        ' OR ',
        array_map(
            static fn(string $field): string => $field . ' IN (' . $visibilityPlaceholders . ')',
            $directoryVisibilityFields
        )
    );

    $countsStmt = db()->prepare(
        'SELECT COUNT(*) AS active_total,
                SUM(CASE WHEN is_uba_member = 1 AND visibility_uba IN (' . $visibilityPlaceholders . ') THEN 1 ELSE 0 END) AS uba_total
         FROM members
         WHERE is_active = 1
           AND UPPER(callsign) <> "ON4CRD"
           AND (' . $visibleProfileConditions . ')'
    );
    $countParams = [];
    foreach ($allowedVisibilityLevels as $visibilityLevel) {
        $countParams[] = $visibilityLevel;
    }
    foreach ($directoryVisibilityFields as $_visibilityField) {
        foreach ($allowedVisibilityLevels as $visibilityLevel) {
            $countParams[] = $visibilityLevel;
        }
    }
    $countsStmt->execute($countParams);
    $countsRow = $countsStmt->fetch() ?: [];
    $activeMembersCount = (int) ($countsRow['active_total'] ?? 0);
    $ubaMembersCount = (int) ($countsRow['uba_total'] ?? 0);

    $fieldVisibilityMap = [
        'photo_path' => 'visibility_photo',
        'avatar_path' => 'visibility_photo',
    ];
    foreach (member_profile_preview_fields($profileT) as $field => $fieldMeta) {
        $fieldVisibilityMap[$field] = (string) $fieldMeta['visibility'];
    }

    foreach ($members as $index => &$member) {
        $member = member_with_name_parts($member);
        foreach ($fieldVisibilityMap as $field => $visibilityField) {
            $visibility = (string) ($member[$visibilityField] ?? (string) ($directoryVisibilityFieldMeta[$visibilityField]['default'] ?? 'private'));
            if (!in_array($visibility, $allowedVisibilityLevels, true)) {
                $member[$field] = '';
            }
        }

        $hasVisibleData = false;
        foreach (array_keys($fieldVisibilityMap) as $field) {
            if ($field === 'is_uba_member' && (int) ($member[$field] ?? 0) !== 1) {
                continue;
            }
            if (trim((string) ($member[$field] ?? '')) !== '') {
                $hasVisibleData = true;
                break;
            }
        }
        if (!$hasVisibleData) {
            unset($members[$index]);
        }
    }
    unset($member);
    $members = array_values($members);

    $licenceRows = db()->query('SELECT licence_class, COUNT(*) AS total FROM members WHERE is_active = 1 AND UPPER(callsign) <> "ON4CRD" AND licence_class IS NOT NULL AND licence_class <> "" GROUP BY licence_class ORDER BY licence_class ASC')->fetchAll() ?: [];
} else {
    $licenceRows = [];
}

$visibleMembersCount = count($members);
foreach ($members as $member) {
    if ((int) ($member['is_committee'] ?? 0) === 1) {
        $committeeMembersCount++;
    }
}
$hasFilters = $search !== '' || $licenceFilter !== '';
$licenceOptions = [];
foreach ($licenceRows as $licenceRow) {
    $licenceValue = trim((string) ($licenceRow['licence_class'] ?? ''));
    if ($licenceValue === '') {
        continue;
    }
    $licenceOptions[] = [
        'value' => $licenceValue,
        'total' => (int) ($licenceRow['total'] ?? 0),
    ];
}

$memberInitials = static function (array $member): string {
    $callsign = trim((string) ($member['callsign'] ?? ''));
    if ($callsign !== '') {
        return mb_safe_strtoupper(mb_safe_substr($callsign, 0, 2));
    }

    $name = member_full_name_from_parts((string) ($member['first_name'] ?? ''), (string) ($member['last_name'] ?? ''));
    if ($name === '') {
        return 'OM';
    }

    $parts = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= mb_safe_strtoupper(mb_safe_substr((string) $part, 0, 1));
    }

    return $initials !== '' ? $initials : 'OM';
};

ob_start();
?>
<div class="directory-page">
    <section class="directory-hero">
        <div class="directory-hero-copy">
            <p class="directory-eyebrow directory-hero-title"><?= e($t('club_numbers')) ?></p>
            <h1 class="directory-hero-heading"><?= e($t('members_title')) ?></h1>
            <p class="directory-lead"><?= e($t('intro')) ?></p>
        </div>
        <div class="directory-hero-stats" aria-label="<?= e($t('club_numbers')) ?>">
            <article>
                <span><?= e($t('member_list')) ?></span>
                <strong><?= e((string) $activeMembersCount) ?></strong>
            </article>
            <article>
                <span><?= e($t('visible_members')) ?></span>
                <strong><?= e((string) $visibleMembersCount) ?></strong>
            </article>
            <article>
                <span><?= e($t('uba_members')) ?></span>
                <strong><?= e((string) $ubaMembersCount) ?></strong>
            </article>
            <article>
                <span><?= e($t('committee')) ?></span>
                <strong><?= e((string) $committeeMembersCount) ?></strong>
            </article>
        </div>
    </section>

    <section class="directory-toolbar">
        <form class="directory-search-panel" method="get" action="<?= e(base_url('index.php')) ?>">
            <input type="hidden" name="route" value="directory">
            <label>
                <span><?= e($t('search_label')) ?></span>
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= e($t('search_placeholder')) ?>">
            </label>
            <label>
                <span><?= e($t('licence_filter')) ?></span>
                <select name="licence">
                    <option value=""><?= e($t('all_licences')) ?></option>
                    <?php foreach ($licenceOptions as $licenceOption): ?>
                        <?php $licenceValue = (string) $licenceOption['value']; ?>
                        <option value="<?= e($licenceValue) ?>" <?= $licenceFilter === $licenceValue ? 'selected' : '' ?>>
                            <?= e($licenceValue) ?> (<?= (int) $licenceOption['total'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="directory-search-actions">
                <button type="submit" class="button"><?= e($t('apply_filters')) ?></button>
                <?php if ($hasFilters): ?>
                    <a class="button secondary" href="<?= e(route_url('directory')) ?>"><?= e($t('reset_filters')) ?></a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="directory-results">
        <div class="directory-results-header">
            <div>
                <h2><?= e($t('members_title')) ?></h2>
            </div>
            <?php if ($hasFilters): ?>
                <div class="directory-active-filters">
                    <?php if ($search !== ''): ?>
                        <span><?= e($t('search_label')) ?>: <?= e($search) ?></span>
                    <?php endif; ?>
                    <?php if ($licenceFilter !== ''): ?>
                        <span><?= e($t('licence')) ?>: <?= e($licenceFilter) ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php if ($members === []): ?>
        <div class="directory-empty">
            <h3><?= e($t('none')) ?></h3>
            <p><?= e($t('none_help')) ?></p>
            <?php if ($hasFilters): ?>
                <a class="button secondary" href="<?= e(route_url('directory')) ?>"><?= e($t('reset_filters')) ?></a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="directory-member-grid">
            <?php foreach ($members as $member): ?>
                <?php
                $callsign = trim((string) ($member['callsign'] ?? ''));
                $displayName = member_full_name_from_parts((string) ($member['first_name'] ?? ''), (string) ($member['last_name'] ?? ''));
                $licenceClass = trim((string) ($member['licence_class'] ?? ''));
                $email = trim((string) ($member['email'] ?? ''));
                $phone = trim((string) ($member['phone'] ?? ''));
                $country = trim((string) ($member['country'] ?? ''));
                $address = trim((string) ($member['address'] ?? ''));
                $postalCode = trim((string) ($member['postal_code'] ?? ''));
                $qth = trim((string) ($member['qth'] ?? ''));
                $grid = trim((string) ($member['locator'] ?? ''));
                $qrzUrl = trim((string) ($member['qrz_url'] ?? ''));
                $website = trim((string) ($member['website'] ?? ''));
                $safeQrzUrl = $qrzUrl !== '' ? sanitize_href_attribute($qrzUrl) : null;
                $safeWebsite = $website !== '' ? sanitize_href_attribute($website) : null;
                $bio = trim((string) ($member['bio'] ?? ''));
                $operatorSince = trim((string) ($member['operator_since'] ?? ''));
                $cqZone = trim((string) ($member['cq_zone'] ?? ''));
                $ituZone = trim((string) ($member['itu_zone'] ?? ''));
                $qslVia = trim((string) ($member['qsl_via'] ?? ''));
                $lotwUsername = trim((string) ($member['lotw_username'] ?? ''));
                $eqslUsername = trim((string) ($member['eqsl_username'] ?? ''));
                $maxPower = trim((string) ($member['max_power'] ?? ''));
                $interests = trim((string) ($member['interests'] ?? ''));
                $bands = trim((string) ($member['favourite_bands'] ?? ''));
                $modes = trim((string) ($member['favourite_modes'] ?? ''));
                $station = trim((string) ($member['station_equipment'] ?? ''));
                $antennas = trim((string) ($member['antennas'] ?? ''));
                $memberAvatarSrc = member_avatar_src($member);
                $bandList = array_values(array_filter(array_map('trim', preg_split('/[,;\/]+/', $bands) ?: [])));
                $modeList = array_values(array_filter(array_map('trim', preg_split('/[,;\/]+/', $modes) ?: [])));
                $detailRows = [];
                $addDetail = static function (string $label, string $value) use (&$detailRows): void {
                    $value = trim($value);
                    if ($value !== '') {
                        $detailRows[] = ['label' => $label, 'value' => $value];
                    }
                };
                $addDetail((string) $profileT('postal_code'), $postalCode);
                $addDetail((string) $profileT('address'), $address);
                $addDetail((string) $profileT('operator_since'), $operatorSince);
                $addDetail((string) $profileT('qsl_via'), $qslVia);
                $addDetail((string) $profileT('lotw_username'), $lotwUsername);
                $addDetail((string) $profileT('eqsl_username'), $eqslUsername);
                $addDetail((string) $profileT('max_power'), $maxPower);
                $addDetail((string) $profileT('interests'), $interests);
                ?>
                <article class="directory-member-card">
                    <header class="directory-member-header">
                        <div class="directory-avatar-wrap">
                            <img class="directory-avatar" src="<?= e($memberAvatarSrc) ?>" alt="<?= e($t('avatar_of')) ?> <?= e($callsign) ?>">
                            <span aria-hidden="true"><?= e($memberInitials($member)) ?></span>
                        </div>
                        <div class="directory-member-title">
                            <h3><?= e($callsign) ?></h3>
                            <?php if ($displayName !== ''): ?>
                                <p><?= e($displayName) ?></p>
                            <?php endif; ?>
                        </div>
                    </header>

                    <div class="directory-badges">
                        <?php if ($licenceClass !== ''): ?>
                            <span><?= e($licenceClass) ?></span>
                        <?php endif; ?>
                        <?php if ((int) ($member['is_committee'] ?? 0) === 1): ?>
                            <span><?= e((string) ($member['committee_role'] ?: $t('committee'))) ?></span>
                        <?php endif; ?>
                        <?php if ($qth !== ''): ?>
                            <span>QTH <?= e($qth) ?></span>
                        <?php endif; ?>
                        <?php if ($country !== ''): ?>
                            <span><?= member_country_html($country) ?></span>
                        <?php endif; ?>
                        <?php if ($grid !== ''): ?>
                            <span><?= e($t('grid')) ?> <?= e($grid) ?></span>
                        <?php endif; ?>
                        <?php if ($cqZone !== ''): ?>
                            <span><?= e($profileT('cq_zone')) ?> <?= e($cqZone) ?></span>
                        <?php endif; ?>
                        <?php if ($ituZone !== ''): ?>
                            <span><?= e($profileT('itu_zone')) ?> <?= e($ituZone) ?></span>
                        <?php endif; ?>
                        <?php if ($email !== ''): ?>
                            <span><?= e($profileT('email')) ?> <?= e($email) ?></span>
                        <?php endif; ?>
                        <?php if ($phone !== ''): ?>
                            <span><?= e($profileT('phone')) ?> <?= e($phone) ?></span>
                        <?php endif; ?>
                        <?php if ((int) ($member['is_uba_member'] ?? 0) === 1): ?>
                            <span><?= e($t('uba_member')) ?><?= trim((string) ($member['uba_member_number'] ?? '')) !== '' ? ' ' . e((string) $member['uba_member_number']) : '' ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($bio !== ''): ?>
                        <p class="directory-station"><?= e($bio) ?></p>
                    <?php endif; ?>

                    <?php if ($detailRows !== []): ?>
                        <dl class="directory-detail-list">
                            <?php foreach ($detailRows as $detailRow): ?>
                                <div>
                                    <dt><?= e((string) $detailRow['label']) ?></dt>
                                    <dd><?= e((string) $detailRow['value']) ?></dd>
                                </div>
                            <?php endforeach; ?>
                        </dl>
                    <?php endif; ?>

                    <?php if ($bandList !== []): ?>
                        <div class="directory-band-list" aria-label="<?= e($t('bands')) ?>">
                            <?php foreach (array_slice($bandList, 0, 5) as $band): ?>
                                <span><?= e($band) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($modeList !== []): ?>
                        <div class="directory-band-list" aria-label="<?= e($t('modes')) ?>">
                            <?php foreach (array_slice($modeList, 0, 5) as $mode): ?>
                                <span><?= e($mode) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($station !== ''): ?>
                        <p class="directory-station"><strong><?= e($t('station')) ?>:</strong> <?= e($station) ?></p>
                    <?php endif; ?>

                    <?php if ($antennas !== ''): ?>
                        <p class="directory-station"><strong><?= e($t('antennas')) ?>:</strong> <?= e($antennas) ?></p>
                    <?php endif; ?>

                    <?php if ($safeQrzUrl !== null || $safeWebsite !== null): ?>
                        <div class="directory-contact-row">
                            <?php if ($safeQrzUrl !== null): ?>
                                <a class="button small secondary" href="<?= e($safeQrzUrl) ?>" target="_blank" rel="noopener"><?= e($t('qrz')) ?></a>
                            <?php endif; ?>
                            <?php if ($safeWebsite !== null): ?>
                                <a class="button small secondary" href="<?= e($safeWebsite) ?>" target="_blank" rel="noopener"><?= e($profileT('website')) ?></a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    </section>
</div>
<?php

echo render_layout((string) ob_get_clean(), $t('layout_title'));
