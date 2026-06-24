<?php
declare(strict_types=1);

require_once __DIR__ . '/module_catalog.php';

/**
 * @return array{layout:string,title:string,lead:string,search_label:string,search_placeholder:string,search_cta:string,search_reset:string,empty:string}
 */
function admin_dashboard_translations(string $locale): array
{
    $i18n = i18n_domain_messages('admin');

    $resolved = [];
    foreach (array_keys($i18n['fr']) as $key) {
        $pool = [];
        foreach ($i18n as $lang => $translations) {
            if (isset($translations[$key]) && is_string($translations[$key])) {
                $pool[$lang] = $translations[$key];
            }
        }
        $resolved[$key] = i18n_localized_value($pool, $locale, 'fr');
    }

    return $resolved;
}

/**
 * @return array<int, array{route:string,title:string,desc:string,url:string,pending_count:int}>
 */
function admin_dashboard_cards(string $locale, int $userId, string $search = ''): array
{
    $needle = trim($search);
    $needle = $needle !== '' ? mb_safe_strtolower($needle) : '';

    return admin_cards_for_dashboard($locale, $userId, $needle);
}

/**
 * @return array<int, array{route:string,title:string,desc:string,url:string,pending_count:int}>
 */
function admin_cards_for_dashboard(string $locale, int $userId, string $searchNeedle = ''): array
{
    return cache_remember('admin_cards_' . $locale . '_' . $userId . '_' . hash('sha256', $searchNeedle), 30, static function () use ($locale, $searchNeedle): array {
        $cards = [];
        $pendingCounts = admin_pending_content_counts_by_route();
        foreach (admin_module_cards_catalog() as $card) {
            $module = (string) ($card['module'] ?? '');
            $permission = (string) ($card['permission'] ?? '');
            if ($module !== '' && !module_enabled($module)) {
                continue;
            }
            if ($permission !== '' && !has_permission($permission)) {
                continue;
            }
            $title = i18n_localized_value($card['title'], $locale, 'fr');
            $desc = i18n_localized_value($card['desc'], $locale, 'fr');
            if ($searchNeedle !== '') {
                $haystack = mb_safe_strtolower($title . ' ' . $desc);
                if (!str_contains($haystack, $searchNeedle)) {
                    continue;
                }
            }
            $route = (string) $card['route'];
            $pendingCount = (int) ($pendingCounts[$route] ?? 0);
            $cards[] = [
                'route' => $route,
                'title' => $title,
                'desc' => $desc,
                'url' => admin_pending_content_card_url($route, $pendingCount),
                'pending_count' => $pendingCount,
            ];
        }
        return $cards;
    });
}

/**
 * @return array<string, array{route:string,permission:string,label_key:string}>
 */
function admin_pending_content_areas(): array
{
    return [
        'articles' => ['route' => 'admin_articles', 'permission' => 'articles.manage', 'label_key' => 'pending_area_articles'],
        'albums' => ['route' => 'admin_albums', 'permission' => 'albums.manage', 'label_key' => 'pending_area_albums'],
        'auctions' => ['route' => 'admin_auctions', 'permission' => 'auctions.manage', 'label_key' => 'pending_area_auctions'],
        'classifieds' => ['route' => 'admin_classifieds', 'permission' => 'classifieds.moderate', 'label_key' => 'pending_area_classifieds'],
        'events' => ['route' => 'admin_events', 'permission' => 'events.manage', 'label_key' => 'pending_area_events'],
        'members_library' => ['route' => 'admin_library', 'permission' => 'admin.access', 'label_key' => 'pending_area_members_library'],
        'news' => ['route' => 'admin_news', 'permission' => 'news.moderate', 'label_key' => 'pending_area_news'],
        'presentations' => ['route' => 'admin_presentations', 'permission' => 'admin.access', 'label_key' => 'pending_area_presentations'],
        'videos' => ['route' => 'admin_videos', 'permission' => 'admin.access', 'label_key' => 'pending_area_videos'],
        'webotheque' => ['route' => 'admin_webotheque', 'permission' => 'admin.access', 'label_key' => 'pending_area_webotheque'],
        'wiki' => ['route' => 'admin_wiki', 'permission' => 'wiki.moderate', 'label_key' => 'pending_area_wiki'],
    ];
}

function admin_can_manage_pending_content_area(string $area): bool
{
    $definition = admin_pending_content_areas()[$area] ?? null;
    if (!is_array($definition)) {
        return false;
    }

    return has_permission((string) $definition['permission']);
}

