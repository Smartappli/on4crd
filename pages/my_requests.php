<?php
declare(strict_types=1);

$user = require_login();
$locale = current_locale();
$t = i18n_domain_locale('my_requests', $locale);

$title = (string) ($t['title'] ?? 'Mes demandes');
$intro = (string) ($t['intro'] ?? 'Suivez ici les demandes introduites depuis votre espace membre.');

set_page_meta([
    'title' => $title,
    'description' => (string) ($t['meta_desc'] ?? $intro),
    'robots' => 'noindex,nofollow',
    'schema_type' => 'ProfilePage',
]);

$shortcuts = [
    [
        'title' => (string) ($t['profile_title'] ?? 'Profil'),
        'description' => (string) ($t['profile_desc'] ?? 'Mettre à jour vos informations de membre.'),
        'url' => route_url('profile'),
        'cta' => (string) ($t['profile_cta'] ?? 'Ouvrir le profil'),
    ],
    [
        'title' => (string) ($t['privacy_title'] ?? 'Vie privée'),
        'description' => (string) ($t['privacy_desc'] ?? 'Gérer la visibilité de vos données dans l’annuaire.'),
        'url' => route_url('gdpr'),
        'cta' => (string) ($t['privacy_cta'] ?? 'Gérer la vie privée'),
    ],
    [
        'title' => (string) ($t['events_title'] ?? 'Événements'),
        'description' => (string) ($t['events_desc'] ?? 'Proposer un événement au club depuis la page agenda.'),
        'url' => route_url('events'),
        'cta' => (string) ($t['events_cta'] ?? 'Voir l’agenda'),
    ],
    [
        'title' => (string) ($t['articles_title'] ?? 'Articles'),
        'description' => (string) ($t['articles_desc'] ?? 'Proposer un article ou une catégorie depuis la page articles.'),
        'url' => route_url('articles'),
        'cta' => (string) ($t['articles_cta'] ?? 'Voir les articles'),
    ],
    [
        'title' => (string) ($t['classifieds_title'] ?? 'Petites annonces'),
        'description' => (string) ($t['classifieds_desc'] ?? 'Gérer vos annonces ou proposer une catégorie.'),
        'url' => route_url('classifieds_manage'),
        'cta' => (string) ($t['classifieds_cta'] ?? 'Gérer mes annonces'),
    ],
    [
        'title' => (string) ($t['settings_title'] ?? 'Paramètres'),
        'description' => (string) ($t['settings_desc'] ?? 'Adapter vos préférences de compte.'),
        'url' => route_url('settings'),
        'cta' => (string) ($t['settings_cta'] ?? 'Ouvrir les paramètres'),
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
    'draft' => (string) ($t['article_status_draft'] ?? 'Brouillon'),
    'pending' => (string) ($t['article_status_pending'] ?? 'En validation'),
    'scheduled' => (string) ($t['article_status_scheduled'] ?? 'Planifie'),
    'published' => (string) ($t['article_status_published'] ?? 'Publie'),
    'rejected' => (string) ($t['article_status_rejected'] ?? 'Refuse'),
];

ob_start();
?>
<div class="my-requests-page stack">
    <section class="card my-requests-hero">
        <div>
            <span class="badge muted"><?= e((string) ($t['badge'] ?? 'Espace membre')) ?></span>
            <h1><?= e($title) ?></h1>
            <p class="help"><?= e($intro) ?></p>
        </div>
        <div class="my-requests-member">
            <span><?= e((string) ($t['member_label'] ?? 'Membre')) ?></span>
            <strong><?= e(trim((string) ($user['callsign'] ?? '')) !== '' ? (string) $user['callsign'] : (string) ($user['email'] ?? '')) ?></strong>
        </div>
    </section>

    <section class="card my-requests-status">
        <div class="row-between">
            <div>
                <h2><?= e((string) ($t['status_title'] ?? 'Demandes enregistrées')) ?></h2>
                <p class="help"><?= e((string) ($t['empty_body'] ?? 'Les demandes enregistrées par le site apparaîtront ici.')) ?></p>
            </div>
            <span class="badge muted"><?= (int) $registeredRequestsCount ?></span>
        </div>
        <?php if ($submittedArticles === [] && $privacyRequests === []): ?>
            <div class="my-requests-empty">
                <strong><?= e((string) ($t['empty_title'] ?? 'Aucune demande pour le moment')) ?></strong>
                <p><?= e((string) ($t['empty_hint'] ?? 'Utilisez les raccourcis ci-dessous pour accéder aux démarches disponibles.')) ?></p>
            </div>
        <?php else: ?>
            <div class="my-requests-grid">
                <?php foreach ($privacyRequests as $request): ?>
                    <article class="my-requests-shortcut">
                        <span class="badge muted"><?= e((string) ($request['status'] ?? 'pending')) ?></span>
                        <h3>RGPD: <?= e((string) ($request['request_type'] ?? 'access')) ?></h3>
                        <p><?= e((string) ($t['privacy_title'] ?? 'Vie privee')) ?></p>
                        <p class="help"><?= e(date('d/m/Y', strtotime((string) ($request['requested_at'] ?? 'now')))) ?></p>
                        <?php if (trim((string) ($request['admin_notes'] ?? '')) !== ''): ?>
                            <p class="help"><?= e((string) $request['admin_notes']) ?></p>
                        <?php endif; ?>
                        <a class="button secondary small" href="<?= e(route_url('gdpr')) ?>"><?= e((string) ($t['privacy_cta'] ?? 'Gerer la vie privee')) ?></a>
                    </article>
                <?php endforeach; ?>
                <?php foreach ($submittedArticles as $article): ?>
                    <?php
                    $articleStatus = (string) ($article['status'] ?? 'draft');
                    $articleTitle = trim((string) ($article['title'] ?? ''));
                    if ($articleTitle === '') {
                        $articleTitle = 'Article';
                    }
                    $articleUrl = $articleStatus === 'published' && trim((string) ($article['slug'] ?? '')) !== ''
                        ? route_url('article', ['slug' => (string) $article['slug']])
                        : '';
                    ?>
                    <article class="my-requests-shortcut">
                        <span class="badge muted"><?= e((string) ($statusLabels[$articleStatus] ?? $articleStatus)) ?></span>
                        <h3><?= e($articleTitle) ?></h3>
                        <p><?= e((string) ($t['articles_title'] ?? 'Articles')) ?> · <?= e((string) ($article['category'] ?? 'autres')) ?></p>
                        <p class="help"><?= e(date('d/m/Y', strtotime((string) ($article['updated_at'] ?? $article['created_at'] ?? 'now')))) ?></p>
                        <?php if (trim((string) ($article['moderation_note'] ?? '')) !== ''): ?>
                            <p class="help"><?= e((string) ($t['moderation_note'] ?? 'Note de moderation')) ?>: <?= e((string) $article['moderation_note']) ?></p>
                        <?php endif; ?>
                        <?php if ($articleUrl !== ''): ?>
                            <a class="button secondary small" href="<?= e($articleUrl) ?>"><?= e((string) ($t['article_open'] ?? 'Ouvrir')) ?></a>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="card my-requests-shortcuts" aria-labelledby="my-requests-shortcuts-title">
        <div class="my-requests-section-heading">
            <h2 id="my-requests-shortcuts-title"><?= e((string) ($t['shortcuts_title'] ?? 'Raccourcis utiles')) ?></h2>
            <p class="help"><?= e((string) ($t['shortcuts_intro'] ?? 'Accédez rapidement aux pages liées aux demandes et préférences de votre compte.')) ?></p>
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
