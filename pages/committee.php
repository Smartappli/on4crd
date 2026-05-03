<?php
declare(strict_types=1);

$members = committee_members();
$locale = current_locale();
$i18n = [
    'fr' => ['transparency' => 'Transparence', 'member_welcome' => 'Accueil des membres', 'contact' => 'Contact', 'committee_desc' => 'Le comité coordonne la vie du club, l’organisation des activités, l’accueil des nouveaux et les relations avec les partenaires.', 'manage_label' => 'Gérer le comité', 'none' => 'Aucun membre du comité n’est publié pour le moment.', 'portrait_of' => 'Portrait de'],
    'en' => ['transparency' => 'Transparency', 'member_welcome' => 'Member welcome', 'contact' => 'Contact', 'committee_desc' => 'The committee coordinates club life, activity planning, newcomer onboarding and partner relations.', 'manage_label' => 'Manage committee', 'none' => 'No committee member is published yet.', 'portrait_of' => 'Portrait of'],
    'de' => ['transparency' => 'Transparenz', 'member_welcome' => 'Mitgliederempfang', 'contact' => 'Kontakt', 'committee_desc' => 'Das Komitee koordiniert das Clubleben, die Organisation von Aktivitäten, den Empfang neuer Mitglieder und Partnerbeziehungen.', 'manage_label' => 'Komitee verwalten', 'none' => 'Derzeit ist kein Komiteemitglied veröffentlicht.', 'portrait_of' => 'Porträt von'],
    'nl' => ['transparency' => 'Transparantie', 'member_welcome' => 'Ontvangst van leden', 'contact' => 'Contact', 'committee_desc' => 'Het comité coördineert het clubleven, de organisatie van activiteiten, de ontvangst van nieuwkomers en de relaties met partners.', 'manage_label' => 'Comité beheren', 'none' => 'Er is momenteel geen commissielid gepubliceerd.', 'portrait_of' => 'Portret van'],
];
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
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
