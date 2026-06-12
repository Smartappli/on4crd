<?php
declare(strict_types=1);

if (!function_exists('webotheque_i18n')) {
function webotheque_i18n(string $locale): array
{
    $messages = i18n_domain_locale('webotheque', $locale);
    $fallback = i18n_domain_locale('webotheque', 'en');

    return array_replace($fallback, $messages);
}
}

if (!function_exists('ensure_webotheque_table')) {
function ensure_webotheque_table(): bool
{
    try {
        db()->exec('CREATE TABLE IF NOT EXISTS member_webotheque_links (
            id INT AUTO_INCREMENT PRIMARY KEY,
            member_id INT NOT NULL,
            category VARCHAR(120) NOT NULL DEFAULT \'general\',
            title VARCHAR(255) NOT NULL,
            url VARCHAR(500) NOT NULL,
            description TEXT NULL,
            tags VARCHAR(255) NOT NULL DEFAULT \'\',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL,
            INDEX idx_created (created_at),
            INDEX idx_category (category),
            INDEX idx_tags (tags),
            INDEX idx_member_created (member_id, created_at)
        )');

        if (!table_has_column('member_webotheque_links', 'category')) {
            db()->exec('ALTER TABLE member_webotheque_links ADD COLUMN category VARCHAR(120) NOT NULL DEFAULT "general" AFTER member_id');
        }
        if (!table_has_index('member_webotheque_links', 'idx_category')) {
            db()->exec('ALTER TABLE member_webotheque_links ADD INDEX idx_category (category)');
        }
        db()->exec('UPDATE member_webotheque_links SET category = "general" WHERE category IS NULL OR category = ""');

        return table_exists('member_webotheque_links');
    } catch (Throwable) {
        return false;
    }
}
}

if (!function_exists('webotheque_normalize_url')) {
function webotheque_normalize_url(string $url): string
{
    $url = trim($url);
    if ($url !== '' && !preg_match('~^https?://~i', $url)) {
        $url = 'https://' . $url;
    }
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return '';
    }
    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    if (!in_array($scheme, ['http', 'https'], true)) {
        return '';
    }

    return $url;
}
}

if (!function_exists('webotheque_domain_from_url')) {
function webotheque_domain_from_url(string $url): string
{
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    if (str_starts_with($host, 'www.')) {
        $host = substr($host, 4);
    }

    return $host;
}
}

if (!function_exists('webotheque_category_code')) {
function webotheque_category_code(string $value): string
{
    return content_proposal_category_code($value, 120, 'webotheque');
}
}

if (!function_exists('webotheque_category_label_from_code')) {
function webotheque_category_label_from_code(string $code): string
{
    $label = trim(str_replace('-', ' ', $code));
    if ($label === '') {
        return 'General';
    }

    return mb_convert_case($label, MB_CASE_TITLE, 'UTF-8');
}
}

if (!function_exists('webotheque_categories')) {
/**
 * @return array<string, string>
 */
function webotheque_categories(array $t): array
{
    $categories = [
        'general' => (string) ($t['category_general'] ?? 'General'),
    ];

    try {
        if (table_has_column('member_webotheque_links', 'category')) {
            $rows = db()->query('SELECT category FROM member_webotheque_links WHERE category IS NOT NULL AND category <> "" GROUP BY category ORDER BY category ASC')->fetchAll() ?: [];
            foreach ($rows as $row) {
                $code = webotheque_category_code((string) ($row['category'] ?? ''));
                if ($code !== '' && !isset($categories[$code])) {
                    $categories[$code] = webotheque_category_label_from_code($code);
                }
            }
        }
    } catch (Throwable) {
        // Keep the base category if optional category metadata cannot be read.
    }

    foreach (content_proposal_accepted_categories('webotheque', 120) as $code => $label) {
        $code = webotheque_category_code((string) $code);
        $label = content_proposal_clean_single_line((string) $label, 190);
        if ($code !== '' && $label !== '') {
            $categories[$code] = $label;
        }
    }

    return $categories;
}
}

if (!function_exists('webotheque_category_from_input')) {
/**
 * @param array<string, string> $categories
 */
function webotheque_category_from_input(string $value, array $categories): string
{
    $value = trim($value);
    if ($value === '') {
        return 'general';
    }

    $code = webotheque_category_code($value);
    if (!isset($categories[$code])) {
        throw new RuntimeException('err_category');
    }

    return $code;
}
}

if (!function_exists('webotheque_member_contact')) {
function webotheque_member_contact(array $user): string
{
    $email = trim((string) ($user['email'] ?? ''));
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $email;
    }

    return '';
}
}

