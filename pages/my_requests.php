<?php
declare(strict_types=1);

$user = require_login();
$locale = current_locale();
$t = i18n_domain_locale('my_requests', $locale);
$text = static fn (string $key, ?string $fallback = null): string => (string) ($t[$key] ?? $fallback ?? $key);
$memberAreaLabel = member_area_eyebrow_label($locale);
$isFrench = $locale === 'fr';

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

$formatRequestDate = static function (mixed $value): string {
    $timestamp = strtotime((string) ($value ?: 'now'));
    if ($timestamp === false) {
        $timestamp = time();
    }

    return date('d/m/Y', $timestamp);
};

$timestampFor = static function (mixed $value): int {
    $timestamp = strtotime((string) ($value ?: 'now'));

    return $timestamp !== false ? $timestamp : time();
};

$statusLabels = [
    'draft' => $text('article_status_draft', 'Draft'),
    'pending' => $text('article_status_pending', 'Pending review'),
    'scheduled' => $text('article_status_scheduled', 'Scheduled'),
    'published' => $text('article_status_published', 'Published'),
    'active' => $text('classified_status_active', 'Active'),
    'sold' => $text('classified_status_sold', $isFrench ? 'Vendue' : 'Sold'),
    'archived' => $text('classified_status_archived', $isFrench ? 'Archivee' : 'Archived'),
    'expired' => $text('classified_status_expired', $isFrench ? 'Expiree' : 'Expired'),
    'rejected' => $text('article_status_rejected', 'Rejected'),
    'reviewed' => $text('proposal_status_reviewed', $isFrench ? 'Relue' : 'Reviewed'),
    'accepted' => $text('proposal_status_accepted', $isFrench ? 'Acceptee' : 'Accepted'),
];

$proposalAreaLabels = [
    'articles' => $text('articles_title', 'Articles'),
    'albums' => $text('albums_title', 'Albums'),
    'auctions' => $text('auctions_title', $isFrench ? 'Encheres' : 'Auctions'),
    'classifieds' => $text('classifieds_title', 'Classifieds'),
    'events' => $text('events_title', 'Events'),
    'members_library' => $text('library_title', $isFrench ? 'Bibliothèque membres' : 'Member library'),
    'news' => $text('news_title', $isFrench ? 'Actualités' : 'News'),
    'fichiers' => $text('files_title', $isFrench ? 'Fichiers' : 'Files'),
    'presentations' => $text('presentations_title', $isFrench ? 'Presentations' : 'Presentations'),
    'pv' => $text('minutes_title', $isFrench ? 'Proces verbaux' : 'Minutes'),
    'videos' => $text('videos_title', $isFrench ? 'Videos' : 'Videos'),
    'webotheque' => $text('webotheque_title', $isFrench ? 'Webothèque' : 'Web library'),
    'wiki' => $text('wiki_title', 'Wiki'),
];
$proposalTypeLabels = [
    'category' => $text('proposal_type_category', $isFrench ? 'Proposition de categorie' : 'Category proposal'),
    'content' => $text('proposal_type_content', $isFrench ? 'Proposition de contenu' : 'Content proposal'),
    'domain' => $text('proposal_type_domain', $isFrench ? 'Proposition de domaine' : 'Domain proposal'),
    'tag' => $text('proposal_type_tag', $isFrench ? 'Proposition d etiquette' : 'Tag proposal'),
    'subcategory' => $text('proposal_type_subcategory', $isFrench ? 'Proposition de sous-thematique' : 'Subtopic proposal'),
];
$proposalAreaRoutes = [
    'articles' => 'articles',
    'albums' => 'albums',
    'auctions' => 'auctions',
    'classifieds' => 'classifieds',
    'events' => 'events',
    'members_library' => 'members_library',
    'news' => 'news',
    'fichiers' => 'fichiers',
    'presentations' => 'presentations',
    'pv' => 'pv',
    'videos' => 'videos',
    'webotheque' => 'webotheque',
    'wiki' => 'wiki',
];

$cards = [];
$directDocumentSourceRefs = [];
$normalizeDocumentSourceRef = static function (string $value): string {
    $value = rawurldecode(trim(str_replace('\\', '/', $value)));
    if ($value === '') {
        return '';
    }
    if (preg_match('~(storage/(?:private|uploads)/(?:library|member_modules)/[^\s?#]+)~i', $value, $matches) === 1) {
        return ltrim((string) $matches[1], '/');
    }

    return ltrim($value, '/');
};

