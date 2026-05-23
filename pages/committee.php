<?php
declare(strict_types=1);

$members = committee_members();
$locale = current_locale();
$i18n = [
    'fr' => ['transparency' => 'Transparence', 'member_welcome' => 'Accueil des membres', 'contact' => 'Contact', 'committee_desc' => 'Le comité coordonne la vie du club, l’organisation des activités, l’accueil des nouveaux et les relations avec les partenaires.', 'manage_label' => 'Gérer le comité', 'none' => 'Aucun membre du comité n’est publié pour le moment.', 'portrait_of' => 'Portrait de'],
    'en' => ['transparency' => 'Transparency', 'member_welcome' => 'Member welcome', 'contact' => 'Contact', 'committee_desc' => 'The committee coordinates club life, activity planning, newcomer onboarding and partner relations.', 'manage_label' => 'Manage committee', 'none' => 'No committee member is published yet.', 'portrait_of' => 'Portrait of'],
    'de' => ['transparency' => 'Transparenz', 'member_welcome' => 'Mitgliederempfang', 'contact' => 'Kontakt', 'committee_desc' => 'Das Komitee koordiniert das Clubleben, die Organisation von Aktivitäten, den Empfang neuer Mitglieder und Partnerbeziehungen.', 'manage_label' => 'Komitee verwalten', 'none' => 'Derzeit ist kein Komiteemitglied veröffentlicht.', 'portrait_of' => 'Porträt von'],
    'es' => ['transparency' => 'Transparencia', 'member_welcome' => 'Acogida de miembros', 'contact' => 'Contacto', 'committee_desc' => 'El comité coordina la vida del club, la organización de actividades, la acogida de nuevos miembros y las relaciones con socios.', 'manage_label' => 'Gestionar comité', 'none' => 'No hay miembros del comité publicados por el momento.', 'portrait_of' => 'Retrato de'],
    'it' => ['transparency' => 'Trasparenza', 'member_welcome' => 'Accoglienza membri', 'contact' => 'Contatto', 'committee_desc' => 'Il comitato coordina la vita del club, l’organizzazione delle attività, l’accoglienza dei nuovi membri e i rapporti con i partner.', 'manage_label' => 'Gestisci comitato', 'none' => 'Nessun membro del comitato pubblicato al momento.', 'portrait_of' => 'Ritratto di'],
    'pt' => ['transparency' => 'Transparência', 'member_welcome' => 'Acolhimento de membros', 'contact' => 'Contacto', 'committee_desc' => 'A comissão coordena a vida do clube, a organização das atividades, o acolhimento de novos membros e as relações com parceiros.', 'manage_label' => 'Gerir comissão', 'none' => 'Nenhum membro da comissão publicado de momento.', 'portrait_of' => 'Retrato de'],
    'nl' => ['transparency' => 'Transparantie', 'member_welcome' => 'Ontvangst van leden', 'contact' => 'Contact', 'committee_desc' => 'Het comité coördineert het clubleven, de organisatie van activiteiten, de ontvangst van nieuwkomers en de relaties met partners.', 'manage_label' => 'Comité beheren', 'none' => 'Er is momenteel geen commissielid gepubliceerd.', 'portrait_of' => 'Portret van'],

    'ar' => ['transparency' => 'الشفافية', 'member_welcome' => 'استقبال الأعضاء', 'contact' => 'اتصال', 'committee_desc' => 'تنسّق اللجنة حياة النادي وتنظيم الأنشطة واستقبال الأعضاء الجدد والعلاقات مع الشركاء.', 'manage_label' => 'إدارة اللجنة', 'none' => 'لا يوجد أي عضو لجنة منشور حالياً.', 'portrait_of' => 'صورة لـ'],
    'bn' => ['transparency' => 'স্বচ্ছতা', 'member_welcome' => 'সদস্য অভ্যর্থনা', 'contact' => 'যোগাযোগ', 'committee_desc' => 'কমিটি ক্লাবের কার্যক্রম, অনুষ্ঠান আয়োজন, নতুন সদস্য গ্রহণ এবং অংশীদার সম্পর্ক সমন্বয় করে।', 'manage_label' => 'কমিটি পরিচালনা', 'none' => 'এই মুহূর্তে কোনো কমিটি সদস্য প্রকাশিত নেই।', 'portrait_of' => 'এর প্রতিকৃতি'],
    'hi' => ['transparency' => 'पारदर्शिता', 'member_welcome' => 'सदस्य स्वागत', 'contact' => 'संपर्क', 'committee_desc' => 'समिति क्लब जीवन, गतिविधियों के आयोजन, नए सदस्यों के स्वागत और भागीदार संबंधों का समन्वय करती है।', 'manage_label' => 'समिति प्रबंधन', 'none' => 'अभी कोई समिति सदस्य प्रकाशित नहीं है।', 'portrait_of' => 'का चित्र'],
    'id' => ['transparency' => 'Transparansi', 'member_welcome' => 'Penyambutan anggota', 'contact' => 'Kontak', 'committee_desc' => 'Komite mengoordinasikan kehidupan klub, penyelenggaraan kegiatan, penyambutan anggota baru, dan hubungan mitra.', 'manage_label' => 'Kelola komite', 'none' => 'Belum ada anggota komite yang dipublikasikan.', 'portrait_of' => 'Potret'],
    'ja' => ['transparency' => '透明性', 'member_welcome' => 'メンバー歓迎', 'contact' => '連絡先', 'committee_desc' => '委員会はクラブ運営、活動企画、新規メンバーの受け入れ、パートナー関係を調整します。', 'manage_label' => '委員会を管理', 'none' => '現在、公開されている委員会メンバーはいません。', 'portrait_of' => 'のポートレート'],
    'ru' => ['transparency' => 'Прозрачность', 'member_welcome' => 'Приём участников', 'contact' => 'Контакты', 'committee_desc' => 'Комитет координирует жизнь клуба, организацию мероприятий, приём новых участников и отношения с партнёрами.', 'manage_label' => 'Управление комитетом', 'none' => 'На данный момент нет опубликованных членов комитета.', 'portrait_of' => 'Портрет'],
    'zh' => ['transparency' => '透明度', 'member_welcome' => '成员欢迎', 'contact' => '联系', 'committee_desc' => '委员会负责协调俱乐部事务、活动组织、新成员接待以及合作伙伴关系。', 'manage_label' => '管理委员会', 'none' => '目前尚未发布任何委员会成员。', 'portrait_of' => '的肖像'],
];
$i18n = i18n_expand_supported_locales($i18n);
$t = static function (string $key) use ($locale, $i18n): string {
    return i18n_localized_value($i18n, $locale, $key);
};