function admin_pending_content_area_label(string $area, string $locale): string
{
    $definition = admin_pending_content_areas()[$area] ?? null;
    if (!is_array($definition)) {
        return $area;
    }

    $messages = i18n_domain_locale('admin', $locale);

    return (string) $messages[(string) $definition['label_key']];
}

function admin_pending_content_area_url(string $area): string
{
    $definition = admin_pending_content_areas()[$area] ?? null;
    if (!is_array($definition)) {
        return route_url('admin');
    }

    return route_url((string) $definition['route']);
}

/**
 * @return array<string, string>
 */
function admin_pending_content_proposal_status_labels(string $locale): array
{
    $messages = i18n_domain_locale('admin', $locale);

    return [
        'pending' => (string) $messages['proposal_status_pending'],
        'reviewed' => (string) $messages['proposal_status_reviewed'],
        'accepted' => (string) $messages['proposal_status_accepted'],
        'rejected' => (string) $messages['proposal_status_rejected'],
    ];
}

function admin_pending_content_card_url(string $route, int $pendingCount): string
{
    if ($pendingCount <= 0) {
        return route_url($route);
    }

    return match ($route) {
        'admin_articles', 'admin_library', 'admin_webotheque', 'admin_wiki' => route_url_clean($route, ['status' => 'pending']) . '#pending-proposals',
        'admin_classifieds' => route_url_clean($route, ['status' => 'pending']),
        'admin_ads' => route_url($route, ['refresh' => '1']),
        'admin_news', 'admin_privacy', 'admin_translation_reviews' => route_url($route),
        default => route_url('admin') . '#pending-proposals',
    };
}

/**
 * @return array<string, int>
 */
function admin_pending_content_counts_by_route(): array
{
    $counts = [];
    $addCount = static function (string $route, int $count) use (&$counts): void {
        if ($count <= 0) {
            return;
        }
        $counts[$route] = (int) ($counts[$route] ?? 0) + $count;
    };

    try {
        if (table_exists('content_proposals')) {
            $proposalRows = db()->query('SELECT area, COUNT(*) AS total FROM content_proposals WHERE status = "pending" GROUP BY area')->fetchAll() ?: [];
            foreach ($proposalRows as $row) {
                $area = (string) ($row['area'] ?? '');
                if (!admin_can_manage_pending_content_area($area)) {
                    continue;
                }
                $definition = admin_pending_content_areas()[$area] ?? null;
                if (is_array($definition)) {
                    $addCount((string) $definition['route'], (int) ($row['total'] ?? 0));
                }
            }
        }

        if (has_permission('articles.manage') && table_exists('articles')) {
            $addCount('admin_articles', (int) (db()->query('SELECT COUNT(*) FROM articles WHERE status = "pending"')->fetchColumn() ?: 0));
        }
        if (has_permission('wiki.moderate') && table_exists('wiki_pages')) {
            $addCount('admin_wiki', (int) (db()->query('SELECT COUNT(*) FROM wiki_pages WHERE status = "pending"')->fetchColumn() ?: 0));
        }
        if (has_permission('classifieds.moderate') && table_exists('classified_ads')) {
            $addCount('admin_classifieds', (int) (db()->query('SELECT COUNT(*) FROM classified_ads WHERE status = "pending"')->fetchColumn() ?: 0));
        }
        if (has_permission('ads.moderate') && table_exists('ads')) {
            $addCount('admin_ads', (int) (db()->query('SELECT COUNT(*) FROM ads WHERE status IN ("pending", "rejected", "paused")')->fetchColumn() ?: 0));
        }
        if (has_permission('news.moderate') && table_exists('news_posts')) {
            $addCount('admin_news', (int) (db()->query('SELECT COUNT(*) FROM news_posts WHERE status = "pending"')->fetchColumn() ?: 0));
        }
        if (has_permission('privacy.manage') && table_exists('privacy_requests')) {
            $addCount('admin_privacy', (int) (db()->query('SELECT COUNT(*) FROM privacy_requests WHERE status = "pending"')->fetchColumn() ?: 0));
        }
        if (has_permission('admin.access')) {
            $translationCount = 0;
            if (table_exists('news_translations')) {
                $translationCount += (int) (db()->query('SELECT COUNT(*) FROM news_translations WHERE status IN ("auto", "needs_review")')->fetchColumn() ?: 0);
            }
            if (table_exists('article_translations')) {
                $translationCount += (int) (db()->query('SELECT COUNT(*) FROM article_translations WHERE status IN ("auto", "needs_review")')->fetchColumn() ?: 0);
            }
            $addCount('admin_translation_reviews', $translationCount);
        }
    } catch (Throwable) {
        return $counts;
    }

    return $counts;
}

