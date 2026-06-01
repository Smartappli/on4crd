<?php
declare(strict_types=1);

$members = [];
$locale = current_locale();
$t = i18n_domain_translator('directory', $locale);

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
    $allowedVisibilityLevels = ['public', 'members'];
    if ($viewer !== null && (int) ($viewer['is_committee'] ?? 0) === 1) {
        $allowedVisibilityLevels[] = 'private';
    }
    $visibilityPlaceholders = implode(',', array_fill(0, count($allowedVisibilityLevels), '?'));

    $sql = 'SELECT callsign, full_name, email, phone, country, qth, locator, licence_class, qrz_url, is_uba_member, uba_member_number, favourite_bands, favourite_modes, station_equipment, antennas, photo_path, avatar_path, is_committee, committee_role, visibility_photo, visibility_full_name, visibility_email, visibility_phone, visibility_country, visibility_qth, visibility_locator, visibility_licence_class, visibility_qrz, visibility_uba, visibility_favourite_bands, visibility_favourite_modes, visibility_station, visibility_antennas
        FROM members
        WHERE is_active = 1
          AND UPPER(callsign) <> "ON4CRD"';
    $params = [];

    if ($search !== '') {
        $sql .= ' AND (callsign LIKE ? OR (full_name LIKE ? AND visibility_full_name IN (' . $visibilityPlaceholders . ')))';
        $like = '%' . $search . '%';
        $params[] = $like;
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

    $countsStmt = db()->prepare(
        'SELECT COUNT(*) AS active_total,
                SUM(CASE WHEN is_uba_member = 1 AND visibility_uba IN (' . $visibilityPlaceholders . ') THEN 1 ELSE 0 END) AS uba_total
         FROM members
         WHERE is_active = 1
           AND UPPER(callsign) <> "ON4CRD"
           AND (
               visibility_photo IN (' . $visibilityPlaceholders . ')
               OR visibility_full_name IN (' . $visibilityPlaceholders . ')
               OR visibility_email IN (' . $visibilityPlaceholders . ')
               OR visibility_phone IN (' . $visibilityPlaceholders . ')
               OR visibility_country IN (' . $visibilityPlaceholders . ')
               OR visibility_qth IN (' . $visibilityPlaceholders . ')
               OR visibility_locator IN (' . $visibilityPlaceholders . ')
               OR visibility_licence_class IN (' . $visibilityPlaceholders . ')
               OR visibility_qrz IN (' . $visibilityPlaceholders . ')
               OR visibility_uba IN (' . $visibilityPlaceholders . ')
               OR visibility_favourite_bands IN (' . $visibilityPlaceholders . ')
               OR visibility_favourite_modes IN (' . $visibilityPlaceholders . ')
               OR visibility_station IN (' . $visibilityPlaceholders . ')
               OR visibility_antennas IN (' . $visibilityPlaceholders . ')
           )'
    );
    $countParams = [];
    foreach ($allowedVisibilityLevels as $visibilityLevel) {
        $countParams[] = $visibilityLevel;
    }
    for ($i = 0; $i < 14; $i++) {
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
        'full_name' => 'visibility_full_name',
        'email' => 'visibility_email',
        'phone' => 'visibility_phone',
        'country' => 'visibility_country',
        'qth' => 'visibility_qth',
        'locator' => 'visibility_locator',
        'licence_class' => 'visibility_licence_class',
        'qrz_url' => 'visibility_qrz',
        'is_uba_member' => 'visibility_uba',
        'uba_member_number' => 'visibility_uba',
        'favourite_bands' => 'visibility_favourite_bands',
        'favourite_modes' => 'visibility_favourite_modes',
        'station_equipment' => 'visibility_station',
        'antennas' => 'visibility_antennas',
    ];

    foreach ($members as $index => &$member) {
        foreach ($fieldVisibilityMap as $field => $visibilityField) {
            $visibility = (string) ($member[$visibilityField] ?? 'private');
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

    $name = trim((string) ($member['full_name'] ?? ''));
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
            <p class="directory-eyebrow"><?= e($t('club_numbers')) ?></p>
            <h1><?= e($t('members_title')) ?></h1>
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
                <p class="directory-eyebrow"><?= e($t('results')) ?></p>
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
                $fullName = trim((string) ($member['full_name'] ?? ''));
                $licenceClass = trim((string) ($member['licence_class'] ?? ''));
                $email = trim((string) ($member['email'] ?? ''));
                $phone = trim((string) ($member['phone'] ?? ''));
                $country = trim((string) ($member['country'] ?? ''));
                $qth = trim((string) ($member['qth'] ?? ''));
                $grid = trim((string) ($member['locator'] ?? ''));
                $qrzUrl = trim((string) ($member['qrz_url'] ?? ''));
                $bands = trim((string) ($member['favourite_bands'] ?? ''));
                $modes = trim((string) ($member['favourite_modes'] ?? ''));
                $station = trim((string) ($member['station_equipment'] ?? ''));
                $antennas = trim((string) ($member['antennas'] ?? ''));
                $memberAvatarSrc = member_avatar_src($member);
                $bandList = array_values(array_filter(array_map('trim', preg_split('/[,;\/]+/', $bands) ?: [])));
                $modeList = array_values(array_filter(array_map('trim', preg_split('/[,;\/]+/', $modes) ?: [])));
                ?>
                <article class="directory-member-card">
                    <header class="directory-member-header">
                        <div class="directory-avatar-wrap">
                            <img class="directory-avatar" src="<?= e($memberAvatarSrc) ?>" alt="<?= e($t('avatar_of')) ?> <?= e($callsign) ?>">
                            <span aria-hidden="true"><?= e($memberInitials($member)) ?></span>
                        </div>
                        <div class="directory-member-title">
                            <h3><?= e($callsign) ?></h3>
                            <?php if ($fullName !== ''): ?>
                                <p><?= e($fullName) ?></p>
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
                            <span><?= e($country) ?></span>
                        <?php endif; ?>
                        <?php if ($grid !== ''): ?>
                            <span><?= e($t('grid')) ?> <?= e($grid) ?></span>
                        <?php endif; ?>
                        <?php if ((int) ($member['is_uba_member'] ?? 0) === 1): ?>
                            <span><?= e($t('uba_member')) ?><?= trim((string) ($member['uba_member_number'] ?? '')) !== '' ? ' ' . e((string) $member['uba_member_number']) : '' ?></span>
                        <?php endif; ?>
                    </div>

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

                    <?php if ($email !== '' || $phone !== '' || $qrzUrl !== ''): ?>
                        <div class="directory-contact-row">
                            <?php if ($email !== ''): ?>
                                <a class="button small secondary" href="mailto:<?= e($email) ?>"><?= e($t('email')) ?></a>
                            <?php endif; ?>
                            <?php if ($phone !== ''): ?>
                                <a class="button small secondary" href="tel:<?= e(preg_replace('/\s+/', '', $phone) ?? $phone) ?>"><?= e($t('phone')) ?></a>
                            <?php endif; ?>
                            <?php if ($qrzUrl !== ''): ?>
                                <a class="button small secondary" href="<?= e($qrzUrl) ?>" target="_blank" rel="noopener"><?= e($t('qrz')) ?></a>
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
