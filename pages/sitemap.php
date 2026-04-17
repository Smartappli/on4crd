<?php
declare(strict_types=1);

header('Content-Type: application/xml; charset=utf-8');

$xml = cache_remember('seo_sitemap_xml_v2', 300, static function (): string {
    $base = rtrim((string) config('app.base_url', ''), '/');
    if ($base === '') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $base = $scheme . '://' . $host;
    }

    /** @var list<array{loc:string,lastmod:string,priority:string,changefreq:string}> $entries */
    $entries = [];
    $entries[] = ['loc' => $base . '/index.php?route=home', 'lastmod' => gmdate('c'), 'priority' => '1.0', 'changefreq' => 'daily'];

    $staticRoutes = [
        ['route' => 'news', 'priority' => '0.9', 'changefreq' => 'daily'],
        ['route' => 'articles', 'priority' => '0.8', 'changefreq' => 'weekly'],
        ['route' => 'wiki', 'priority' => '0.8', 'changefreq' => 'weekly'],
        ['route' => 'albums', 'priority' => '0.7', 'changefreq' => 'weekly'],
        ['route' => 'committee', 'priority' => '0.6', 'changefreq' => 'monthly'],
        ['route' => 'press', 'priority' => '0.6', 'changefreq' => 'monthly'],
        ['route' => 'schools', 'priority' => '0.6', 'changefreq' => 'monthly'],
        ['route' => 'events', 'priority' => '0.8', 'changefreq' => 'weekly'],
        ['route' => 'shop', 'priority' => '0.8', 'changefreq' => 'daily'],
        ['route' => 'auctions', 'priority' => '0.8', 'changefreq' => 'daily'],
    ];

    foreach ($staticRoutes as $row) {
        $entries[] = [
            'loc' => $base . '/index.php?route=' . rawurlencode((string) $row['route']),
            'lastmod' => gmdate('c'),
            'priority' => (string) $row['priority'],
            'changefreq' => (string) $row['changefreq'],
        ];
    }

    try {
        $news = db()->query('SELECT slug, updated_at FROM news_posts WHERE status = "published" ORDER BY updated_at DESC LIMIT 500')->fetchAll();
        foreach ($news as $row) {
            if (empty($row['slug'])) {
                continue;
            }
            $entries[] = [
                'loc' => $base . '/index.php?route=news_view&slug=' . rawurlencode((string) $row['slug']),
                'lastmod' => !empty($row['updated_at']) ? date('c', strtotime((string) $row['updated_at'])) : gmdate('c'),
                'priority' => '0.7',
                'changefreq' => 'weekly',
            ];
        }

        $articles = db()->query('SELECT slug, updated_at FROM articles WHERE status = "published" ORDER BY updated_at DESC LIMIT 500')->fetchAll();
        foreach ($articles as $row) {
            if (empty($row['slug'])) {
                continue;
            }
            $entries[] = [
                'loc' => $base . '/index.php?route=article&slug=' . rawurlencode((string) $row['slug']),
                'lastmod' => !empty($row['updated_at']) ? date('c', strtotime((string) $row['updated_at'])) : gmdate('c'),
                'priority' => '0.7',
                'changefreq' => 'monthly',
            ];
        }

        $wikiPages = db()->query('SELECT slug, updated_at FROM wiki_pages ORDER BY updated_at DESC LIMIT 500')->fetchAll();
        foreach ($wikiPages as $row) {
            if (empty($row['slug'])) {
                continue;
            }
            $entries[] = [
                'loc' => $base . '/index.php?route=wiki_view&slug=' . rawurlencode((string) $row['slug']),
                'lastmod' => !empty($row['updated_at']) ? date('c', strtotime((string) $row['updated_at'])) : gmdate('c'),
                'priority' => '0.6',
                'changefreq' => 'monthly',
            ];
        }

        $products = db()->query('SELECT slug, updated_at FROM shop_products WHERE status = "published" ORDER BY updated_at DESC LIMIT 500')->fetchAll();
        foreach ($products as $row) {
            if (empty($row['slug'])) {
                continue;
            }
            $entries[] = [
                'loc' => $base . '/index.php?route=shop_product&slug=' . rawurlencode((string) $row['slug']),
                'lastmod' => !empty($row['updated_at']) ? date('c', strtotime((string) $row['updated_at'])) : gmdate('c'),
                'priority' => '0.7',
                'changefreq' => 'weekly',
            ];
        }

        $lots = db()->query('SELECT slug, updated_at FROM auction_lots WHERE status IN ("scheduled", "active", "closed") ORDER BY updated_at DESC LIMIT 500')->fetchAll();
        foreach ($lots as $row) {
            if (empty($row['slug'])) {
                continue;
            }
            $entries[] = [
                'loc' => $base . '/index.php?route=auction_view&slug=' . rawurlencode((string) $row['slug']),
                'lastmod' => !empty($row['updated_at']) ? date('c', strtotime((string) $row['updated_at'])) : gmdate('c'),
                'priority' => '0.6',
                'changefreq' => 'daily',
            ];
        }
    } catch (Throwable) {
        // Fallback: sitemap statique si la base n'est pas disponible.
    }

    $entries = array_values(array_unique($entries, SORT_REGULAR));

    $buffer = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $buffer .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ($entries as $entry) {
        $buffer .= '<url>';
        $buffer .= '<loc>' . e((string) $entry['loc']) . '</loc>';
        $buffer .= '<lastmod>' . e((string) $entry['lastmod']) . '</lastmod>';
        $buffer .= '<changefreq>' . e((string) $entry['changefreq']) . '</changefreq>';
        $buffer .= '<priority>' . e((string) $entry['priority']) . '</priority>';
        $buffer .= '</url>';
    }
    $buffer .= '</urlset>';

    return $buffer;
});

echo (string) $xml;
