<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');

$buildUrl = static function (string $route, array $query = []): string {
    return route_url($route, $query);
};

$plainText = static function (string $value, int $limit = 240): string {
    $plain = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    return $plain !== '' ? mb_safe_strimwidth($plain, 0, $limit, '...') : '';
};

$recent = [
    'news' => [],
    'articles' => [],
    'events' => [],
    'wiki' => [],
];

if (module_enabled('news') && table_exists('news_posts')) {
    try {
        $rows = db()->query('SELECT slug, title, excerpt, published_at, updated_at FROM news_posts WHERE status = "published" ORDER BY COALESCE(published_at, updated_at) DESC LIMIT 10')->fetchAll() ?: [];
        foreach ($rows as $row) {
            $date = (string) ($row['published_at'] ?? $row['updated_at'] ?? '');
            $recent['news'][] = [
                'title' => (string) ($row['title'] ?? ''),
                'url' => $buildUrl('news_view', ['slug' => (string) ($row['slug'] ?? '')]),
                'summary' => $plainText((string) ($row['excerpt'] ?? '')),
                'published_at' => $date !== '' ? date('c', strtotime($date)) : null,
            ];
        }
    } catch (Throwable) {
    }
}

if (module_enabled('articles') && table_exists('articles')) {
    try {
        $rows = db()->query('SELECT id, slug, title, excerpt, category, published_at, created_at, updated_at FROM articles WHERE status = "published" ORDER BY ' . article_publication_sort_expression() . ' DESC, id DESC LIMIT 10')->fetchAll() ?: [];
        foreach ($rows as $row) {
            $row = localized_article_row($row);
            $published = article_publication_datetime($row);
            $recent['articles'][] = [
                'title' => (string) ($row['title_localized'] ?? $row['title'] ?? ''),
                'url' => $buildUrl('article', ['slug' => (string) ($row['slug'] ?? '')]),
                'summary' => $plainText((string) ($row['excerpt_localized'] ?? $row['excerpt'] ?? '')),
                'category' => (string) ($row['category'] ?? ''),
                'published_at' => $published !== null ? date('c', strtotime($published)) : null,
                'updated_at' => !empty($row['updated_at']) ? date('c', strtotime((string) $row['updated_at'])) : null,
            ];
        }
    } catch (Throwable) {
    }
}

if (module_enabled('events') && table_exists('events')) {
    try {
        $rows = db()->query('SELECT slug, title, summary, start_at, end_at, location FROM events WHERE status = "published" ORDER BY start_at DESC LIMIT 10')->fetchAll() ?: [];
        foreach ($rows as $row) {
            $recent['events'][] = [
                'title' => (string) ($row['title'] ?? ''),
                'url' => $buildUrl('event_view', ['slug' => (string) ($row['slug'] ?? '')]),
                'summary' => $plainText((string) ($row['summary'] ?? '')),
                'start_at' => !empty($row['start_at']) ? date('c', strtotime((string) $row['start_at'])) : null,
                'end_at' => !empty($row['end_at']) ? date('c', strtotime((string) $row['end_at'])) : null,
                'location' => (string) ($row['location'] ?? ''),
            ];
        }
    } catch (Throwable) {
    }
}

if (module_enabled('wiki') && table_exists('wiki_pages')) {
    try {
        $rows = db()->query('SELECT slug, title, content, updated_at FROM wiki_pages WHERE status = "published" ORDER BY updated_at DESC LIMIT 10')->fetchAll() ?: [];
        foreach ($rows as $row) {
            $recent['wiki'][] = [
                'title' => (string) ($row['title'] ?? ''),
                'url' => $buildUrl('wiki_view', ['slug' => (string) ($row['slug'] ?? '')]),
                'summary' => $plainText((string) ($row['content'] ?? '')),
                'updated_at' => !empty($row['updated_at']) ? date('c', strtotime((string) $row['updated_at'])) : null,
            ];
        }
    } catch (Throwable) {
    }
}

$payload = [
    'schema_version' => '1.1',
    'generated_at' => gmdate('c'),
    'site' => [
        'name' => 'Radio Club Durnal ON4CRD',
        'alternate_names' => ['ON4CRD', 'Club Radio Durnal', 'ON4CRD.be'],
        'type' => 'AmateurRadioClub',
        'url' => $buildUrl('home'),
        'language' => 'fr',
        'available_languages' => supported_locales(),
        'location' => [
            'name' => 'Durnal / Yvoir, Province de Namur, Belgium',
            'latitude' => 50.3150,
            'longitude' => 4.9452,
        ],
        'topics' => ['amateur radio', 'radioamateurisme', 'QSL', 'Morse CW', 'propagation radio', 'Belgian band plans', 'club events'],
        'same_as' => [
            'https://www.facebook.com/groups/clubradiodurnal/',
            'https://www.uba.be',
        ],
    ],
    'discovery' => [
        'sitemap' => $buildUrl('sitemap.xml'),
        'robots' => $buildUrl('robots.txt'),
        'llms' => $buildUrl('llms.txt'),
        'ai_index' => $buildUrl('ai-index.json'),
        'knowledge_graph' => $buildUrl('knowledge-graph.jsonld'),
    ],
    'canonical_pages' => [
        ['name' => 'Home', 'url' => $buildUrl('home')],
        ['name' => 'News', 'url' => $buildUrl('news')],
        ['name' => 'Events', 'url' => $buildUrl('events')],
        ['name' => 'Articles', 'url' => $buildUrl('articles')],
        ['name' => 'Wiki', 'url' => $buildUrl('wiki')],
        ['name' => 'Tools', 'url' => $buildUrl('tools')],
        ['name' => 'Membership', 'url' => $buildUrl('membership')],
        ['name' => 'Donation', 'url' => $buildUrl('donation')],
        ['name' => 'Press', 'url' => $buildUrl('press')],
        ['name' => 'Schools', 'url' => $buildUrl('schools')],
        ['name' => 'Code Q', 'url' => $buildUrl('code_q')],
        ['name' => 'Code CW', 'url' => $buildUrl('code_cw')],
        ['name' => 'Band plan ON3', 'url' => $buildUrl('bandplan_on3')],
        ['name' => 'Band plan ON2', 'url' => $buildUrl('bandplan_on2')],
        ['name' => 'Band plan HAREC', 'url' => $buildUrl('bandplan_harec')],
    ],
    'recent_public_content' => $recent,
    'answer_engine_policy' => [
        'prefer_canonical_urls' => true,
        'verify_time_sensitive_pages' => ['events', 'auctions', 'classifieds', 'membership'],
        'exclude_private_routes' => true,
        'excluded_route_prefixes' => ['admin', 'dashboard', 'profile', 'settings', 'qsl', 'notifications'],
        'allowed_source_types' => ['public page', 'public news', 'public article', 'public event', 'public wiki page', 'public album'],
        'citation_required' => true,
        'summary_guidance' => 'Use public page metadata and canonical URLs. Do not infer private member information.',
    ],
];

echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n";