/**
 * @return list<array<string, mixed>>
 */
function admin_pending_content_proposals_for_dashboard(string $locale, int $limit = 80): array
{
    if (!ensure_content_proposals_table()) {
        return [];
    }

    try {
        $stmt = db()->prepare(
            'SELECT cp.id, cp.member_id, cp.area, cp.proposal_type, cp.title, cp.summary, cp.contact, cp.source_ref, cp.status, cp.moderation_note, cp.created_at, cp.updated_at, m.callsign, m.email
             FROM content_proposals cp
             LEFT JOIN members m ON m.id = cp.member_id
             WHERE cp.status = "pending"
             ORDER BY cp.created_at ASC, cp.id ASC
             LIMIT ' . max(1, $limit)
        );
        $stmt->execute();
    } catch (Throwable) {
        return [];
    }

    $rows = [];
    foreach (($stmt->fetchAll() ?: []) as $row) {
        $area = (string) ($row['area'] ?? '');
        if (!admin_can_manage_pending_content_area($area)) {
            continue;
        }
        $row['area_label'] = admin_pending_content_area_label($area, $locale);
        $row['area_url'] = admin_pending_content_area_url($area);
        $rows[] = $row;
    }

    return $rows;
}

function admin_update_content_proposal_status(int $proposalId, string $status, string $moderationNote, string $locale): void
{
    $labels = admin_pending_content_proposal_status_labels($locale);
    $messages = i18n_domain_locale('admin', $locale);
    if ($proposalId <= 0 || !isset($labels[$status])) {
        throw new RuntimeException((string) $messages['err_invalid_proposal']);
    }
    if (!ensure_content_proposals_table()) {
        throw new RuntimeException((string) $messages['proposal_storage_unavailable']);
    }

    $stmt = db()->prepare('SELECT id, member_id, area, proposal_type, title, summary, source_ref FROM content_proposals WHERE id = ? LIMIT 1');
    $stmt->execute([$proposalId]);
    $proposal = $stmt->fetch() ?: null;
    if (!is_array($proposal)) {
        throw new RuntimeException((string) $messages['proposal_not_found']);
    }
    $area = (string) ($proposal['area'] ?? '');
    if (!admin_can_manage_pending_content_area($area)) {
        throw new RuntimeException((string) $messages['insufficient_permission']);
    }

    if ($status === 'accepted') {
        admin_apply_accepted_content_proposal($proposal, $locale);
    }

    db()->prepare('UPDATE content_proposals SET status = ?, moderation_note = ? WHERE id = ?')
        ->execute([$status, $moderationNote !== '' ? $moderationNote : null, $proposalId]);
}

/**
 * @param array<string, mixed> $proposal
 */
function admin_apply_accepted_content_proposal(array $proposal, string $locale): void
{
    $area = (string) ($proposal['area'] ?? '');

    if ($area === 'events') {
        admin_apply_accepted_event_proposal($proposal);
        return;
    }

    if ($area === 'auctions') {
        admin_apply_accepted_auction_proposal($proposal);
        return;
    }

    if ($area === 'news') {
        admin_apply_accepted_news_proposal($proposal);
        return;
    }

    if ($area === 'articles') {
        admin_apply_accepted_article_taxonomy_proposal($proposal, $locale);
        return;
    }

    if ($area === 'albums') {
        require_once __DIR__ . '/album_helpers.php';

        album_apply_accepted_proposal($proposal);
        return;
    }

    if ($area === 'webotheque') {
        require_once __DIR__ . '/member_webotheque.php';

        $messages = webotheque_i18n($locale);
        $categories = webotheque_categories($messages);
        webotheque_apply_accepted_proposal($proposal, $categories, $messages);
        return;
    }

    if (in_array($area, ['presentations', 'videos'], true)) {
        require_once __DIR__ . '/member_module_documents.php';

        member_document_apply_accepted_proposal($proposal, $area);
        return;
    }

    if ($area !== 'members_library') {
        return;
    }

    require_once __DIR__ . '/member_library_helpers.php';
    require_once __DIR__ . '/article_import_helpers.php';

    member_library_apply_accepted_proposal(
        $proposal,
        i18n_domain_locale('members_library', $locale)
    );
}

