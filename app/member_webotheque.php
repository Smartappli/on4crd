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
    $rawScheme = parse_url($url, PHP_URL_SCHEME);
    if (is_string($rawScheme) && $rawScheme !== '' && !in_array(strtolower($rawScheme), ['http', 'https'], true)) {
        return '';
    }
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

if (!function_exists('webotheque_default_categories')) {
/**
 * @return array<string, string>
 */
function webotheque_default_categories(array $t): array
{
    return [
        'general' => (string) ($t['category_general'] ?? 'General'),
        'radioamateur' => 'Radioamateur',
        'antennes' => 'Antennes',
        'propagation' => 'Propagation',
        'modes-numeriques' => 'Modes numériques',
        'logiciels' => 'Logiciels',
        'materiel' => 'Matériel',
        'reglementation' => 'Réglementation',
        'formation' => 'Formation',
    ];
}
}

if (!function_exists('webotheque_categories')) {
/**
 * @return array<string, string>
 */
function webotheque_categories(array $t): array
{
    $categories = webotheque_default_categories($t);

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

    foreach (content_proposal_accepted_categories('webotheque', 120) + content_proposal_accepted_terms('webotheque', 'domain', 120, 'webotheque') as $code => $label) {
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
        (string) ($t['domain_field'] ?? $t['category_field'] ?? 'Domain') => $categoryLabel,
        (string) ($t['description_field'] ?? 'Description') => $description,
        (string) ($t['tags_field'] ?? 'Tags') => $tags,
    ]);
}
}

if (!function_exists('webotheque_proposal_source_url')) {
function webotheque_proposal_source_url(string $sourceRef): string
{
    $sourceRef = trim($sourceRef);
    if ($sourceRef === '') {
        return '';
    }
    if (preg_match('/https?:\/\/\S+/i', $sourceRef, $match) === 1) {
        return webotheque_normalize_url((string) $match[0]);
    }

    return webotheque_normalize_url($sourceRef);
}
}

if (!function_exists('webotheque_proposal_detail_from_summary')) {
/**
 * @param list<string> $labels
 */
function webotheque_proposal_detail_from_summary(string $summary, array $labels): string
{
    $wanted = [];
    foreach ($labels as $label) {
        $normalized = mb_strtolower(trim($label), 'UTF-8');
        if ($normalized !== '') {
            $wanted[$normalized] = true;
        }
    }
    foreach (preg_split('/\R/u', $summary) ?: [] as $line) {
        if (preg_match('/^\s*([^:]{1,120}):\s*(.+)\s*$/u', (string) $line, $matches) !== 1) {
            continue;
        }
        $label = mb_strtolower(trim((string) $matches[1]), 'UTF-8');
        if (isset($wanted[$label])) {
            return trim((string) $matches[2]);
        }
    }

    return '';
}
}

if (!function_exists('webotheque_proposal_category_from_summary')) {
/**
 * @param array<string, string> $categories
 */
function webotheque_proposal_category_from_summary(string $summary, array $categories): string
{
    foreach (preg_split('/\R/u', $summary) ?: [] as $line) {
        if (preg_match('/^\s*([^:]{1,120}):\s*(.+)\s*$/u', (string) $line, $matches) !== 1) {
            continue;
        }
        $code = webotheque_category_code((string) $matches[2]);
        if (isset($categories[$code])) {
            return $code;
        }
    }

    return 'general';
}
}

if (!function_exists('webotheque_tags_from_text')) {
/**
 * @return array<string, string>
 */
function webotheque_tags_from_text(string $value): array
{
    $tags = [];
    foreach (preg_split('/[,;#]+/u', $value) ?: [] as $part) {
        $tag = content_proposal_clean_single_line((string) $part, 80);
        if ($tag === '') {
            continue;
        }
        $tags[mb_strtolower($tag, 'UTF-8')] = $tag;
    }

    return $tags;
}
}

if (!function_exists('webotheque_accepted_tags')) {
/**
 * @return array<string, string>
 */
function webotheque_accepted_tags(): array
{
    $tags = [];
    foreach (content_proposal_accepted_terms('webotheque', 'tag', 80, 'tag') as $label) {
        foreach (webotheque_tags_from_text((string) $label) as $key => $tag) {
            $tags[$key] = $tag;
        }
    }

    return $tags;
}
}