if (function_exists('member_library_sync_accepted_proposals')) {
    try {
        member_library_sync_accepted_proposals(i18n_domain_locale('members_library', $locale));
    } catch (Throwable $throwable) {
        log_structured_event('my_requests_member_library_sync_failed', ['message' => $throwable->getMessage()]);
    }
}

$privacyRequests = privacy_member_requests((int) $user['id']);
foreach ($privacyRequests as $request) {
    $requestedAt = (string) ($request['requested_at'] ?? 'now');
    $notes = trim((string) ($request['admin_notes'] ?? ''));
    $cards[] = [
        'timestamp' => $timestampFor($requestedAt),
        'status' => (string) ($request['status'] ?? 'pending'),
        'title' => $text('privacy_request_prefix') . ': ' . (string) ($request['request_type'] ?? 'access'),
        'meta' => $text('privacy_title'),
        'date' => $formatRequestDate($requestedAt),
        'note' => $notes,
        'url' => route_url('gdpr'),
        'cta' => $text('privacy_cta'),
    ];
}

if (table_exists('articles') && table_has_column('articles', 'author_id')) {
    try {
        $stmt = db()->prepare('SELECT id, slug, title, status, category, moderation_note, created_at, updated_at FROM articles WHERE author_id = ? ORDER BY updated_at DESC, id DESC LIMIT 50');
        $stmt->execute([(int) $user['id']]);
        foreach (($stmt->fetchAll() ?: []) as $article) {
            $articleStatus = (string) ($article['status'] ?? 'draft');
            $articleTitle = trim((string) ($article['title'] ?? ''));
            if ($articleTitle === '') {
                $articleTitle = $text('article_default_title', 'Article');
            }
            $articleUrl = $articleStatus === 'published' && trim((string) ($article['slug'] ?? '')) !== ''
                ? route_url('article', ['slug' => (string) $article['slug']])
                : '';
            $updatedAt = (string) ($article['updated_at'] ?? $article['created_at'] ?? 'now');
            $cards[] = [
                'timestamp' => $timestampFor($updatedAt),
                'status' => (string) ($statusLabels[$articleStatus] ?? $articleStatus),
                'title' => $articleTitle,
                'meta' => $text('articles_title') . ' / ' . (string) ($article['category'] ?? $text('category_default', 'Other')),
                'date' => $formatRequestDate($updatedAt),
                'note' => trim((string) ($article['moderation_note'] ?? '')) !== '' ? $text('moderation_note', 'Moderation note') . ': ' . (string) $article['moderation_note'] : '',
                'url' => $articleUrl,
                'cta' => $text('article_open', 'Open'),
            ];
        }
    } catch (Throwable $throwable) {
        log_structured_event('my_requests_articles_load_failed', ['message' => $throwable->getMessage()]);
    }
}

if (table_exists('wiki_pages') && table_has_column('wiki_pages', 'author_id')) {
    try {
        $stmt = db()->prepare('SELECT id, slug, title, status, created_at, updated_at FROM wiki_pages WHERE author_id = ? ORDER BY updated_at DESC, id DESC LIMIT 50');
        $stmt->execute([(int) $user['id']]);
        foreach (($stmt->fetchAll() ?: []) as $wikiPage) {
            $wikiStatus = (string) ($wikiPage['status'] ?? 'pending');
            $updatedAt = (string) ($wikiPage['updated_at'] ?? $wikiPage['created_at'] ?? 'now');
            $cards[] = [
                'timestamp' => $timestampFor($updatedAt),
                'status' => (string) ($statusLabels[$wikiStatus] ?? $wikiStatus),
                'title' => trim((string) ($wikiPage['title'] ?? '')) !== '' ? (string) $wikiPage['title'] : $text('wiki_default_title', $isFrench ? 'Page wiki' : 'Wiki page'),
                'meta' => $text('wiki_title', 'Wiki'),
                'date' => $formatRequestDate($updatedAt),
                'note' => '',
                'url' => $wikiStatus === 'published' && trim((string) ($wikiPage['slug'] ?? '')) !== '' ? route_url('wiki_view', ['slug' => (string) $wikiPage['slug']]) : route_url('wiki'),
                'cta' => $text('content_open', $isFrench ? 'Ouvrir' : 'Open'),
            ];
        }
    } catch (Throwable $throwable) {
        log_structured_event('my_requests_wiki_load_failed', ['message' => $throwable->getMessage()]);
    }
}

