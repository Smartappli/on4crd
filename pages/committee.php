<?php
declare(strict_types=1);

$members = committee_members();
$locale = current_locale();
$i18n = require __DIR__ . '/../app/i18n/committee.php';
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