if (!function_exists('webotheque_stats')) {
function webotheque_stats(): array
{
    $rows = db()->query('SELECT url, tags, category, created_at FROM member_webotheque_links')->fetchAll() ?: [];
    $domains = [];
    $tags = webotheque_accepted_tags();
    $byCategory = [];
    $latest = '';
    $latestTimestamp = 0;
    foreach ($rows as $row) {
        $domain = webotheque_domain_from_url((string) ($row['url'] ?? ''));
        if ($domain !== '') {
            $domains[$domain] = true;
        }
        $category = webotheque_category_code((string) ($row['category'] ?? ''));
        if ($category !== '') {
            $byCategory[$category] = ($byCategory[$category] ?? 0) + 1;
        }
        foreach (webotheque_tags_from_text((string) ($row['tags'] ?? '')) as $key => $tag) {
            $tags[$key] = $tag;
        }
        $createdAt = trim((string) ($row['created_at'] ?? ''));
        $createdTimestamp = $createdAt !== '' ? strtotime($createdAt) : false;
        if ($createdTimestamp !== false && $createdTimestamp > $latestTimestamp) {
            $latestTimestamp = $createdTimestamp;
            $latest = $createdAt;
        }
    }

    return ['total' => count($rows), 'tags' => count($tags), 'domains' => count($domains), 'latest' => $latest, 'by_category' => $byCategory];
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
        $html .= '<p class="help">' . e((string) ($t['domain_field'] ?? $t['category_field'] ?? 'Domain')) . ': ' . e($categoryLabel) . '</p>';
        if ($tags !== '') {
            $html .= '<p class="help">' . e((string) $t['tags']) . ': ' . e($tags) . '</p>';
        }
        $html .= '<p class="actions"><a class="button secondary" href="' . e($url) . '" target="_blank" rel="noopener noreferrer">' . e((string) $t['open']) . '</a></p>'
            . '</article>';
    }

    return $html;
}
}

if (!function_exists('render_webotheque_link_fields')) {
/**
 * @param array<string, string> $t
 * @param array<string, string> $categories
 */
function render_webotheque_link_fields(array $t, array $categories, ?string $proposalContact = null): string
{
    $html = '<label><span>' . e((string) $t['title_field']) . '</span><input type="text" name="title" maxlength="190" required></label>'
        . '<label><span>' . e((string) $t['url_field']) . '</span><input type="url" name="url" maxlength="500" placeholder="https://example.org" required></label>'
        . '<label><span>' . e((string) ($t['domain_field'] ?? $t['category_field'])) . '</span><select name="category">';

    foreach ($categories as $code => $label) {
        $html .= '<option value="' . e((string) $code) . '">' . e((string) $label) . '</option>';
    }

    $html .= '</select></label>'
        . '<label><span>' . e((string) $t['description_field']) . '</span><textarea name="description" rows="4"></textarea></label>'
        . '<label><span>' . e((string) $t['tags_field']) . '</span><input type="text" name="tags" maxlength="255"></label>';

    if ($proposalContact !== null) {
        $html .= '<label><span>' . e((string) $t['contact_field']) . '</span><input type="email" name="proposal_contact" maxlength="220" value="' . e($proposalContact) . '"></label>';
    }

    return $html;
}
}