if (table_exists('classified_ads') && table_has_column('classified_ads', 'owner_member_id')) {
    try {
        $stmt = db()->prepare('SELECT id, category_code, title, price_cents, status, expires_at, created_at, updated_at FROM classified_ads WHERE owner_member_id = ? ORDER BY updated_at DESC, id DESC LIMIT 50');
        $stmt->execute([(int) $user['id']]);
        foreach (($stmt->fetchAll() ?: []) as $ad) {
            $adStatus = (string) ($ad['status'] ?? 'draft');
            $updatedAt = (string) ($ad['updated_at'] ?? $ad['created_at'] ?? 'now');
            $price = function_exists('format_price_eur') ? format_price_eur((int) ($ad['price_cents'] ?? 0)) : '';
            $meta = $text('classifieds_title') . ' / ' . (string) ($ad['category_code'] ?? $text('category_default', 'Other'));
            if ($price !== '') {
                $meta .= ' / ' . $price;
            }
            $cards[] = [
                'timestamp' => $timestampFor($updatedAt),
                'status' => (string) ($statusLabels[$adStatus] ?? $adStatus),
                'title' => trim((string) ($ad['title'] ?? '')) !== '' ? (string) $ad['title'] : $text('classified_default_title', $isFrench ? 'Annonce' : 'Classified ad'),
                'meta' => $meta,
                'date' => $formatRequestDate($updatedAt),
                'note' => trim((string) ($ad['expires_at'] ?? '')) !== '' ? $text('expires_on', $isFrench ? 'Expire le' : 'Expires on') . ': ' . $formatRequestDate((string) $ad['expires_at']) : '',
                'url' => route_url('classifieds_manage', ['edit' => (int) ($ad['id'] ?? 0)]),
                'cta' => $text('content_manage', $isFrench ? 'Gerer' : 'Manage'),
            ];
        }
    } catch (Throwable $throwable) {
        log_structured_event('my_requests_classifieds_load_failed', ['message' => $throwable->getMessage()]);
    }
}

if (table_exists('member_library_documents')) {
    try {
        $stmt = db()->prepare('SELECT id, title, description, category, subcategory, tags, file_path, uploaded_at FROM member_library_documents WHERE member_id = ? ORDER BY uploaded_at DESC, id DESC LIMIT 50');
        $stmt->execute([(int) $user['id']]);
        foreach (($stmt->fetchAll() ?: []) as $document) {
            $titleValue = trim((string) ($document['title'] ?? ''));
            if ($titleValue === '') {
                $titleValue = $text('library_document_default_title', $isFrench ? 'Document' : 'Document');
            }
            $uploadedAt = (string) ($document['uploaded_at'] ?? 'now');
            $categoryValue = trim((string) ($document['category'] ?? ''));
            $subcategoryValue = trim((string) ($document['subcategory'] ?? ''));
            $sourceKey = $normalizeDocumentSourceRef((string) ($document['file_path'] ?? ''));
            if ($sourceKey !== '') {
                $directDocumentSourceRefs[$sourceKey] = true;
            }
            $urlQuery = ['q' => $titleValue];
            if ($categoryValue !== '') {
                $urlQuery['category'] = $categoryValue;
            }
            if ($subcategoryValue !== '') {
                $urlQuery['subcategory'] = $subcategoryValue;
            }
            $metaParts = [$text('library_title', $isFrench ? 'Bibliothèque membres' : 'Member library')];
            if ($categoryValue !== '') {
                $metaParts[] = $categoryValue;
            }
            if ($subcategoryValue !== '') {
                $metaParts[] = $subcategoryValue;
            }
            $cards[] = [
                'timestamp' => $timestampFor($uploadedAt),
                'status' => (string) ($statusLabels['published'] ?? ($isFrench ? 'Publie' : 'Published')),
                'title' => $titleValue,
                'meta' => implode(' / ', $metaParts),
                'date' => $formatRequestDate($uploadedAt),
                'note' => trim((string) ($document['description'] ?? '')),
                'url' => route_url_clean('members_library', $urlQuery),
                'cta' => $text('content_open', $isFrench ? 'Ouvrir' : 'Open'),
            ];
        }
    } catch (Throwable $throwable) {
        log_structured_event('my_requests_member_library_documents_load_failed', ['message' => $throwable->getMessage()]);
    }
}