set_page_meta([
    'title' => editorial_text('committee.title'),
    'description' => editorial_text('committee.intro'),
]);

ob_start();
?>
<section class="hero hero-home">
    <div class="card hero-copy">
        <div class="badge">Club ON4CRD</div>
        <h1><?= e(editorial_text('committee.title')) ?></h1>
        <p class="hero-lead"><?= e(editorial_text('committee.intro')) ?></p>
        <div class="pill-row">
            <span class="pill"><?= e(editorial_text('committee.mission')) ?></span>
            <span class="pill"><?= e($t('transparency')) ?></span>
            <span class="pill"><?= e($t('member_welcome')) ?></span>
        </div>
    </div>
    <aside class="hero-panel">
        <h2><?= e($t('contact')) ?></h2>
        <p><?= e($t('committee_desc')) ?></p>
        <?php if (has_permission('admin.access')): ?>
            <div class="actions"><a class="button secondary" href="<?= e(route_url('admin_committee')) ?>"><?= e($t('manage_label')) ?></a></div>
        <?php endif; ?>
    </aside>
</section>

<section class="inner-card">
    <?php if ($members === []): ?>
        <div class="card empty-state"><p><?= e($t('none')) ?></p></div>
    <?php else: ?>
        <div class="directory-grid">
            <?php foreach ($members as $member): ?>
                <article class="member-card">
                    <div class="widget-profile">
                        <img class="avatar member" src="<?= e(asset_url($member['photo_path'] ?: placeholder_avatar((string) $member['callsign'], 256))) ?>" alt="<?= e($t('portrait_of')) ?> <?= e((string) $member['callsign']) ?>">
                        <div>
                            <h2><?= e((string) $member['full_name']) ?></h2>
                            <p class="help"><?= e((string) $member['callsign']) ?></p>
                        </div>
                    </div>
                    <?php if (!empty($member['committee_role'])): ?><p><strong><?= e((string) $member['committee_role']) ?></strong></p><?php endif; ?>
                    <?php if (!empty($member['committee_bio'])): ?><p><?= e((string) $member['committee_bio']) ?></p><?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php
echo render_layout((string) ob_get_clean(), editorial_text('committee.title'));
