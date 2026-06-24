<?php
declare(strict_types=1);

$locale = current_locale();
$t = i18n_domain_locale('schools', $locale);

set_page_meta([
    'title' => (string) $t['meta_title'],
    'description' => (string) $t['meta_desc'],
    'schema_type' => 'WebPage',
]);

$resources = [
    ['title' => (string) $t['r1_t'], 'text' => (string) $t['r1_x']],
    ['title' => (string) $t['r2_t'], 'text' => (string) $t['r2_x']],
    ['title' => (string) $t['r3_t'], 'text' => (string) $t['r3_x']],
];

ob_start();
?>
<section class="hero hero-home">
    <div class="card hero-copy">
        <div class="badge"><?= e((string) $t['badge']) ?></div>
        <h1><?= e((string) $t['title']) ?></h1>
        <p class="hero-lead"><?= e((string) $t['lead']) ?></p>
        <div class="pill-row">
            <span class="pill"><?= e((string) $t['pill_discovery']) ?></span>
            <span class="pill"><?= e((string) $t['pill_demos']) ?></span>
            <span class="pill"><?= e((string) $t['pill_tech']) ?></span>
        </div>
    </div>
    <aside class="hero-panel">
        <h2><?= e((string) $t['formats']) ?></h2>
        <ul class="feature-list compact-feature-list">
            <li><strong><?= e((string) $t['class_title']) ?></strong><span><?= e((string) $t['class_text']) ?></span></li>
            <li><strong><?= e((string) $t['club_title']) ?></strong><span><?= e((string) $t['club_text']) ?></span></li>
            <li><strong><?= e((string) $t['event_title']) ?></strong><span><?= e((string) $t['event_text']) ?></span></li>
        </ul>
    </aside>
</section>

<section class="grid-3 inner-card">
    <?php foreach ($resources as $resource): ?>
        <article class="card feature-card">
            <h2><?= e($resource['title']) ?></h2>
            <p><?= e($resource['text']) ?></p>
        </article>
    <?php endforeach; ?>
</section>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['layout']);
