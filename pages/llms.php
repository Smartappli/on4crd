<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$base = rtrim((string) config('app.base_url', ''), '/');
if ($base === '') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $base = $scheme . '://' . $host;
}

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
    $query = array_merge(['route' => $route], $query);
    return $base . '/index.php?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
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
            $rows = db()->query('SELECT slug, title, excerpt, category, updated_at FROM articles WHERE status = "published" ORDER BY updated_at DESC LIMIT 5')->fetchAll() ?: [];
            if ($rows !== []) {
                $sections[] = '';
                $sections[] = '## Recent technical articles';
                foreach ($rows as $row) {
                    $summary = llms_plain_text((string) ($row['excerpt'] ?? ''));
                    $category = trim((string) ($row['category'] ?? ''));
                    $sections[] = '- ' . (string) ($row['title'] ?? '') . ($category !== '' ? ' (' . $category . ')' : '') . ': ' . llms_route($base, 'article', ['slug' => (string) ($row['slug'] ?? '')]) . ($summary !== '' ? ' - ' . $summary : '');
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
            $rows = db()->query('SELECT slug, title, content, updated_at FROM wiki_pages WHERE status = "published" ORDER BY updated_at DESC LIMIT 5')->fetchAll() ?: [];
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
    '',
    '## Core pages',
    '- Home: ' . $base . '/index.php?route=home',
    '- News: ' . $base . '/index.php?route=news',
    '- Events: ' . $base . '/index.php?route=events',
    '- Articles: ' . $base . '/index.php?route=articles',
    '- Wiki: ' . $base . '/index.php?route=wiki',
    '- Tools: ' . $base . '/index.php?route=tools',
    '- Membership: ' . $base . '/index.php?route=membership',
    '- Committee: ' . $base . '/index.php?route=committee',
    '- Press: ' . $base . '/index.php?route=press',
    '- Schools: ' . $base . '/index.php?route=schools',
    '- Legal notice: ' . $base . '/index.php?route=mentions_legales',
    '',
    '## Discovery files',
    '- Sitemap: ' . $base . '/index.php?route=sitemap.xml',
    '- Robots: ' . $base . '/index.php?route=robots.txt',
    '- LLM context: ' . $base . '/index.php?route=llms.txt',
    '- AI JSON index: ' . $base . '/index.php?route=ai-index.json',
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