/**
 * @param array<string, mixed> $proposal
 * @return list<string>
 */
function admin_content_proposal_summary_values(array $proposal): array
{
    $values = [];
    foreach (content_proposal_summary_rows((string) ($proposal['summary'] ?? '')) as $row) {
        $values[] = trim((string) ($row['value'] ?? ''));
    }

    return $values;
}

function admin_content_proposal_unique_slug(string $table, string $title, string $fallback, int $maxLength = 190): string
{
    if (!in_array($table, ['events', 'auction_lots', 'news_posts'], true)) {
        throw new RuntimeException('Invalid content proposal target.');
    }

    $base = slugify($title);
    if ($base === '' || $base === 'n-a') {
        $base = $fallback;
    }
    if (strlen($base) > $maxLength) {
        $base = rtrim(substr($base, 0, $maxLength), '-');
    }
    $base = $base !== '' ? $base : $fallback;

    $suffix = 1;
    do {
        $suffixText = $suffix <= 1 ? '' : '-' . $suffix;
        $candidate = $suffixText === ''
            ? $base
            : rtrim(substr($base, 0, max(1, $maxLength - strlen($suffixText))), '-') . $suffixText;
        $stmt = db()->prepare('SELECT id FROM ' . $table . ' WHERE slug = ? LIMIT 1');
        $stmt->execute([$candidate]);
        if (!$stmt->fetchColumn()) {
            return $candidate;
        }
        $suffix++;
    } while ($suffix < 10000);

    throw new RuntimeException('Cannot generate unique proposal slug.');
}

/**
 * @param array<string, mixed> $proposal
 */
function admin_apply_accepted_event_proposal(array $proposal): void
{
    if ((string) ($proposal['proposal_type'] ?? '') !== 'content' || !table_exists('events')) {
        return;
    }
    require_once __DIR__ . '/event_helpers.php';

    $title = content_proposal_clean_single_line((string) ($proposal['title'] ?? ''), 160);
    if ($title === '') {
        throw new RuntimeException('Invalid event proposal.');
    }
    $existingStmt = db()->prepare('SELECT id FROM events WHERE title = ? LIMIT 1');
    $existingStmt->execute([$title]);
    if ((int) ($existingStmt->fetchColumn() ?: 0) > 0) {
        return;
    }

    $values = admin_content_proposal_summary_values($proposal);
    $dateRaw = content_proposal_clean_single_line((string) ($values[0] ?? ''), 160);
    $startTs = $dateRaw !== '' ? strtotime($dateRaw) : false;
    if ($startTs === false) {
        throw new RuntimeException('Invalid event proposal date.');
    }

    $location = content_proposal_clean_single_line((string) ($values[1] ?? ''), 190);
    $descriptionText = content_proposal_clean_multiline((string) ($values[2] ?? ''), 1600);
    $slug = admin_content_proposal_unique_slug('events', $title, 'event');

    event_publish_club_event($slug, $title, $startTs, $descriptionText, $location);
}

/**
 * @param array<string, mixed> $proposal
 */
function admin_apply_accepted_auction_proposal(array $proposal): void
{
    if ((string) ($proposal['proposal_type'] ?? '') !== 'content' || !table_exists('auction_lots')) {
        return;
    }

    require_once __DIR__ . '/money_helpers.php';

    $title = content_proposal_clean_single_line((string) ($proposal['title'] ?? ''), 190);
    if ($title === '') {
        throw new RuntimeException('Invalid auction proposal.');
    }
    $existingStmt = db()->prepare('SELECT id FROM auction_lots WHERE title = ? LIMIT 1');
    $existingStmt->execute([$title]);
    if ((int) ($existingStmt->fetchColumn() ?: 0) > 0) {
        return;
    }

    $values = admin_content_proposal_summary_values($proposal);
    $summary = content_proposal_clean_multiline((string) ($values[0] ?? ''), 1000);
    $price = content_proposal_clean_single_line((string) ($values[1] ?? '0'), 64);
    $descriptionText = content_proposal_clean_multiline((string) ($values[2] ?? ''), 5000);
    $contact = content_proposal_clean_single_line((string) ($proposal['contact'] ?? ''), 220);
    $description = $descriptionText !== '' ? '<p>' . nl2br(e($descriptionText), false) . '</p>' : '';
    if ($contact !== '') {
        $description .= '<p><strong>Contact:</strong> ' . e($contact) . '</p>';
    }
    $startsAt = time();
    $endsAt = strtotime('+7 days', $startsAt) ?: ($startsAt + 7 * 86400);
    $slug = admin_content_proposal_unique_slug('auction_lots', $title, 'lot');

    db()->prepare('INSERT INTO auction_lots (slug, title, summary, description, image_url, starting_price_cents, reserve_price_cents, min_increment_cents, buy_now_price_cents, starts_at, ends_at, status) VALUES (?, ?, ?, ?, "", ?, NULL, 100, NULL, ?, ?, "active")')
        ->execute([
            $slug,
            $title,
            $summary !== '' ? $summary : mb_safe_strimwidth($descriptionText, 0, 280, '...'),
            sanitize_rich_html($description),
            max(0, parse_price_to_cents($price)),
            date('Y-m-d H:i:s', $startsAt),
            date('Y-m-d H:i:s', $endsAt),
        ]);
    cache_forget('auction_public_lots_60_v1');
}