if (table_exists('member_module_documents')) {
    try {
        $stmt = db()->prepare('SELECT id, module_code, title, description, category, subcategory, tags, file_path, uploaded_at FROM member_module_documents WHERE member_id = ? ORDER BY uploaded_at DESC, id DESC LIMIT 100');
        $stmt->execute([(int) $user['id']]);
        foreach (($stmt->fetchAll() ?: []) as $document) {
            $moduleCode = preg_replace('/[^a-z0-9_]/', '', strtolower((string) ($document['module_code'] ?? ''))) ?: '';
            if ($moduleCode === '') {
                continue;
            }
            $titleValue = trim((string) ($document['title'] ?? ''));
            if ($titleValue === '') {
                $titleValue = $text('document_default_title', $isFrench ? 'Document' : 'Document');
            }
            $uploadedAt = (string) ($document['uploaded_at'] ?? 'now');
            $categoryValue = trim((string) ($document['category'] ?? ''));
            $subcategoryValue = trim((string) ($document['subcategory'] ?? ''));
            $sourceKey = $normalizeDocumentSourceRef((string) ($document['file_path'] ?? ''));
            if ($sourceKey !== '') {
                $directDocumentSourceRefs[$sourceKey] = true;
            }
            $route = (string) ($proposalAreaRoutes[$moduleCode] ?? $moduleCode);
            $urlQuery = ['q' => $titleValue];
            if ($categoryValue !== '') {
                $urlQuery['category'] = $categoryValue;
            }
            if ($subcategoryValue !== '') {
                $urlQuery['subcategory'] = $subcategoryValue;
            }
            $metaParts = [(string) ($proposalAreaLabels[$moduleCode] ?? ucfirst(str_replace('_', ' ', $moduleCode)))];
            if ($categoryValue !== '') {
                $metaParts[] = $categoryValue;
            }
            if ($subcategoryValue !== '') {
                $metaParts[] = $subcategoryValue;
            }
            $cards[] = [
                'timestamp' => $timestampFor($uploadedAt),
                'status' => (string) ($statusLabels['published'] ?? ($isFrench ? 'Publie' : 'Published')),
                'title' => $titleValue,
                'meta' => implode(' / ', $metaParts),
                'date' => $formatRequestDate($uploadedAt),
                'note' => trim((string) ($document['description'] ?? '')),
                'url' => route_url_clean($route, $urlQuery),
                'cta' => $text('content_open', $isFrench ? 'Ouvrir' : 'Open'),
            ];
        }
    } catch (Throwable $throwable) {
        log_structured_event('my_requests_member_module_documents_load_failed', ['message' => $throwable->getMessage()]);
    }
}

