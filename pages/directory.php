<?php
declare(strict_types=1);

$members = [];
$locale = current_locale();
$i18n = [
    'fr' => ['club_numbers' => 'Le club en chiffres', 'member_list' => 'Liste des membres', 'uba_members' => 'Membres UBA', 'members_title' => 'Liste des membres', 'none' => 'Aucun membre trouvé.', 'avatar_of' => 'Avatar de', 'licence' => 'Licence', 'email' => 'Email', 'phone' => 'Téléphone', 'bands' => 'Bandes', 'station' => 'Station', 'committee' => 'Comité', 'layout_title' => 'Annuaire'],
    'en' => ['club_numbers' => 'Club in numbers', 'member_list' => 'Member directory', 'uba_members' => 'UBA members', 'members_title' => 'Member directory', 'none' => 'No member found.', 'avatar_of' => 'Avatar of', 'licence' => 'Licence', 'email' => 'Email', 'phone' => 'Phone', 'bands' => 'Bands', 'station' => 'Station', 'committee' => 'Committee', 'layout_title' => 'Directory'],
    'de' => ['club_numbers' => 'Der Club in Zahlen', 'member_list' => 'Mitgliederliste', 'uba_members' => 'UBA-Mitglieder', 'members_title' => 'Mitgliederliste', 'none' => 'Keine Mitglieder gefunden.', 'avatar_of' => 'Avatar von', 'licence' => 'Lizenz', 'email' => 'E-Mail', 'phone' => 'Telefon', 'bands' => 'Bänder', 'station' => 'Station', 'committee' => 'Komitee', 'layout_title' => 'Verzeichnis'],
    'nl' => ['club_numbers' => 'De club in cijfers', 'member_list' => 'Ledenlijst', 'uba_members' => 'UBA-leden', 'members_title' => 'Ledenlijst', 'none' => 'Geen leden gevonden.', 'avatar_of' => 'Avatar van', 'licence' => 'Licentie', 'email' => 'E-mail', 'phone' => 'Telefoon', 'bands' => 'Banden', 'station' => 'Station', 'committee' => 'Comité', 'layout_title' => 'Ledenlijst'],
];
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};

$activeMembersCount = 0;
$ubaMembersCount = 0;
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
    $allowedVisibilityLevels = ['public'];
    if ($viewer !== null) {
        $allowedVisibilityLevels[] = 'members';
        if ((int) ($viewer['is_committee'] ?? 0) === 1) {
            $allowedVisibilityLevels[] = 'private';
        }
    }
    $visibilityPlaceholders = implode(',', array_fill(0, count($allowedVisibilityLevels), '?'));

    $sql = 'SELECT callsign, full_name, email, phone, qth, licence_class, favourite_bands, station_equipment, photo_path, avatar_path, is_committee, committee_role, visibility_photo, visibility_full_name, visibility_email, visibility_phone, visibility_qth, visibility_licence_class, visibility_favourite_bands, visibility_station
        FROM members
        WHERE is_active = 1';
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
                SUM(CASE WHEN UPPER(COALESCE(licence_class, "")) LIKE "%UBA%" AND visibility_licence_class IN (' . $visibilityPlaceholders . ') THEN 1 ELSE 0 END) AS uba_total
         FROM members
         WHERE is_active = 1
           AND (
               visibility_photo IN (' . $visibilityPlaceholders . ')
               OR visibility_full_name IN (' . $visibilityPlaceholders . ')
               OR visibility_email IN (' . $visibilityPlaceholders . ')
               OR visibility_phone IN (' . $visibilityPlaceholders . ')
               OR visibility_qth IN (' . $visibilityPlaceholders . ')
               OR visibility_licence_class IN (' . $visibilityPlaceholders . ')
               OR visibility_favourite_bands IN (' . $visibilityPlaceholders . ')
               OR visibility_station IN (' . $visibilityPlaceholders . ')
           )'
    );
    $countParams = [];
    foreach ($allowedVisibilityLevels as $visibilityLevel) {
        $countParams[] = $visibilityLevel;
    }
    for ($i = 0; $i < 8; $i++) {
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
        'qth' => 'visibility_qth',
        'licence_class' => 'visibility_licence_class',
        'favourite_bands' => 'visibility_favourite_bands',
        'station_equipment' => 'visibility_station',
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

    $licenceRows = db()->query('SELECT licence_class, COUNT(*) AS total FROM members WHERE is_active = 1 AND licence_class IS NOT NULL AND licence_class <> "" GROUP BY licence_class ORDER BY licence_class ASC')->fetchAll() ?: [];
} else {
    $licenceRows = [];
}

ob_start();
?>
<section class="card">
    <h2 class="text-xl font-bold text-slate-900"><?= e($t('club_numbers')) ?></h2>
    <div class="directory-grid">
        <article class="directory-card">
            <h3><?= e((string) $activeMembersCount) ?></h3>
            <p><?= e($t('member_list')) ?></p>
        </article>
        <article class="directory-card">
            <h3><?= e((string) $ubaMembersCount) ?></h3>
            <p><?= e($t('uba_members')) ?></p>
        </article>
    </div>
</section>

<section class="card mt-4">
    <h2><?= e($t('members_title')) ?></h2>
    <?php if ($members === []): ?>
        <p><?= e($t('none')) ?></p>
    <?php else: ?>
        <div class="directory-grid">
            <?php foreach ($members as $member): ?>
                <article class="directory-card">
                    <h3><?= e((string) $member['callsign']) ?></h3>
                    <?php $memberAvatarSrc = member_avatar_src($member); ?>
                    <p><img src="<?= e($memberAvatarSrc) ?>" alt="<?= e($t('avatar_of')) ?> <?= e((string) $member['callsign']) ?>" style="width:96px;height:96px;object-fit:cover;border-radius:999px;"></p>
                    <?php if (trim((string) ($member['full_name'] ?? '')) !== ''): ?>
                        <p><?= e((string) $member['full_name']) ?></p>
                    <?php endif; ?>
                    <?php if (trim((string) ($member['licence_class'] ?? '')) !== ''): ?>
                        <p class="help"><?= e($t('licence')) ?> : <?= e((string) $member['licence_class']) ?></p>
                    <?php endif; ?>
                    <?php if (trim((string) ($member['email'] ?? '')) !== ''): ?>
                        <p class="help"><?= e($t('email')) ?> : <?= e((string) $member['email']) ?></p>
                    <?php endif; ?>
                    <?php if (trim((string) ($member['phone'] ?? '')) !== ''): ?>
                        <p class="help"><?= e($t('phone')) ?> : <?= e((string) $member['phone']) ?></p>
                    <?php endif; ?>
                    <?php if (trim((string) ($member['qth'] ?? '')) !== ''): ?>
                        <p class="help">QTH : <?= e((string) $member['qth']) ?></p>
                    <?php endif; ?>
                    <?php if (trim((string) ($member['favourite_bands'] ?? '')) !== ''): ?>
                        <p class="help"><?= e($t('bands')) ?> : <?= e((string) $member['favourite_bands']) ?></p>
                    <?php endif; ?>
                    <?php if (trim((string) ($member['station_equipment'] ?? '')) !== ''): ?>
                        <p class="help"><?= e($t('station')) ?> : <?= e((string) $member['station_equipment']) ?></p>
                    <?php endif; ?>
                    <?php if ((int) ($member['is_committee'] ?? 0) === 1): ?>
                        <span class="badge muted"><?= e((string) ($member['committee_role'] ?: $t('committee'))) ?></span>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php

echo render_layout((string) ob_get_clean(), $t('layout_title'));
