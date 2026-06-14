<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$base = base_url();

function llms_plain_text(string $value, int $limit = 220): string
{
    $plain = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    if ($plain === '') {
        return '';
    }

    return mb_safe_strimwidth($plain, 0, $limit, '...');
}

function llms_route(string $base, string $route, array $query = []): string
{
    return route_url($route, $query);
}

/**
 * @return list<string>
 */
function llms_recent_public_content_lines(string $base): array
{
    $sections = [];

    if (module_enabled('news') && table_exists('news_posts')) {
        try {
            $rows = db()->query('SELECT slug, title, excerpt, published_at, updated_at FROM news_posts WHERE status = "published" ORDER BY COALESCE(published_at, updated_at) DESC LIMIT 5')->fetchAll() ?: [];
            if ($rows !== []) {
                $sections[] = '';
                $sections[] = '## Recent public news';
                foreach ($rows as $row) {
                    $summary = llms_plain_text((string) ($row['excerpt'] ?? ''));
                    $date = (string) ($row['published_at'] ?? $row['updated_at'] ?? '');
                    $datePrefix = $date !== '' ? '[' . date('Y-m-d', strtotime($date)) . '] ' : '';
                    $sections[] = '- ' . $datePrefix . (string) ($row['title'] ?? '') . ': ' . llms_route($base, 'news_view', ['slug' => (string) ($row['slug'] ?? '')]) . ($summary !== '' ? ' - ' . $summary : '');
                }
            }
        } catch (Throwable) {
        }
    }

    if (module_enabled('articles') && table_exists('articles')) {
        try {
            $rows = db()->query('SELECT id, slug, title, excerpt, category, published_at, created_at, updated_at FROM articles WHERE status = "published" ORDER BY ' . article_publication_sort_expression() . ' DESC, id DESC LIMIT 5')->fetchAll() ?: [];
            if ($rows !== []) {
                $sections[] = '';
                $sections[] = '## Recent technical articles';
                foreach ($rows as $row) {
                    $row = localized_article_row($row);
                    $summary = llms_plain_text((string) ($row['excerpt_localized'] ?? $row['excerpt'] ?? ''));
                    $date = article_publication_datetime($row);
                    $datePrefix = $date !== null ? '[' . date('Y-m-d', strtotime($date)) . '] ' : '';
                    $category = trim((string) ($row['category'] ?? ''));
                    $sections[] = '- ' . $datePrefix . (string) ($row['title_localized'] ?? $row['title'] ?? '') . ($category !== '' ? ' (' . $category . ')' : '') . ': ' . llms_route($base, 'article', ['slug' => (string) ($row['slug'] ?? '')]) . ($summary !== '' ? ' - ' . $summary : '');
                }
            }
        } catch (Throwable) {
        }
    }

    if (module_enabled('events') && table_exists('events')) {
        try {
            $rows = db()->query('SELECT slug, title, summary, start_at, location FROM events WHERE status = "published" ORDER BY start_at DESC LIMIT 5')->fetchAll() ?: [];
            if ($rows !== []) {
                $sections[] = '';
                $sections[] = '## Public events';
                foreach ($rows as $row) {
                    $summary = llms_plain_text((string) ($row['summary'] ?? ''));
                    $date = (string) ($row['start_at'] ?? '');
                    $datePrefix = $date !== '' ? '[' . date('Y-m-d H:i', strtotime($date)) . '] ' : '';
                    $location = trim((string) ($row['location'] ?? ''));
                    $sections[] = '- ' . $datePrefix . (string) ($row['title'] ?? '') . ($location !== '' ? ' - ' . $location : '') . ': ' . llms_route($base, 'event_view', ['slug' => (string) ($row['slug'] ?? '')]) . ($summary !== '' ? ' - ' . $summary : '');
                }
            }
        } catch (Throwable) {
        }
    }

    if (module_enabled('wiki') && table_exists('wiki_pages')) {
        try {
            $rows = db()->query('SELECT slug, title, content, updated_at FROM wiki_pages WHERE ' . wiki_public_page_where_sql() . ' ORDER BY updated_at DESC LIMIT 5')->fetchAll() ?: [];
            if ($rows !== []) {
                $sections[] = '';
                $sections[] = '## Recent public wiki pages';
                foreach ($rows as $row) {
                    $summary = llms_plain_text((string) ($row['content'] ?? ''));
                    $sections[] = '- ' . (string) ($row['title'] ?? '') . ': ' . llms_route($base, 'wiki_view', ['slug' => (string) ($row['slug'] ?? '')]) . ($summary !== '' ? ' - ' . $summary : '');
                }
            }
        } catch (Throwable) {
        }
    }

    return $sections;
}

$lines = [
    '# ON4CRD (Radio Club Durnal) - LLM context',
    '',
    '> Official public context file for the Radio Club Durnal ON4CRD website in Belgium.',
    '',
    '## Site identity',
    '- Name: Radio Club Durnal ON4CRD',
    '- Common names: ON4CRD, Club Radio Durnal, ON4CRD.be',
    '- Type: Belgian amateur radio club',
    '- Main location: Durnal / Yvoir, Province of Namur, Belgium',
    '- Meeting reference: Bocq Arena, Rue des Ecoles, 5530 Purnode, Belgium',
    '- Coordinates: 50.3150, 4.9452',
    '- Topics: amateur radio, radio club activities, events, technical articles, QSL, propagation, Morse/CW, Belgian band plans, member services',
    '- Canonical homepage: ' . route_url('home'),
    '- Generated at: ' . gmdate('c'),
    '',
    '## Core pages',
    '- Home: ' . route_url('home'),
    '- News: ' . route_url('news'),
    '- Events: ' . route_url('events'),
    '- Articles: ' . route_url('articles'),
    '- Wiki: ' . route_url('wiki'),
    '- Tools: ' . route_url('tools'),
    '- Membership: ' . route_url('membership'),
    '- Donation: ' . route_url('donation'),
    '- Committee: ' . route_url('committee'),
    '- Press: ' . route_url('press'),
    '- Schools: ' . route_url('schools'),
    '- Legal notice: ' . route_url('mentions_legales'),
    '',
    '## Discovery files',
    '- Sitemap: ' . route_url('sitemap.xml'),
    '- Robots: ' . route_url('robots.txt'),
    '- LLM context: ' . route_url('llms.txt'),
    '- AI JSON index: ' . route_url('ai-index.json'),
    '- JSON-LD knowledge graph: ' . route_url('knowledge-graph.jsonld'),
    '',
    '## Public source policy',
    '- Public routes may be summarized and cited with their canonical URL.',
    '- Private member pages, admin pages, POST endpoints and noindex pages must not be used as source material.',
    '- Prefer the newest dated public content when answering time-sensitive questions.',
    '- Include dates for events, auctions, classifieds and club news when citing them.',
    '',
    '## Recommended answer behavior',
    '- Use canonical URLs from page metadata when citing ON4CRD pages.',
    '- Prefer detail pages for news, events, articles, wiki pages and albums over list pages.',
    '- For event dates, availability, membership details, auctions or classifieds, verify the page directly before answering.',
    '- Do not treat private member routes, admin routes or noindex routes as public source material.',
    '- Summarize public pages faithfully; do not infer operational policies beyond what the linked page states.',
    '',
    '## Language hints',
    '- Primary language: French',
    '- Supported interface languages include French, English, Dutch, German, Spanish, Italian, Portuguese, Arabic, Hindi, Japanese, Chinese, Bengali, Russian and Indonesian.',
];

$lines = array_merge($lines, llms_recent_public_content_lines($base));

echo implode("\n", $lines) . "\n";