if (ensure_content_proposals_table()) {
    try {
        $stmt = db()->prepare('SELECT id, area, proposal_type, title, summary, contact, source_ref, status, moderation_note, created_at, updated_at FROM content_proposals WHERE member_id = ? ORDER BY updated_at DESC, id DESC LIMIT 100');
        $stmt->execute([(int) $user['id']]);
        foreach (($stmt->fetchAll() ?: []) as $proposal) {
            $area = (string) ($proposal['area'] ?? '');
            $proposalType = (string) ($proposal['proposal_type'] ?? 'content');
            $proposalStatus = (string) ($proposal['status'] ?? 'pending');
            $proposalTitle = trim((string) ($proposal['title'] ?? $text('proposal_default_title', $isFrench ? 'Proposition' : 'Proposal')));
            if ($proposalTitle === '') {
                $proposalTitle = $text('proposal_default_title', $isFrench ? 'Proposition' : 'Proposal');
            }
            $proposalSourceRef = trim((string) ($proposal['source_ref'] ?? ''));
            $proposalSourceKey = $normalizeDocumentSourceRef($proposalSourceRef);
            if (
                $proposalStatus === 'accepted'
                && $proposalType === 'content'
                && $proposalSourceKey !== ''
                && isset($directDocumentSourceRefs[$proposalSourceKey])
            ) {
                continue;
            }
            $updatedAt = (string) ($proposal['updated_at'] ?? $proposal['created_at'] ?? 'now');
            $route = (string) ($proposalAreaRoutes[$area] ?? 'my_requests');
            $proposalUrl = route_url($route);
            if ($area === 'albums' && $proposalType === 'content' && $proposalSourceRef !== '') {
                $sourceQuery = [];
                parse_str((string) (parse_url($proposalSourceRef, PHP_URL_QUERY) ?: ''), $sourceQuery);
                $sourceAlbumId = (int) ($sourceQuery['id'] ?? 0);
                if (($sourceQuery['route'] ?? '') === 'album' && $sourceAlbumId > 0) {
                    $proposalUrl = route_url('album', ['id' => $sourceAlbumId]);
                }
            }
            if ($area === 'members_library' && $proposalType === 'content' && $proposalStatus === 'accepted') {
                $proposalUrl = route_url_clean('members_library', ['q' => $proposalTitle]);
            }
            if ($area === 'albums' && $proposalType === 'content' && $proposalStatus === 'accepted' && $proposalUrl === route_url('albums')) {
                $proposalUrl = route_url_clean('albums', ['q' => $proposalTitle]);
            }
            if (in_array($area, ['presentations', 'videos'], true) && $proposalType === 'content' && $proposalStatus === 'accepted') {
                $proposalUrl = route_url_clean($area, ['q' => $proposalTitle]);
            }
            if ($area === 'webotheque' && $proposalStatus === 'accepted') {
                $proposalUrl = in_array($proposalType, ['content', 'tag'], true)
                    ? route_url_clean('webotheque', ['q' => $proposalTitle])
                    : route_url_clean('webotheque', ['category' => $proposalTitle]);
            }
            $noteParts = [];
            if (trim((string) ($proposal['summary'] ?? '')) !== '') {
                $noteParts[] = trim((string) $proposal['summary']);
            }
            if (trim((string) ($proposal['moderation_note'] ?? '')) !== '') {
                $noteParts[] = $text('moderation_note', 'Moderation note') . ': ' . trim((string) $proposal['moderation_note']);
            }
            $cards[] = [
                'timestamp' => $timestampFor($updatedAt),
                'status' => (string) ($statusLabels[$proposalStatus] ?? $proposalStatus),
                'title' => (string) ($proposalTypeLabels[$proposalType] ?? $proposalType) . ': ' . $proposalTitle,
                'meta' => (string) ($proposalAreaLabels[$area] ?? $area),
                'date' => $formatRequestDate($updatedAt),
                'note' => implode("\n", $noteParts),
                'url' => $proposalUrl,
                'cta' => $text('content_open', $isFrench ? 'Ouvrir' : 'Open'),
            ];
        }
    } catch (Throwable $throwable) {
        log_structured_event('my_requests_proposals_load_failed', ['message' => $throwable->getMessage()]);
    }
}

usort($cards, static fn (array $a, array $b): int => ((int) ($b['timestamp'] ?? 0)) <=> ((int) ($a['timestamp'] ?? 0)));
$registeredRequestsCount = count($cards);

ob_start();
?>
<div class="my-requests-page stack">
    <section class="card my-requests-hero member-module-hero">
        <div>
            <p class="eyebrow"><?= e($memberAreaLabel) ?></p>
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
        <?php if ($cards === []): ?>
            <div class="my-requests-empty">
                <strong><?= e($text('empty_title')) ?></strong>
                <p><?= e($text('empty_hint')) ?></p>
            </div>
        <?php else: ?>
            <div class="my-requests-grid">
                <?php foreach ($cards as $card): ?>
                    <article class="my-requests-shortcut">
                        <span class="badge muted"><?= e((string) ($card['status'] ?? '')) ?></span>
                        <h3><?= e((string) ($card['title'] ?? '')) ?></h3>
                        <p><?= e((string) ($card['meta'] ?? '')) ?></p>
                        <p class="help"><?= e((string) ($card['date'] ?? '')) ?></p>
                        <?php if (trim((string) ($card['note'] ?? '')) !== ''): ?>
                            <p class="help"><?= nl2br(e((string) $card['note'])) ?></p>
                        <?php endif; ?>
                        <?php if (trim((string) ($card['url'] ?? '')) !== ''): ?>
                            <a class="button secondary small" href="<?= e((string) $card['url']) ?>"><?= e((string) ($card['cta'] ?? $text('content_open', $isFrench ? 'Ouvrir' : 'Open'))) ?></a>
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
