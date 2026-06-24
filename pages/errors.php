<?php
declare(strict_types=1);

http_response_code(404);

$locale = current_locale();
$t = i18n_domain_locale('errors', $locale);
$tr = static fn(string $key): string => (string) $t[$key];

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

$description = $tr('description');
set_page_meta([
    'title' => $tr('title'),
    'description' => $description,
    'canonical' => route_url('home'),
    'robots' => 'noindex,nofollow',
    'schema_type' => 'WebPage',
    'content_type' => 'error_page',
    'ai_summary' => $description,
]);

$quickLinks = [
    [
        'label' => $tr('home_button'),
        'description' => $tr('home_hint'),
        'href' => route_url('home'),
    ],
    [
        'label' => $tr('news_link'),
        'description' => $tr('news_hint'),
        'href' => route_url('news'),
    ],
    [
        'label' => $tr('events_link'),
        'description' => $tr('events_hint'),
        'href' => route_url('events'),
    ],
    [
        'label' => $tr('tools_link'),
        'description' => $tr('tools_hint'),
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
            <p class="error-eyebrow"><?= e($tr('eyebrow')) ?></p>
            <h1 id="error-404-title"><?= e($tr('title')) ?></h1>
            <p class="error-lead"><?= e($description) ?></p>
            <?php if ($requestedLabel !== ''): ?>
                <p class="error-requested">
                    <span><?= e($tr('requested_label')) ?></span>
                    <code><?= e($requestedLabel) ?></code>
                </p>
            <?php endif; ?>
            <div class="error-actions">
                <a class="button" href="<?= e(route_url('home')) ?>"><?= e($tr('home_button')) ?></a>
                <a class="button secondary" href="<?= e(route_url('search')) ?>"><?= e($tr('search_button')) ?></a>
            </div>
        </div>
    </div>

    <div class="error-recovery-grid">
        <form method="get" class="error-search-card">
            <input type="hidden" name="route" value="search">
            <label for="error-search-query"><?= e($tr('search_label')) ?></label>
            <div class="error-search-row">
                <input id="error-search-query" type="search" name="q" placeholder="<?= e($tr('search_placeholder')) ?>" required>
                <button class="button" type="submit"><?= e($tr('search_submit')) ?></button>
            </div>
            <p><?= e($tr('search_hint')) ?></p>
        </form>

        <div class="error-links-card">
            <h2><?= e($tr('quick_links_title')) ?></h2>
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

echo render_layout($content, $tr('title'));