if (!function_exists('webotheque_insert_link')) {
function webotheque_insert_link(
    int $memberId,
    string $category,
    string $title,
    string $url,
    string $description = '',
    string $tags = ''
): int {
    $title = content_proposal_clean_single_line($title, 190);
    $url = webotheque_normalize_url($url);
    $description = content_proposal_clean_multiline($description, 5000);
    $tags = content_proposal_clean_single_line($tags, 255);
    $category = webotheque_category_code($category);
    if ($memberId <= 0 || $title === '' || $url === '' || $category === '') {
        throw new RuntimeException($url === '' ? 'err_url' : 'err_required');
    }

    db()->prepare('INSERT INTO member_webotheque_links (member_id, category, title, url, description, tags) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([$memberId, $category, $title, $url, $description !== '' ? $description : null, $tags]);

    return (int) db()->lastInsertId();
}
}

if (!function_exists('webotheque_link_summary')) {
/**
 * @param array<string, string> $t
 */
function webotheque_link_summary(array $t, string $categoryLabel, string $description, string $tags): string
{
    return content_proposal_details_text([
        (string) ($t['category_field'] ?? 'Category') => $categoryLabel,
        (string) ($t['description_field'] ?? 'Description') => $description,
        (string) ($t['tags_field'] ?? 'Tags') => $tags,
    ]);
}
}

if (!function_exists('webotheque_stats')) {
function webotheque_stats(): array
{
    $total = (int) (db()->query('SELECT COUNT(*) FROM member_webotheque_links')->fetchColumn() ?: 0);
    $rows = db()->query('SELECT url FROM member_webotheque_links')->fetchAll() ?: [];
    $domains = [];
    foreach ($rows as $row) {
        $domain = webotheque_domain_from_url((string) ($row['url'] ?? ''));
        if ($domain !== '') {
            $domains[$domain] = true;
        }
    }
    $latest = (string) (db()->query('SELECT created_at FROM member_webotheque_links ORDER BY created_at DESC, id DESC LIMIT 1')->fetchColumn() ?: '');

    return ['total' => $total, 'domains' => count($domains), 'latest' => $latest];
}
}

if (!function_exists('webotheque_fetch_links')) {
function webotheque_fetch_links(string $search, string $category = '', int $limit = 80): array
{
    $where = [];
    $params = [];
    if ($category !== '') {
        $where[] = 'category = ?';
        $params[] = $category;
    }
    if ($search !== '') {
        $where[] = '(title LIKE ? OR url LIKE ? OR description LIKE ? OR tags LIKE ? OR category LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like, $like);
    }
    $whereSql = $where === [] ? '' : (' WHERE ' . implode(' AND ', $where));
    $stmt = db()->prepare('SELECT * FROM member_webotheque_links' . $whereSql . ' ORDER BY created_at DESC, id DESC LIMIT ' . (int) $limit);
    $stmt->execute($params);

    return $stmt->fetchAll() ?: [];
}
}

if (!function_exists('render_webotheque_cards')) {
function render_webotheque_cards(array $links, array $t, array $categories = []): string
{
    $html = '';
    foreach ($links as $link) {
        $title = trim((string) ($link['title'] ?? ''));
        $url = trim((string) ($link['url'] ?? ''));
        if ($title === '' || $url === '') {
            continue;
        }
        $description = trim((string) ($link['description'] ?? ''));
        $tags = trim((string) ($link['tags'] ?? ''));
        $category = webotheque_category_code((string) ($link['category'] ?? 'general'));
        $categoryLabel = (string) ($categories[$category] ?? webotheque_category_label_from_code($category));
        $domain = webotheque_domain_from_url($url);
        $html .= '<article class="news-card feature-card webotheque-card">'
            . '<span class="badge muted">' . e($domain !== '' ? $domain : (string) $t['link']) . '</span>'
            . '<h2>' . e($title) . '</h2>';
        if ($description !== '') {
            $html .= '<p>' . e($description) . '</p>';
        }
        $html .= '<p class="help">' . e((string) ($t['category_field'] ?? 'Category')) . ': ' . e($categoryLabel) . '</p>';
        if ($tags !== '') {
            $html .= '<p class="help">' . e((string) $t['tags']) . ': ' . e($tags) . '</p>';
        }
        $html .= '<p class="actions"><a class="button secondary" href="' . e($url) . '" target="_blank" rel="noopener noreferrer">' . e((string) $t['open']) . '</a></p>'
            . '</article>';
    }

    return $html;
}
}

if (!function_exists('render_webotheque_page')) {
function render_webotheque_page(): void
{
    require_login();
    $locale = current_locale();
    $t = webotheque_i18n($locale);

    set_page_meta([
        'title' => (string) $t['title'],
        'description' => (string) $t['meta_desc'],
        'robots' => 'noindex,follow',
        'schema_type' => 'CollectionPage',
    ]);

    if (!ensure_webotheque_table()) {
        echo render_layout('<div class="card"><p>' . e((string) $t['storage_unavailable']) . '</p></div>', (string) $t['title']);
        return;
    }

    $search = trim((string) ($_GET['q'] ?? ''));
    if (mb_strlen($search) > 120) {
        $search = mb_substr($search, 0, 120);
    }
    $stats = webotheque_stats();
    $links = webotheque_fetch_links($search);
    $latestDate = trim((string) ($stats['latest'] ?? ''));
    $latestLabel = $latestDate !== '' ? date('d/m/Y', strtotime($latestDate) ?: time()) : (string) $t['none'];

    ob_start();
    ?>
    <div class="stack webotheque-page">
        <section class="page-hero webotheque-hero">
            <div class="webotheque-hero-copy">
                <p class="eyebrow"><?= e((string) $t['members_area']) ?></p>
                <h1><?= e((string) $t['title']) ?></h1>
                <p class="help"><?= e((string) $t['intro']) ?></p>
            </div>
            <div class="webotheque-hero-side">
                <div class="webotheque-hero-stats">
                    <article><span><?= e((string) $t['links']) ?></span><strong><?= (int) ($stats['total'] ?? 0) ?></strong></article>
                    <article><span><?= e((string) $t['domains']) ?></span><strong><?= (int) ($stats['domains'] ?? 0) ?></strong></article>
                    <article><span><?= e((string) $t['latest']) ?></span><strong><?= e($latestLabel) ?></strong></article>
                </div>
                <p class="actions webotheque-hero-actions">
                    <a class="button secondary" href="#webotheque-list"><?= e((string) $t['view_links']) ?></a>
                    <?php if (has_permission('admin.access')): ?>
                        <a class="button" href="<?= e(route_url('admin_webotheque')) ?>"><?= e((string) $t['administration']) ?></a>
                    <?php endif; ?>
                </p>
            </div>
        </section>

        <section class="card webotheque-search-panel">
            <form method="get" class="inline-form webotheque-search-form">
                <input type="hidden" name="route" value="webotheque">
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $t['search_ph']) ?>">
                <button class="button" type="submit"><?= e((string) $t['search']) ?></button>
                <?php if ($search !== ''): ?>
                    <a class="button secondary" href="<?= e(route_url('webotheque')) ?>"><?= e((string) $t['reset']) ?></a>
                <?php endif; ?>
            </form>
        </section>

        <section id="webotheque-list" class="webotheque-content">
            <?php if ($links === []): ?>
                <div class="card"><p><?= e((string) $t['empty']) ?><?php if ($search !== ''): ?><?= e((string) $t['for_filters']) ?>.<?php endif; ?></p></div>
            <?php else: ?>
                <div class="news-grid webotheque-grid"><?= render_webotheque_cards($links, $t) ?></div>
            <?php endif; ?>
        </section>
    </div>
    <?php
    echo render_layout((string) ob_get_clean(), (string) $t['title']);
}
}

