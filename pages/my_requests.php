<?php
declare(strict_types=1);

$user = require_login();
$locale = current_locale();
$t = i18n_domain_locale('my_requests', $locale);
$text = static fn (string $key): string => (string) ($t[$key] ?? $key);

$title = $text('title');
$intro = $text('intro');

set_page_meta([
    'title' => $title,
    'description' => (string) ($t['meta_desc'] ?? $intro),
    'robots' => 'noindex,nofollow',
    'schema_type' => 'ProfilePage',
]);

$shortcuts = [
    [
        'title' => $text('profile_title'),
        'description' => $text('profile_desc'),
        'url' => route_url('profile'),
        'cta' => $text('profile_cta'),
    ],
    [
        'title' => $text('privacy_title'),
        'description' => $text('privacy_desc'),
        'url' => route_url('gdpr'),
        'cta' => $text('privacy_cta'),
    ],
    [
        'title' => $text('events_title'),
        'description' => $text('events_desc'),
        'url' => route_url('events'),
        'cta' => $text('events_cta'),
    ],
    [
        'title' => $text('articles_title'),
        'description' => $text('articles_desc'),
        'url' => route_url('articles'),
        'cta' => $text('articles_cta'),
    ],
    [
        'title' => $text('classifieds_title'),
        'description' => $text('classifieds_desc'),
        'url' => route_url('classifieds_manage'),
        'cta' => $text('classifieds_cta'),
    ],
    [
        'title' => $text('settings_title'),
        'description' => $text('settings_desc'),
        'url' => route_url('settings'),
        'cta' => $text('settings_cta'),
    ],
];

$submittedArticles = [];
if (table_exists('articles') && table_has_column('articles', 'author_id')) {
    $stmt = db()->prepare('SELECT id, slug, title, status, category, moderation_note, created_at, updated_at FROM articles WHERE author_id = ? ORDER BY updated_at DESC, id DESC LIMIT 50');
    $stmt->execute([(int) $user['id']]);
    $submittedArticles = $stmt->fetchAll() ?: [];
}
$privacyRequests = privacy_member_requests((int) $user['id']);
$registeredRequestsCount = count($submittedArticles) + count($privacyRequests);

$statusLabels = [
    'draft' => $text('article_status_draft'),
    'pending' => $text('article_status_pending'),
    'scheduled' => $text('article_status_scheduled'),
    'published' => $text('article_status_published'),
    'rejected' => $text('article_status_rejected'),
];

ob_start();
?>
<div class="my-requests-page stack">
    <section class="card my-requests-hero">
        <div>
            <span class="badge muted"><?= e($text('badge')) ?></span>
            <h1><?= e($title) ?></h1>
            <p class="help"><?= e($intro) ?></p>
        </div>
        <div class="my-requests-member">
            <span><?= e($text('member_label')) ?></span>
            <strong><?= e(trim((string) ($user['callsign'] ?? '')) !== '' ? (string) $user['callsign'] : (string) ($user['email'] ?? '')) ?></strong>
        </div>
    </section>

    <section class="card my-requests-status">
        <div class="row-between">
            <div>
                <h2><?= e($text('status_title')) ?></h2>
                <p class="help"><?= e($text('empty_body')) ?></p>
            </div>
            <span class="badge muted"><?= (int) $registeredRequestsCount ?></span>
        </div>
        <?php if ($submittedArticles === [] && $privacyRequests === []): ?>
            <div class="my-requests-empty">
                <strong><?= e($text('empty_title')) ?></strong>
                <p><?= e($text('empty_hint')) ?></p>
            </div>
        <?php else: ?>
            <div class="my-requests-grid">
                <?php foreach ($privacyRequests as $request): ?>
                    <article class="my-requests-shortcut">
                        <span class="badge muted"><?= e((string) ($request['status'] ?? 'pending')) ?></span>
                        <h3><?= e($text('privacy_request_prefix')) ?>: <?= e((string) ($request['request_type'] ?? 'access')) ?></h3>
                        <p><?= e($text('privacy_title')) ?></p>
                        <p class="help"><?= e(date('d/m/Y', strtotime((string) ($request['requested_at'] ?? 'now')))) ?></p>
                        <?php if (trim((string) ($request['admin_notes'] ?? '')) !== ''): ?>
                            <p class="help"><?= e((string) $request['admin_notes']) ?></p>
                        <?php endif; ?>
                        <a class="button secondary small" href="<?= e(route_url('gdpr')) ?>"><?= e($text('privacy_cta')) ?></a>
                    </article>
                <?php endforeach; ?>
                <?php foreach ($submittedArticles as $article): ?>
                    <?php
                    $articleStatus = (string) ($article['status'] ?? 'draft');
                    $articleTitle = trim((string) ($article['title'] ?? ''));
                    if ($articleTitle === '') {
                        $articleTitle = $text('article_default_title');
                    }
                    $articleUrl = $articleStatus === 'published' && trim((string) ($article['slug'] ?? '')) !== ''
                        ? route_url('article', ['slug' => (string) $article['slug']])
                        : '';
                    ?>
                    <article class="my-requests-shortcut">
                        <span class="badge muted"><?= e((string) ($statusLabels[$articleStatus] ?? $articleStatus)) ?></span>
                        <h3><?= e($articleTitle) ?></h3>
                        <p><?= e($text('articles_title')) ?> · <?= e((string) ($article['category'] ?? $text('category_default'))) ?></p>
                        <p class="help"><?= e(date('d/m/Y', strtotime((string) ($article['updated_at'] ?? $article['created_at'] ?? 'now')))) ?></p>
                        <?php if (trim((string) ($article['moderation_note'] ?? '')) !== ''): ?>
                            <p class="help"><?= e($text('moderation_note')) ?>: <?= e((string) $article['moderation_note']) ?></p>
                        <?php endif; ?>
                        <?php if ($articleUrl !== ''): ?>
                            <a class="button secondary small" href="<?= e($articleUrl) ?>"><?= e($text('article_open')) ?></a>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="card my-requests-shortcuts" aria-labelledby="my-requests-shortcuts-title">
        <div class="my-requests-section-heading">
            <h2 id="my-requests-shortcuts-title"><?= e($text('shortcuts_title')) ?></h2>
            <p class="help"><?= e($text('shortcuts_intro')) ?></p>
        </div>
        <div class="my-requests-grid">
            <?php foreach ($shortcuts as $shortcut): ?>
                <article class="my-requests-shortcut">
                    <h3><?= e((string) $shortcut['title']) ?></h3>
                    <p><?= e((string) $shortcut['description']) ?></p>
                    <a class="button secondary small" href="<?= e((string) $shortcut['url']) ?>"><?= e((string) $shortcut['cta']) ?></a>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>
<?php

echo render_layout((string) ob_get_clean(), $title);
