<?php
declare(strict_types=1);

$members = [];
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
    $qthVisibility = $viewer !== null ? ['public', 'members'] : ['public'];

    $sql = 'SELECT callsign, full_name, qth, licence_class, favourite_bands, is_committee, committee_role, visibility_qth
        FROM members
        WHERE is_active = 1';
    $params = [];

    if ($search !== '') {
        $sql .= ' AND (callsign LIKE ? OR full_name LIKE ?)';
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
    }
    if ($licenceFilter !== '') {
        $sql .= ' AND licence_class = ?';
        $params[] = $licenceFilter;
    }

    $sql .= ' ORDER BY callsign ASC LIMIT 300';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $members = $stmt->fetchAll() ?: [];

    $countsStmt = db()->query(
        'SELECT COUNT(*) AS active_total, SUM(CASE WHEN UPPER(COALESCE(licence_class, "")) LIKE "%UBA%" THEN 1 ELSE 0 END) AS uba_total
        FROM members
        WHERE is_active = 1'
    );
    $countsRow = $countsStmt ? ($countsStmt->fetch() ?: []) : [];
    $activeMembersCount = (int) ($countsRow['active_total'] ?? 0);
    $ubaMembersCount = (int) ($countsRow['uba_total'] ?? 0);

    foreach ($members as &$member) {
        $visibility = (string) ($member['visibility_qth'] ?? 'private');
        if (!in_array($visibility, $qthVisibility, true)) {
            $member['qth'] = '';
        }
    }
    unset($member);

    $licenceRows = db()->query('SELECT licence_class, COUNT(*) AS total FROM members WHERE is_active = 1 AND licence_class IS NOT NULL AND licence_class <> "" GROUP BY licence_class ORDER BY licence_class ASC')->fetchAll() ?: [];
} else {
    $licenceRows = [];
}

ob_start();
?>
<section class="card">
    <h2>Statistiques membres</h2>
    <div class="directory-grid">
        <article class="directory-card">
            <h3><?= e((string) $activeMembersCount) ?></h3>
            <p>Membres actifs</p>
        </article>
        <article class="directory-card">
            <h3><?= e((string) $ubaMembersCount) ?></h3>
            <p>Membres UBA</p>
        </article>
    </div>
</section>

<section class="card">
    <h2>Membres actifs</h2>
    <?php if ($members === []): ?>
        <p>Aucun membre actif trouvé.</p>
    <?php else: ?>
        <div class="directory-grid">
            <?php foreach ($members as $member): ?>
                <article class="directory-card">
                    <h3><?= e((string) $member['callsign']) ?></h3>
                    <p><?= e((string) $member['full_name']) ?></p>
                    <?php if (trim((string) ($member['licence_class'] ?? '')) !== ''): ?>
                        <p class="help">Licence : <?= e((string) $member['licence_class']) ?></p>
                    <?php endif; ?>
                    <?php if (trim((string) ($member['qth'] ?? '')) !== ''): ?>
                        <p class="help">QTH : <?= e((string) $member['qth']) ?></p>
                    <?php endif; ?>
                    <?php if (trim((string) ($member['favourite_bands'] ?? '')) !== ''): ?>
                        <p class="help">Bandes : <?= e((string) $member['favourite_bands']) ?></p>
                    <?php endif; ?>
                    <?php if ((int) ($member['is_committee'] ?? 0) === 1): ?>
                        <span class="badge muted"><?= e((string) ($member['committee_role'] ?: 'Comité')) ?></span>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php

echo render_layout((string) ob_get_clean(), 'Annuaire');