/**
 * @param array<string, mixed> $proposal
 */
function admin_apply_accepted_news_proposal(array $proposal): void
{
    $proposalType = (string) ($proposal['proposal_type'] ?? '');
    if (!in_array($proposalType, ['category', 'content'], true)) {
        return;
    }

    require_once __DIR__ . '/news_helpers.php';

    $title = content_proposal_clean_single_line((string) ($proposal['title'] ?? ''), 190);
    if ($title === '') {
        throw new RuntimeException('Invalid news proposal.');
    }

    if ($proposalType === 'category') {
        if (!table_exists('news_sections')) {
            return;
        }
        $slug = news_slug_base($title, 100);
        db()->prepare(
            'INSERT INTO news_sections (slug, name, sort_order)
             VALUES (?, ?, 100)
             ON DUPLICATE KEY UPDATE name = VALUES(name)'
        )->execute([$slug, $title]);
        cache_forget('news_categories_v2');
        return;
    }

    if (!table_exists('news_posts')) {
        return;
    }
    $existingStmt = db()->prepare('SELECT id FROM news_posts WHERE title = ? LIMIT 1');
    $existingStmt->execute([$title]);
    if ((int) ($existingStmt->fetchColumn() ?: 0) > 0) {
        return;
    }

    $sectionId = news_default_section_id();
    if ($sectionId <= 0) {
        throw new RuntimeException('News storage unavailable.');
    }

    $values = admin_content_proposal_summary_values($proposal);
    $summaryText = content_proposal_clean_multiline((string) ($values[0] ?? ''), 1800);
    $sourceText = content_proposal_clean_single_line((string) ($proposal['source_ref'] ?? $values[1] ?? ''), 500);
    if ($summaryText === '') {
        throw new RuntimeException('Invalid news proposal.');
    }

    $content = news_content_html_from_summary($summaryText, $sourceText);

    $slug = admin_content_proposal_unique_slug('news_posts', $title, 'news');
    db()->prepare('INSERT INTO news_posts (section_id, author_id, slug, title, excerpt, content, status, published_at) VALUES (?, ?, ?, ?, ?, ?, "published", NOW())')
        ->execute([
            $sectionId,
            max(0, (int) ($proposal['member_id'] ?? 0)),
            $slug,
            $title,
            mb_safe_strimwidth($summaryText, 0, 280, '...'),
            $content,
        ]);
    $postId = (int) db()->lastInsertId();
    if ($postId > 0 && function_exists('news_translations_sync_all')) {
        news_translations_sync_all($postId);
    }
    cache_forget('news_published_count_v1');
    cache_forget('news_categories_v2');
    cache_forget('news_archives_v1');
    cache_forget('home_latest_news_v1');
}

/**
 * @param array<string, mixed> $proposal
 */
function admin_apply_accepted_article_taxonomy_proposal(array $proposal, string $locale): void
{
    if ((string) ($proposal['proposal_type'] ?? '') !== 'category') {
        return;
    }

    require_once __DIR__ . '/article_helpers.php';

    $title = content_proposal_clean_single_line((string) ($proposal['title'] ?? ''), 160);
    if ($title === '' || !article_ensure_categories_table(i18n_domain_locale('articles', $locale))) {
        return;
    }
    $code = article_category_code($title);
    if ($code === '') {
        return;
    }

    db()->prepare('INSERT INTO article_categories (code, label, deleted_at) VALUES (?, ?, NULL) ON DUPLICATE KEY UPDATE label = VALUES(label), deleted_at = NULL')
        ->execute([$code, $title]);
}