if (!function_exists('render_admin_webotheque_page')) {
function render_admin_webotheque_page(): void
{
    require_permission('admin.access');
    $user = current_user();
    $locale = current_locale();
    $t = webotheque_i18n($locale);

    set_page_meta([
        'title' => (string) $t['admin_title'],
        'description' => (string) $t['meta_desc'],
        'robots' => 'noindex,nofollow',
    ]);

    if (!ensure_webotheque_table()) {
        echo render_layout('<div class="card"><p>' . e((string) $t['storage_unavailable']) . '</p></div>', (string) $t['admin_title']);
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            verify_csrf();
            $action = (string) ($_POST['action'] ?? 'add_link');
            if ($action === 'delete_link') {
                $id = (int) ($_POST['id'] ?? 0);
                db()->prepare('DELETE FROM member_webotheque_links WHERE id = ? LIMIT 1')->execute([$id]);
                set_flash('success', (string) $t['ok_deleted']);
                redirect('admin_webotheque');
            }

            $title = trim((string) ($_POST['title'] ?? ''));
            $url = webotheque_normalize_url((string) ($_POST['url'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $tags = mb_safe_substr(trim((string) ($_POST['tags'] ?? '')), 0, 255);
            if ($title === '' || $url === '') {
                throw new RuntimeException($url === '' ? 'err_url' : 'err_required');
            }
            db()->prepare('INSERT INTO member_webotheque_links (member_id, title, url, description, tags) VALUES (?, ?, ?, ?, ?)')
                ->execute([(int) ($user['id'] ?? 0), $title, $url, $description, $tags]);
            set_flash('success', (string) $t['ok_added']);
            redirect('admin_webotheque');
        } catch (Throwable $throwable) {
            $key = $throwable->getMessage();
            set_flash('error', (string) ($t[$key] ?? $key));
            redirect('admin_webotheque');
        }
    }

    $search = trim((string) ($_GET['q'] ?? ''));
    if (mb_strlen($search) > 120) {
        $search = mb_substr($search, 0, 120);
    }
    $stats = webotheque_stats();
    $links = webotheque_fetch_links($search, 120);
    $latestDate = trim((string) ($stats['latest'] ?? ''));
    $latestLabel = $latestDate !== '' ? date('d/m/Y', strtotime($latestDate) ?: time()) : (string) $t['none'];

    ob_start();
    ?>
    <div class="stack admin-webotheque-page">
        <section class="page-hero admin-webotheque-hero">
            <div class="admin-webotheque-hero-copy">
                <p class="eyebrow"><?= e((string) $t['administration']) ?></p>
                <h1><?= e((string) $t['title']) ?></h1>
                <p class="help"><?= e((string) $t['intro']) ?></p>
            </div>
            <div class="admin-webotheque-hero-side">
                <div class="webotheque-hero-stats">
                    <article><span><?= e((string) $t['links']) ?></span><strong><?= (int) ($stats['total'] ?? 0) ?></strong></article>
                    <article><span><?= e((string) $t['domains']) ?></span><strong><?= (int) ($stats['domains'] ?? 0) ?></strong></article>
                    <article><span><?= e((string) $t['latest']) ?></span><strong><?= e($latestLabel) ?></strong></article>
                </div>
                <p class="actions admin-webotheque-hero-actions">
                    <a class="button secondary" href="<?= e(route_url('webotheque')) ?>"><?= e((string) $t['view_links']) ?></a>
                    <a class="button" href="#webotheque-add"><?= e((string) $t['add_link']) ?></a>
                </p>
            </div>
        </section>

        <section class="card" id="webotheque-add">
            <h2><?= e((string) $t['add_link']) ?></h2>
            <p class="help"><?= e((string) $t['add_link_help']) ?></p>
            <form method="post" class="stack admin-webotheque-form">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="add_link">
                <label><span><?= e((string) $t['title_field']) ?></span><input type="text" name="title" maxlength="255" required></label>
                <label><span><?= e((string) $t['url_field']) ?></span><input type="url" name="url" maxlength="500" placeholder="https://example.org" required></label>
                <label><span><?= e((string) $t['description_field']) ?></span><textarea name="description" rows="4"></textarea></label>
                <label><span><?= e((string) $t['tags_field']) ?></span><input type="text" name="tags" maxlength="255"></label>
                <p class="actions"><button class="button" type="submit"><?= e((string) $t['save']) ?></button></p>
            </form>
        </section>

        <section class="card webotheque-search-panel">
            <form method="get" class="inline-form webotheque-search-form">
                <input type="hidden" name="route" value="admin_webotheque">
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $t['search_ph']) ?>">
                <button class="button" type="submit"><?= e((string) $t['search']) ?></button>
                <?php if ($search !== ''): ?>
                    <a class="button secondary" href="<?= e(route_url('admin_webotheque')) ?>"><?= e((string) $t['reset']) ?></a>
                <?php endif; ?>
            </form>
        </section>

        <section class="admin-webotheque-list">
            <h2><?= e((string) $t['content_list']) ?></h2>
            <?php if ($links === []): ?>
                <div class="card"><p><?= e((string) $t['empty']) ?></p></div>
            <?php else: ?>
                <div class="news-grid webotheque-grid">
                    <?php foreach ($links as $link): ?>
                        <?php $url = (string) ($link['url'] ?? ''); ?>
                        <article class="news-card feature-card webotheque-card">
                            <span class="badge muted"><?= e(webotheque_domain_from_url($url) ?: (string) $t['link']) ?></span>
                            <h3><?= e((string) ($link['title'] ?? '')) ?></h3>
                            <?php if (trim((string) ($link['description'] ?? '')) !== ''): ?><p><?= e((string) $link['description']) ?></p><?php endif; ?>
                            <?php if (trim((string) ($link['tags'] ?? '')) !== ''): ?><p class="help"><?= e((string) $t['tags']) ?>: <?= e((string) $link['tags']) ?></p><?php endif; ?>
                            <div class="actions">
                                <a class="button secondary" href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer"><?= e((string) $t['open']) ?></a>
                                <form method="post" class="inline-form" onsubmit="return confirm('<?= e((string) $t['confirm_delete']) ?>');">
                                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete_link">
                                    <input type="hidden" name="id" value="<?= (int) ($link['id'] ?? 0) ?>">
                                    <button class="button secondary" type="submit"><?= e((string) $t['delete']) ?></button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
    <?php
    echo render_layout((string) ob_get_clean(), (string) $t['admin_title']);
}
}
