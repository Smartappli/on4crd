<?php
declare(strict_types=1);

http_response_code(404);

$locale = current_locale();
$t = i18n_domain_locale('errors', $locale);
$tr = static fn(string $key, string $fallback): string => (string) ($t[$key] ?? $fallback);

$requestedRoute = trim((string) ($_GET['_not_found_route'] ?? ''));
$requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
$requestPath = is_string($requestPath) ? rawurldecode($requestPath) : '';
$requestedLabel = '';
if ($requestPath !== '' && $requestPath !== '/' && $requestPath !== '/index.php') {
    $requestedLabel = $requestPath;
}
if ($requestedLabel === '' && $requestedRoute !== '' && $requestedRoute !== 'errors') {
    $requestedLabel = $requestedRoute;
}

$description = $tr('description', 'The requested page does not exist or has been moved.');
set_page_meta([
    'title' => $tr('title', '404 - Page not found'),
    'description' => $description,
    'canonical' => route_url('home'),
    'robots' => 'noindex,nofollow',
    'schema_type' => 'WebPage',
    'content_type' => 'error_page',
    'ai_summary' => $description,
]);

$quickLinks = [
    [
        'label' => $tr('home_button', 'Back to homepage'),
        'description' => $tr('home_hint', 'Return to the main ON4CRD portal.'),
        'href' => route_url('home'),
    ],
    [
        'label' => $tr('news_link', 'News'),
        'description' => $tr('news_hint', 'Read the latest club updates.'),
        'href' => route_url('news'),
    ],
    [
        'label' => $tr('events_link', 'Events'),
        'description' => $tr('events_hint', 'Check upcoming activities.'),
        'href' => route_url('events'),
    ],
    [
        'label' => $tr('tools_link', 'Radio tools'),
        'description' => $tr('tools_hint', 'Open the practical radioamateur tools.'),
        'href' => route_url('tools'),
    ],
];

ob_start();
?>
<section class="errors-module error-not-found-page" aria-labelledby="error-404-title">
    <div class="error-hero">
        <div class="error-signal" aria-hidden="true">
            <span class="error-signal-code">404</span>
            <span class="error-signal-line"></span>
            <span class="error-signal-dot"></span>
        </div>
        <div class="error-copy">
            <p class="error-eyebrow"><?= e($tr('eyebrow', 'Signal lost')) ?></p>
            <h1 id="error-404-title"><?= e($tr('title', '404 - Page not found')) ?></h1>
            <p class="error-lead"><?= e($description) ?></p>
            <?php if ($requestedLabel !== ''): ?>
                <p class="error-requested">
                    <span><?= e($tr('requested_label', 'Requested address:')) ?></span>
                    <code><?= e($requestedLabel) ?></code>
                </p>
            <?php endif; ?>
            <div class="error-actions">
                <a class="button" href="<?= e(route_url('home')) ?>"><?= e($tr('home_button', 'Back to homepage')) ?></a>
                <a class="button secondary" href="<?= e(route_url('search')) ?>"><?= e($tr('search_button', 'Search the site')) ?></a>
            </div>
        </div>
    </div>

    <div class="error-recovery-grid">
        <form method="get" class="error-search-card">
            <input type="hidden" name="route" value="search">
            <label for="error-search-query"><?= e($tr('search_label', 'Search on ON4CRD')) ?></label>
            <div class="error-search-row">
                <input id="error-search-query" type="search" name="q" placeholder="<?= e($tr('search_placeholder', 'Keyword, article, event...')) ?>" required>
                <button class="button" type="submit"><?= e($tr('search_submit', 'Search')) ?></button>
            </div>
            <p><?= e($tr('search_hint', 'Try a callsign, a radio topic, an event name or a club service.')) ?></p>
        </form>

        <div class="error-links-card">
            <h2><?= e($tr('quick_links_title', 'Useful links')) ?></h2>
            <div class="error-links">
                <?php foreach ($quickLinks as $link): ?>
                    <a href="<?= e((string) $link['href']) ?>">
                        <span><?= e((string) $link['label']) ?></span>
                        <small><?= e((string) $link['description']) ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php
$content = (string) ob_get_clean();

echo render_layout($content, $tr('title', '404 - Page not found'));
