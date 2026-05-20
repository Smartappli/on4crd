<?php
declare(strict_types=1);

$members = [];
$locale = current_locale();
$i18n = [
    'fr' => ['club_numbers' => 'Le club en chiffres', 'member_list' => 'Liste des membres', 'uba_members' => 'Membres UBA', 'members_title' => 'Liste des membres', 'none' => 'Aucun membre trouvé.', 'avatar_of' => 'Avatar de', 'licence' => 'Licence', 'email' => 'Email', 'phone' => 'Téléphone', 'bands' => 'Bandes', 'station' => 'Station', 'committee' => 'Comité', 'layout_title' => 'Annuaire'],
    'en' => ['club_numbers' => 'Club in numbers', 'member_list' => 'Member directory', 'uba_members' => 'UBA members', 'members_title' => 'Member directory', 'none' => 'No member found.', 'avatar_of' => 'Avatar of', 'licence' => 'Licence', 'email' => 'Email', 'phone' => 'Phone', 'bands' => 'Bands', 'station' => 'Station', 'committee' => 'Committee', 'layout_title' => 'Directory'],
    'de' => ['club_numbers' => 'Der Club in Zahlen', 'member_list' => 'Mitgliederliste', 'uba_members' => 'UBA-Mitglieder', 'members_title' => 'Mitgliederliste', 'none' => 'Keine Mitglieder gefunden.', 'avatar_of' => 'Avatar von', 'licence' => 'Lizenz', 'email' => 'E-Mail', 'phone' => 'Telefon', 'bands' => 'Bänder', 'station' => 'Station', 'committee' => 'Komitee', 'layout_title' => 'Verzeichnis'],
    'es' => ['club_numbers' => 'El club en cifras', 'member_list' => 'Lista de miembros', 'uba_members' => 'Miembros UBA', 'members_title' => 'Lista de miembros', 'none' => 'No se encontró ningún miembro.', 'avatar_of' => 'Avatar de', 'licence' => 'Licencia', 'email' => 'Correo electrónico', 'phone' => 'Teléfono', 'bands' => 'Bandas', 'station' => 'Estación', 'committee' => 'Comisión', 'layout_title' => 'Directorio'],
    'it' => ['club_numbers' => 'Il club in cifre', 'member_list' => 'Elenco membri', 'uba_members' => 'Membri UBA', 'members_title' => 'Elenco membri', 'none' => 'Nessun membro trovato.', 'avatar_of' => 'Avatar di', 'licence' => 'Licenza', 'email' => 'Email', 'phone' => 'Telefono', 'bands' => 'Bande', 'station' => 'Stazione', 'committee' => 'Comitato', 'layout_title' => 'Rubrica'],
    'pt' => ['club_numbers' => 'O clube em números', 'member_list' => 'Lista de membros', 'uba_members' => 'Membros UBA', 'members_title' => 'Lista de membros', 'none' => 'Nenhum membro encontrado.', 'avatar_of' => 'Avatar de', 'licence' => 'Licença', 'email' => 'Email', 'phone' => 'Telefone', 'bands' => 'Bandas', 'station' => 'Estação', 'committee' => 'Comissão', 'layout_title' => 'Diretório'],
    'nl' => ['club_numbers' => 'De club in cijfers', 'member_list' => 'Ledenlijst', 'uba_members' => 'UBA-leden', 'members_title' => 'Ledenlijst', 'none' => 'Geen leden gevonden.', 'avatar_of' => 'Avatar van', 'licence' => 'Licentie', 'email' => 'E-mail', 'phone' => 'Telefoon', 'bands' => 'Banden', 'station' => 'Station', 'committee' => 'Commissie', 'layout_title' => 'Ledenlijst'],

    'ar' => ['club_numbers' => 'النادي بالأرقام', 'member_list' => 'دليل الأعضاء', 'uba_members' => 'أعضاء UBA', 'members_title' => 'دليل الأعضاء', 'none' => 'لم يتم العثور على أي عضو.', 'avatar_of' => 'الصورة الرمزية لـ', 'licence' => 'الرخصة', 'email' => 'البريد الإلكتروني', 'phone' => 'الهاتف', 'bands' => 'النطاقات', 'station' => 'المحطة', 'committee' => 'اللجنة', 'layout_title' => 'الدليل'],
    'bn' => ['club_numbers' => 'সংখ্যায় ক্লাব', 'member_list' => 'সদস্য নির্দেশিকা', 'uba_members' => 'UBA সদস্য', 'members_title' => 'সদস্য নির্দেশিকা', 'none' => 'কোনও সদস্য পাওয়া যায়নি।', 'avatar_of' => 'এর অবতার', 'licence' => 'লাইসেন্স', 'email' => 'ইমেইল', 'phone' => 'ফোন', 'bands' => 'ব্যান্ড', 'station' => 'স্টেশন', 'committee' => 'কমিটি', 'layout_title' => 'নির্দেশিকা'],
    'hi' => ['club_numbers' => 'आँकड़ों में क्लब', 'member_list' => 'सदस्य निर्देशिका', 'uba_members' => 'UBA सदस्य', 'members_title' => 'सदस्य निर्देशिका', 'none' => 'कोई सदस्य नहीं मिला।', 'avatar_of' => 'का अवतार', 'licence' => 'लाइसेंस', 'email' => 'ईमेल', 'phone' => 'फ़ोन', 'bands' => 'बैंड', 'station' => 'स्टेशन', 'committee' => 'समिति', 'layout_title' => 'निर्देशिका'],
    'id' => ['club_numbers' => 'Klub dalam angka', 'member_list' => 'Direktori anggota', 'uba_members' => 'Anggota UBA', 'members_title' => 'Direktori anggota', 'none' => 'Tidak ada anggota ditemukan.', 'avatar_of' => 'Avatar', 'licence' => 'Lisensi', 'email' => 'Email', 'phone' => 'Telepon', 'bands' => 'Band', 'station' => 'Stasiun', 'committee' => 'Komite', 'layout_title' => 'Direktori'],
    'ja' => ['club_numbers' => '数字で見るクラブ', 'member_list' => 'メンバーディレクトリ', 'uba_members' => 'UBAメンバー', 'members_title' => 'メンバーディレクトリ', 'none' => 'メンバーが見つかりません。', 'avatar_of' => 'のアバター', 'licence' => 'ライセンス', 'email' => 'メール', 'phone' => '電話', 'bands' => 'バンド', 'station' => '無線局', 'committee' => '委員会', 'layout_title' => 'ディレクトリ'],
    'ru' => ['club_numbers' => 'Клуб в цифрах', 'member_list' => 'Каталог участников', 'uba_members' => 'Участники UBA', 'members_title' => 'Каталог участников', 'none' => 'Участники не найдены.', 'avatar_of' => 'Аватар', 'licence' => 'Лицензия', 'email' => 'Email', 'phone' => 'Телефон', 'bands' => 'Диапазоны', 'station' => 'Станция', 'committee' => 'Комитет', 'layout_title' => 'Каталог'],
    'zh' => ['club_numbers' => '俱乐部数据', 'member_list' => '会员名录', 'uba_members' => 'UBA 会员', 'members_title' => '会员名录', 'none' => '未找到会员。', 'avatar_of' => '的头像', 'licence' => '执照', 'email' => '电子邮箱', 'phone' => '电话', 'bands' => '波段', 'station' => '电台', 'committee' => '委员会', 'layout_title' => '名录'],
];
$i18n = i18n_expand_supported_locales($i18n);
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