if (!function_exists('render_webotheque_page')) {
function render_webotheque_page(): void
{
    $user = require_login();
    $locale = current_locale();
    $t = webotheque_i18n($locale);
    $memberAreaLabel = member_area_eyebrow_label($locale);

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

    $categories = webotheque_default_categories($t) + webotheque_categories($t);
    $proposalContact = webotheque_member_contact($user);
    $canAutoValidate = has_permission('admin.access');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            verify_csrf();
            $action = (string) ($_POST['action'] ?? '');

            if ($action === 'propose_domain' || $action === 'propose_category') {
                $proposalCategory = content_proposal_clean_single_line((string) ($_POST['proposal_domain'] ?? $_POST['proposal_category'] ?? ''), 120);
                $proposalDetails = content_proposal_clean_multiline((string) ($_POST['proposal_details'] ?? ''), 1200);
                $proposalContact = content_proposal_clean_single_line((string) ($_POST['proposal_contact'] ?? $proposalContact), 220);
                if ($proposalCategory === '') {
                    throw new RuntimeException('err_category_required');
                }

                $summary = content_proposal_details_text([
                    (string) ($t['proposal_details_field'] ?? 'Details') => $proposalDetails,
                ]);
                $autoAccept = has_permission('admin.access');
                $proposalStatus = $autoAccept ? 'accepted' : 'pending';
                $proposalId = content_proposal_create((int) $user['id'], 'webotheque', 'domain', $proposalCategory, $summary, $proposalContact, '', $proposalStatus);
                if ($autoAccept) {
                    set_flash('success', (string) ($t['ok_category_added'] ?? 'Category created and approved.'));
                    redirect_url(route_url_clean('webotheque', ['category' => content_proposal_category_code($proposalCategory, 120, 'webotheque')]));
                }

                content_proposal_notify_site((string) ($t['propose_category_subject'] ?? 'Web library category proposal'), [
                    'area' => 'webotheque',
                    'proposal_type' => 'domain',
                    'title' => $proposalCategory,
                    'summary' => $summary,
                    'contact' => $proposalContact,
                    'source_ref' => 'content_proposals#' . $proposalId,
                ]);
                set_flash('success', (string) ($t['proposal_recorded'] ?? 'Proposal saved in your content area.'));
                redirect('my_requests');
            }

            if ($action === 'propose_tag') {
                $proposalTag = content_proposal_clean_single_line((string) ($_POST['proposal_tag'] ?? ''), 80);
                $proposalDetails = content_proposal_clean_multiline((string) ($_POST['proposal_details'] ?? ''), 1200);
                $proposalContact = content_proposal_clean_single_line((string) ($_POST['proposal_contact'] ?? $proposalContact), 220);
                if ($proposalTag === '') {
                    throw new RuntimeException('err_tag_required');
                }

                $summary = content_proposal_details_text([
                    (string) ($t['proposal_details_field'] ?? 'Details') => $proposalDetails,
                ]);
                $autoAccept = has_permission('admin.access');
                $proposalStatus = $autoAccept ? 'accepted' : 'pending';
                $proposalId = content_proposal_create((int) $user['id'], 'webotheque', 'tag', $proposalTag, $summary, $proposalContact, '', $proposalStatus);
                if ($autoAccept) {
                    set_flash('success', (string) ($t['ok_tag_added'] ?? 'Tag created and approved.'));
                    redirect_url(route_url('webotheque'));
                }

                content_proposal_notify_site((string) ($t['propose_tag_subject'] ?? 'Web library tag proposal'), [
                    'area' => 'webotheque',
                    'proposal_type' => 'tag',
                    'title' => $proposalTag,
                    'summary' => $summary,
                    'contact' => $proposalContact,
                    'source_ref' => 'content_proposals#' . $proposalId,
                ]);
                set_flash('success', (string) ($t['proposal_recorded'] ?? 'Proposal saved in your content area.'));
                redirect('my_requests');
            }

            if ($action === 'propose_link') {
                $title = content_proposal_clean_single_line((string) ($_POST['title'] ?? ''), 190);
                $url = webotheque_normalize_url((string) ($_POST['url'] ?? ''));
                $description = content_proposal_clean_multiline((string) ($_POST['description'] ?? ''), 5000);
                $tags = content_proposal_clean_single_line((string) ($_POST['tags'] ?? ''), 255);
                $category = webotheque_category_from_input((string) ($_POST['category'] ?? ''), $categories);
                $proposalContact = content_proposal_clean_single_line((string) ($_POST['proposal_contact'] ?? $proposalContact), 220);
                if ($title === '' || $url === '') {
                    throw new RuntimeException($url === '' ? 'err_url' : 'err_required');
                }

                $summary = webotheque_link_summary($t, (string) ($categories[$category] ?? $category), $description, $tags);
                $autoAccept = has_permission('admin.access');
                $proposalStatus = $autoAccept ? 'accepted' : 'pending';
                $proposalId = content_proposal_create((int) $user['id'], 'webotheque', 'content', $title, $summary, $proposalContact, $url, $proposalStatus);
                if ($autoAccept) {
                    webotheque_insert_link((int) $user['id'], $category, $title, $url, $description, $tags);
                    set_flash('success', (string) ($t['ok_added'] ?? 'Link added.'));
                    redirect_url(route_url_clean('webotheque', ['category' => $category]));
                }

                content_proposal_notify_site((string) ($t['propose_link_subject'] ?? 'Web library link proposal'), [
                    'area' => 'webotheque',
                    'proposal_type' => 'content',
                    'title' => $title,
                    'summary' => $summary,
                    'contact' => $proposalContact,
                    'source_ref' => 'content_proposals#' . $proposalId . ' ' . $url,
                ]);
                set_flash('success', (string) ($t['proposal_recorded'] ?? 'Proposal saved in your content area.'));
                redirect('my_requests');
            }

            throw new RuntimeException('invalid');
        } catch (Throwable $throwable) {
            $key = $throwable->getMessage();
            set_flash('error', (string) ($t[$key] ?? $key));
            redirect_url(route_url('webotheque'));
        }
    }

    $search = trim((string) ($_GET['q'] ?? ''));
    if (mb_strlen($search) > 120) {
        $search = mb_substr($search, 0, 120);
    }
    $categoryFilter = '';
    $categoryInput = trim((string) ($_GET['category'] ?? ''));
    if ($categoryInput !== '') {
        $categoryCode = webotheque_category_code($categoryInput);
        if (isset($categories[$categoryCode])) {
            $categoryFilter = $categoryCode;
        }
    }
    $showLinkProposalForm = (string) ($_GET['propose_link'] ?? '') === '1';
    $showCategoryProposalForm = (string) ($_GET['propose_domain'] ?? $_GET['propose_category'] ?? '') === '1';
    $showTagProposalForm = (string) ($_GET['propose_tag'] ?? '') === '1';
    $stats = webotheque_stats();
    $links = webotheque_fetch_links($search, $categoryFilter);
    $pendingWebothequeAdminUrl = route_url_clean('admin_webotheque', ['status' => 'pending']) . '#pending-proposals';
    $pendingWebothequeAdminLabel = $locale === 'fr' ? 'Administrer' : 'Manage';

    ob_start();
    ?>
    <div class="stack webotheque-page">
        <section class="page-hero webotheque-hero">
            <div class="webotheque-hero-copy">
                <p class="eyebrow"><?= e($memberAreaLabel) ?></p>
                <h1><?= e((string) $t['title']) ?></h1>
                <p class="help"><?= e((string) $t['intro']) ?></p>
            </div>
            <div class="webotheque-hero-side">
                <div class="webotheque-hero-stats">
                    <article><span><?= e((string) $t['links']) ?></span><strong><?= (int) ($stats['total'] ?? 0) ?></strong></article>
                    <article><span><?= e((string) $t['tags']) ?></span><strong><?= (int) ($stats['tags'] ?? 0) ?></strong></article>
                    <article><span><?= e((string) $t['domains']) ?></span><strong><?= (int) ($stats['domains'] ?? 0) ?></strong></article>
                    <article><span><?= e((string) $t['latest']) ?></span><strong><?= e(module_hero_latest_stat_date_label((string) ($stats['latest'] ?? ''), $locale)) ?></strong></article>
                </div>
                <div class="actions webotheque-hero-actions">
                    <details class="webotheque-propose-menu">
                        <summary class="button" aria-haspopup="menu"><?= e((string) $t['propose_menu']) ?></summary>
                        <div class="webotheque-propose-menu-panel" role="menu">
                            <a class="webotheque-propose-menu-item" role="menuitem" href="<?= e(route_url('webotheque', ['propose_domain' => '1'])) ?>" data-webotheque-modal-open="webotheque-domain-dialog" aria-haspopup="dialog" aria-controls="webotheque-domain-dialog"><?= e((string) $t['propose_category_item']) ?></a>
                            <a class="webotheque-propose-menu-item" role="menuitem" href="<?= e(route_url('webotheque', ['propose_tag' => '1'])) ?>" data-webotheque-modal-open="webotheque-tag-dialog" aria-haspopup="dialog" aria-controls="webotheque-tag-dialog"><?= e((string) $t['propose_tag_item']) ?></a>
                            <a class="webotheque-propose-menu-item" role="menuitem" href="<?= e(route_url('webotheque', ['propose_link' => '1'])) ?>" data-webotheque-modal-open="webotheque-link-dialog" aria-haspopup="dialog" aria-controls="webotheque-link-dialog"><?= e((string) $t['propose_link_item']) ?></a>
                        </div>
                    </details>
                    <?php if ($canAutoValidate): ?>
                        <a class="button secondary" href="<?= e($pendingWebothequeAdminUrl) ?>"><?= e($pendingWebothequeAdminLabel) ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <dialog class="webotheque-proposal-dialog" id="webotheque-link-dialog" aria-labelledby="webotheque-link-title"<?= $showLinkProposalForm ? ' open data-webotheque-auto-open' : '' ?>>
            <div class="webotheque-proposal-dialog-card">
                <div class="webotheque-proposal-dialog-header module-dialog-header">
                    <div>
                        <p class="eyebrow"><?= e((string) $t['title']) ?></p>
                        <h2 id="webotheque-link-title"><?= e((string) $t['propose_link']) ?></h2>
                        <p class="help"><?= e($canAutoValidate ? (string) $t['proposal_auto_accept_help'] : (string) $t['propose_link_help']) ?></p>
                    </div>
                    <button class="webotheque-proposal-dialog-close module-dialog-close" type="button" data-webotheque-modal-close aria-label="<?= e((string) $t['cancel']) ?>">&times;</button>
                </div>
                <form method="post" class="webotheque-proposal-form module-dialog-form">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="propose_link">
                    <?= render_webotheque_link_fields($t, $categories, $proposalContact) ?>
                    <p class="webotheque-proposal-dialog-actions module-dialog-actions">
                        <button class="button" type="submit"><?= e((string) $t['submit_proposal']) ?></button>
                        <button class="button secondary" type="button" data-webotheque-modal-close><?= e((string) $t['cancel']) ?></button>
                    </p>
                </form>
            </div>
        </dialog>

        <dialog class="webotheque-proposal-dialog" id="webotheque-domain-dialog" aria-labelledby="webotheque-domain-title"<?= $showCategoryProposalForm ? ' open data-webotheque-auto-open' : '' ?>>
            <div class="webotheque-proposal-dialog-card">
                <div class="webotheque-proposal-dialog-header module-dialog-header">
                    <div>
                        <p class="eyebrow"><?= e((string) $t['title']) ?></p>
                        <h2 id="webotheque-domain-title"><?= e((string) $t['propose_category']) ?></h2>
                        <p class="help"><?= e($canAutoValidate ? (string) $t['proposal_auto_accept_help'] : (string) $t['propose_category_help']) ?></p>
                    </div>
                    <button class="webotheque-proposal-dialog-close module-dialog-close" type="button" data-webotheque-modal-close aria-label="<?= e((string) $t['cancel']) ?>">&times;</button>
                </div>
                <form method="post" class="webotheque-proposal-form module-dialog-form">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="propose_domain">
                    <label><span><?= e((string) $t['category_name_field']) ?></span><input type="text" name="proposal_domain" maxlength="120" required></label>
                    <label><span><?= e((string) $t['proposal_details_field']) ?></span><textarea name="proposal_details" rows="4" maxlength="1200"></textarea></label>
                    <label><span><?= e((string) $t['contact_field']) ?></span><input type="email" name="proposal_contact" maxlength="220" value="<?= e($proposalContact) ?>"></label>
                    <p class="webotheque-proposal-dialog-actions module-dialog-actions">
                        <button class="button" type="submit"><?= e((string) $t['submit_proposal']) ?></button>
                        <button class="button secondary" type="button" data-webotheque-modal-close><?= e((string) $t['cancel']) ?></button>
                    </p>
                </form>
            </div>
        </dialog>

        <dialog class="webotheque-proposal-dialog" id="webotheque-tag-dialog" aria-labelledby="webotheque-tag-title"<?= $showTagProposalForm ? ' open data-webotheque-auto-open' : '' ?>>
            <div class="webotheque-proposal-dialog-card">
                <div class="webotheque-proposal-dialog-header module-dialog-header">
                    <div>
                        <p class="eyebrow"><?= e((string) $t['title']) ?></p>
                        <h2 id="webotheque-tag-title"><?= e((string) $t['propose_tag']) ?></h2>
                        <p class="help"><?= e($canAutoValidate ? (string) $t['proposal_auto_accept_help'] : (string) $t['propose_tag_help']) ?></p>
                    </div>
                    <button class="webotheque-proposal-dialog-close module-dialog-close" type="button" data-webotheque-modal-close aria-label="<?= e((string) $t['cancel']) ?>">&times;</button>
                </div>
                <form method="post" class="webotheque-proposal-form module-dialog-form">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="propose_tag">
                    <label><span><?= e((string) $t['tag_name_field']) ?></span><input type="text" name="proposal_tag" maxlength="80" required></label>
                    <label><span><?= e((string) $t['proposal_details_field']) ?></span><textarea name="proposal_details" rows="4" maxlength="1200"></textarea></label>
                    <label><span><?= e((string) $t['contact_field']) ?></span><input type="email" name="proposal_contact" maxlength="220" value="<?= e($proposalContact) ?>"></label>
                    <p class="webotheque-proposal-dialog-actions module-dialog-actions">
                        <button class="button" type="submit"><?= e((string) $t['submit_proposal']) ?></button>
                        <button class="button secondary" type="button" data-webotheque-modal-close><?= e((string) $t['cancel']) ?></button>
                    </p>
                </form>
            </div>
        </dialog>

        <section class="card webotheque-search-panel">
            <form method="get" class="inline-form webotheque-search-form">
                <input type="hidden" name="route" value="webotheque">
                <?php if ($categoryFilter !== ''): ?>
                    <input type="hidden" name="category" value="<?= e($categoryFilter) ?>">
                <?php endif; ?>
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $t['search_ph']) ?>">
                <button class="button" type="submit"><?= e((string) $t['search']) ?></button>
                <?php if ($search !== '' || $categoryFilter !== ''): ?>
                    <a class="button secondary" href="<?= e(route_url('webotheque')) ?>"><?= e((string) $t['reset']) ?></a>
                <?php endif; ?>
            </form>
        </section>

        <section class="webotheque-layout module-taxonomy-layout">
            <aside class="card webotheque-domains-column module-taxonomy-index">
                <p class="webotheque-domains-title module-taxonomy-title"><?= e((string) ($t['topics'] ?? $t['category_field'])) ?></p>
                <nav class="webotheque-domains-list module-taxonomy-list" aria-label="<?= e((string) ($t['topics'] ?? $t['category_field'])) ?>">
                    <a class="webotheque-domain-item module-taxonomy-item<?= $categoryFilter === '' ? ' is-active' : '' ?>" href="<?= e(route_url_clean('webotheque', ['q' => $search])) ?>"<?= $categoryFilter === '' ? ' aria-current="page"' : '' ?>>
                        <span><?= e((string) $t['all_categories']) ?></span>
                        <strong><?= (int) ($stats['total'] ?? 0) ?></strong>
                    </a>
                    <?php foreach ($categories as $code => $label): ?>
                        <a class="webotheque-domain-item module-taxonomy-item<?= $categoryFilter === $code ? ' is-active' : '' ?>" href="<?= e(route_url_clean('webotheque', ['q' => $search, 'category' => (string) $code])) ?>"<?= $categoryFilter === $code ? ' aria-current="page"' : '' ?>>
                            <span><?= e((string) $label) ?></span>
                            <strong><?= (int) ($stats['by_category'][$code] ?? 0) ?></strong>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </aside>

            <section id="webotheque-list" class="webotheque-content module-taxonomy-content">
                <?php if ($links === []): ?>
                    <div class="card"><p><?= e((string) $t['empty']) ?><?php if ($search !== '' || $categoryFilter !== ''): ?><?= e((string) $t['for_filters']) ?>.<?php endif; ?></p></div>
                <?php else: ?>
                    <div class="news-grid webotheque-grid"><?= render_webotheque_cards($links, $t, $categories) ?></div>
                <?php endif; ?>
            </section>
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

    $categories = webotheque_default_categories($t) + webotheque_categories($t);
    $isFrench = $locale === 'fr';
    $adminText = static function (string $key, string $fr, string $en) use ($t, $isFrench): string {
        return (string) ($t[$key] ?? ($isFrench ? $fr : $en));
    };
    $pendingProposalUrl = route_url_clean('admin_webotheque', ['status' => 'pending']) . '#pending-proposals';
    $proposalStatusLabels = [
        'pending' => $adminText('proposal_status_pending', 'En attente', 'Pending'),
        'reviewed' => $adminText('proposal_status_reviewed', 'Relue', 'Reviewed'),
        'accepted' => $adminText('proposal_status_accepted', 'Acceptée', 'Accepted'),
        'rejected' => $adminText('proposal_status_rejected', 'Refusée', 'Rejected'),
    ];
    $proposalTypeLabels = [
        'domain' => $adminText('proposal_type_domain', 'Thématique', 'Topic'),
        'category' => $adminText('proposal_type_domain', 'Thématique', 'Topic'),
        'content' => $adminText('proposal_type_content', 'Lien', 'Link'),
        'tag' => $adminText('proposal_type_tag', 'Mot clé', 'Keyword'),
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            verify_csrf();
            $action = (string) ($_POST['action'] ?? 'add_link');
            if ($action === 'update_proposal_status') {
                $proposalId = (int) ($_POST['proposal_id'] ?? 0);
                $proposalStatus = (string) ($_POST['proposal_status'] ?? 'pending');
                $moderationNote = trim((string) ($_POST['moderation_note'] ?? ''));
                if ($proposalId <= 0 || !isset($proposalStatusLabels[$proposalStatus])) {
                    throw new RuntimeException('err_required');
                }
                if (!ensure_content_proposals_table()) {
                    throw new RuntimeException('storage_unavailable');
                }

                $proposalStmt = db()->prepare('SELECT id, member_id, proposal_type, title, summary, source_ref FROM content_proposals WHERE id = ? AND area = "webotheque" LIMIT 1');
                $proposalStmt->execute([$proposalId]);
                $proposal = $proposalStmt->fetch() ?: null;
                if (!is_array($proposal)) {
                    throw new RuntimeException('err_required');
                }

                if ($proposalStatus === 'accepted' && (string) ($proposal['proposal_type'] ?? '') === 'content') {
                    $sourceUrl = webotheque_proposal_source_url((string) ($proposal['source_ref'] ?? ''));
                    if ($sourceUrl === '') {
                        throw new RuntimeException('err_url');
                    }
                    $proposalCategory = webotheque_category_from_input((string) ($_POST['proposal_category'] ?? 'general'), $categories);
                    $summary = (string) ($proposal['summary'] ?? '');
                    $description = webotheque_proposal_detail_from_summary($summary, [(string) ($t['description_field'] ?? 'Description'), 'Description']);
                    if ($description === '') {
                        $description = $summary;
                    }
                    $tags = webotheque_proposal_detail_from_summary($summary, [(string) ($t['tags_field'] ?? 'Tags'), 'Tags', 'Étiquettes', 'Etiquettes']);
                    $existingStmt = db()->prepare('SELECT id FROM member_webotheque_links WHERE url = ? LIMIT 1');
                    $existingStmt->execute([$sourceUrl]);
                    if (!$existingStmt->fetch()) {
                        webotheque_insert_link((int) ($proposal['member_id'] ?? ($user['id'] ?? 0)), $proposalCategory, (string) ($proposal['title'] ?? ''), $sourceUrl, $description, $tags);
                    }
                }

                db()->prepare('UPDATE content_proposals SET status = ?, moderation_note = ? WHERE id = ? AND area = "webotheque"')
                    ->execute([$proposalStatus, $moderationNote !== '' ? $moderationNote : null, $proposalId]);
                set_flash('success', $adminText('proposal_status_saved', 'Proposition mise à jour.', 'Proposal updated.'));
                redirect_url($pendingProposalUrl);
            }
            if ($action === 'delete_link') {
                $id = (int) ($_POST['id'] ?? 0);
                db()->prepare('DELETE FROM member_webotheque_links WHERE id = ? LIMIT 1')->execute([$id]);
                set_flash('success', (string) $t['ok_deleted']);
                redirect('admin_webotheque');
            }

            $title = content_proposal_clean_single_line((string) ($_POST['title'] ?? ''), 190);
            $url = webotheque_normalize_url((string) ($_POST['url'] ?? ''));
            $description = content_proposal_clean_multiline((string) ($_POST['description'] ?? ''), 5000);
            $tags = content_proposal_clean_single_line((string) ($_POST['tags'] ?? ''), 255);
            $category = webotheque_category_from_input((string) ($_POST['category'] ?? ''), $categories);
            if ($title === '' || $url === '') {
                throw new RuntimeException($url === '' ? 'err_url' : 'err_required');
            }
            $summary = webotheque_link_summary($t, (string) ($categories[$category] ?? $category), $description, $tags);
            content_proposal_create((int) ($user['id'] ?? 0), 'webotheque', 'content', $title, $summary, webotheque_member_contact($user ?? []), $url, 'accepted');
            webotheque_insert_link((int) ($user['id'] ?? 0), $category, $title, $url, $description, $tags);
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
    $categoryFilter = '';
    $categoryInput = trim((string) ($_GET['category'] ?? ''));
    if ($categoryInput !== '') {
        $categoryCode = webotheque_category_code($categoryInput);
        if (isset($categories[$categoryCode])) {
            $categoryFilter = $categoryCode;
        }
    }
    $showAdminLinkProposalForm = (string) ($_GET['propose_link'] ?? '') === '1';
    $stats = webotheque_stats();
    $links = webotheque_fetch_links($search, $categoryFilter, 120);
    $showPendingProposals = (string) ($_GET['status'] ?? '') === 'pending';
    $pendingProposals = [];
    if ($showPendingProposals && ensure_content_proposals_table()) {
        $pendingStmt = db()->prepare(
            'SELECT cp.id, cp.member_id, cp.proposal_type, cp.title, cp.summary, cp.contact, cp.source_ref, cp.status, cp.moderation_note, cp.created_at, cp.updated_at, m.callsign, m.email
             FROM content_proposals cp
             LEFT JOIN members m ON m.id = cp.member_id
             WHERE cp.area = "webotheque" AND cp.status = "pending"
             ORDER BY cp.created_at ASC, cp.id ASC'
        );
        $pendingStmt->execute();
        $pendingProposals = $pendingStmt->fetchAll() ?: [];
    }

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
                    <article><span><?= e((string) $t['tags']) ?></span><strong><?= (int) ($stats['tags'] ?? 0) ?></strong></article>
                    <article><span><?= e((string) $t['domains']) ?></span><strong><?= (int) ($stats['domains'] ?? 0) ?></strong></article>
                </div>
                <p class="actions admin-webotheque-hero-actions">
                    <a class="button secondary" href="<?= e(route_url('webotheque')) ?>"><?= e((string) $t['view_links']) ?></a>
                    <a class="button" href="<?= e(route_url('admin_webotheque', ['propose_link' => '1'])) ?>" data-webotheque-modal-open="admin-webotheque-link-dialog" aria-haspopup="dialog" aria-controls="admin-webotheque-link-dialog"><?= e((string) $t['propose_link']) ?></a>
                </p>
            </div>
        </section>

        <?php if ($showPendingProposals): ?>
        <section class="admin-webotheque-list" id="pending-proposals" aria-labelledby="pending-proposals-title">
            <div class="row-between">
                <h2 id="pending-proposals-title"><?= e($adminText('pending_proposals_title', 'Contenus en attente de validation', 'Content pending review')) ?></h2>
                <a class="button secondary" href="<?= e(route_url('admin_webotheque')) ?>"><?= e((string) $t['reset']) ?></a>
            </div>
            <?php if ($pendingProposals === []): ?>
                <div class="card"><p><?= e($adminText('pending_proposals_empty', 'Aucun contenu webothèque en attente de validation.', 'No web library content is pending review.')) ?></p></div>
            <?php endif; ?>
            <?php foreach ($pendingProposals as $proposal): ?>
                <?php
                $proposalType = (string) ($proposal['proposal_type'] ?? 'content');
                $proposalStatus = (string) ($proposal['status'] ?? 'pending');
                $sourceUrl = webotheque_proposal_source_url((string) ($proposal['source_ref'] ?? ''));
                $memberLabel = trim((string) ($proposal['callsign'] ?? ''));
                if ($memberLabel === '') {
                    $memberLabel = trim((string) ($proposal['email'] ?? ''));
                }
                if ($memberLabel === '') {
                    $memberLabel = '#' . (int) ($proposal['member_id'] ?? 0);
                }
                $proposalCreatedTimestamp = strtotime((string) ($proposal['created_at'] ?? 'now'));
                if ($proposalCreatedTimestamp === false) {
                    $proposalCreatedTimestamp = time();
                }
                $proposalCategory = webotheque_proposal_category_from_summary((string) ($proposal['summary'] ?? ''), $categories);
                ?>
                <article class="news-card feature-card webotheque-card">
                    <p>
                        <span class="badge muted"><?= e((string) ($proposalTypeLabels[$proposalType] ?? $proposalType)) ?></span>
                        <span class="badge muted"><?= e((string) ($proposalStatusLabels[$proposalStatus] ?? $proposalStatus)) ?></span>
                        <span class="badge muted"><?= e(date('d/m/Y H:i', $proposalCreatedTimestamp)) ?></span>
                    </p>
                    <h3><?= e((string) ($proposal['title'] ?? $adminText('proposal_default_title', 'Proposition', 'Proposal'))) ?></h3>
                    <p class="help"><?= e($adminText('proposal_author', 'Proposé par', 'Proposed by')) ?>: <?= e($memberLabel) ?></p>
                    <?php if (trim((string) ($proposal['summary'] ?? '')) !== ''): ?>
                        <p><?= nl2br(e((string) $proposal['summary'])) ?></p>
                    <?php endif; ?>
                    <?php if (trim((string) ($proposal['contact'] ?? '')) !== ''): ?>
                        <p class="help"><?= e($adminText('proposal_contact', 'Contact', 'Contact')) ?>: <?= e((string) $proposal['contact']) ?></p>
                    <?php endif; ?>
                    <form method="post" class="webotheque-proposal-form module-dialog-form">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="update_proposal_status">
                        <input type="hidden" name="proposal_id" value="<?= (int) ($proposal['id'] ?? 0) ?>">
                        <label>
                            <span><?= e($adminText('proposal_status_label', 'Statut', 'Status')) ?></span>
                            <select name="proposal_status">
                                <?php foreach ($proposalStatusLabels as $statusCode => $statusLabel): ?>
                                    <option value="<?= e($statusCode) ?>"<?= $proposalStatus === $statusCode ? ' selected' : '' ?>><?= e($statusLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <?php if ($proposalType === 'content'): ?>
                            <label>
                                <span><?= e((string) ($t['domain_field'] ?? $t['category_field'])) ?></span>
                                <select name="proposal_category">
                                    <?php foreach ($categories as $code => $label): ?>
                                        <option value="<?= e((string) $code) ?>"<?= $proposalCategory === $code ? ' selected' : '' ?>><?= e((string) $label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        <?php endif; ?>
                        <label>
                            <span><?= e($adminText('proposal_moderation_note', 'Note de modération', 'Moderation note')) ?></span>
                            <textarea name="moderation_note" rows="3"><?= e((string) ($proposal['moderation_note'] ?? '')) ?></textarea>
                        </label>
                        <p class="actions">
                            <?php if ($sourceUrl !== ''): ?>
                                <a class="button secondary" href="<?= e($sourceUrl) ?>" target="_blank" rel="noopener noreferrer"><?= e($adminText('proposal_open_source', 'Ouvrir la source', 'Open source')) ?></a>
                            <?php endif; ?>
                            <button class="button" type="submit"><?= e($adminText('proposal_save_status', 'Enregistrer le statut', 'Save status')) ?></button>
                        </p>
                    </form>
                </article>
            <?php endforeach; ?>
        </section>
        <?php endif; ?>

        <dialog class="webotheque-proposal-dialog" id="admin-webotheque-link-dialog" aria-labelledby="admin-webotheque-link-title"<?= $showAdminLinkProposalForm ? ' open data-webotheque-auto-open' : '' ?>>
            <div class="webotheque-proposal-dialog-card">
                <div class="webotheque-proposal-dialog-header module-dialog-header">
                    <div>
                        <p class="eyebrow"><?= e((string) $t['administration']) ?></p>
                        <h2 id="admin-webotheque-link-title"><?= e((string) $t['propose_link']) ?></h2>
                        <p class="help"><?= e((string) $t['proposal_auto_accept_help']) ?></p>
                    </div>
                    <button class="webotheque-proposal-dialog-close module-dialog-close" type="button" data-webotheque-modal-close aria-label="<?= e((string) $t['cancel']) ?>">&times;</button>
                </div>
                <form method="post" class="webotheque-proposal-form module-dialog-form">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="add_link">
                    <?= render_webotheque_link_fields($t, $categories) ?>
                    <p class="webotheque-proposal-dialog-actions module-dialog-actions">
                        <button class="button" type="submit"><?= e((string) $t['save']) ?></button>
                        <button class="button secondary" type="button" data-webotheque-modal-close><?= e((string) $t['cancel']) ?></button>
                    </p>
                </form>
            </div>
        </dialog>

        <section class="card webotheque-search-panel">
            <form method="get" class="inline-form webotheque-search-form">
                <input type="hidden" name="route" value="admin_webotheque">
                <?php if ($categoryFilter !== ''): ?>
                    <input type="hidden" name="category" value="<?= e($categoryFilter) ?>">
                <?php endif; ?>
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $t['search_ph']) ?>">
                <button class="button" type="submit"><?= e((string) $t['search']) ?></button>
                <?php if ($search !== '' || $categoryFilter !== ''): ?>
                    <a class="button secondary" href="<?= e(route_url('admin_webotheque')) ?>"><?= e((string) $t['reset']) ?></a>
                <?php endif; ?>
            </form>
        </section>

        <?php if (count($categories) > 1): ?>
            <nav class="classifieds-category-strip webotheque-category-filter" aria-label="<?= e((string) ($t['domain_field'] ?? $t['category_field'])) ?>">
                <a class="classifieds-category-pill<?= $categoryFilter === '' ? ' is-active' : '' ?>" href="<?= e(route_url_clean('admin_webotheque', ['q' => $search])) ?>"><?= e((string) $t['all_categories']) ?></a>
                <?php foreach ($categories as $code => $label): ?>
                    <a class="classifieds-category-pill<?= $categoryFilter === $code ? ' is-active' : '' ?>" href="<?= e(route_url_clean('admin_webotheque', ['q' => $search, 'category' => (string) $code])) ?>"><?= e((string) $label) ?></a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

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
                            <?php $linkCategory = webotheque_category_code((string) ($link['category'] ?? 'general')); ?>
                            <p class="help"><?= e((string) ($t['domain_field'] ?? $t['category_field'])) ?>: <?= e((string) ($categories[$linkCategory] ?? webotheque_category_label_from_code($linkCategory))) ?></p>
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
