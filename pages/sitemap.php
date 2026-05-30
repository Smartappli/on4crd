<?php
declare(strict_types=1);

header('Content-Type: application/xml; charset=utf-8');

$xml = cache_remember('seo_sitemap_xml_v3', 300, static function (): string {
    $base = rtrim((string) config('app.base_url', ''), '/');
    if ($base === '') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $base = $scheme . '://' . $host;
    }

    /** @var list<array{loc:string,lastmod:string,priority:string,changefreq:string}> $entries */
    $entries = [];
    $addEntry = static function (string $route, string $priority, string $changefreq, array $query = [], ?string $lastmod = null) use (&$entries, $base): void {
        $urlQuery = array_merge(['route' => $route], $query);
        $entries[] = [
            'loc' => $base . '/index.php?' . http_build_query($urlQuery, '', '&', PHP_QUERY_RFC3986),
            'lastmod' => $lastmod ?? gmdate('c'),
            'priority' => $priority,
            'changefreq' => $changefreq,
        ];
    };

    $addEntry('home', '1.0', 'daily');

    $staticRoutes = [
        ['route' => 'news', 'priority' => '0.9', 'changefreq' => 'daily'],
        ['route' => 'search', 'priority' => '0.7', 'changefreq' => 'weekly'],
        ['route' => 'membership', 'priority' => '0.8', 'changefreq' => 'monthly'],
        ['route' => 'newsletter_public', 'priority' => '0.5', 'changefreq' => 'monthly'],
        ['route' => 'conditions_utilisation', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ['route' => 'mentions_legales', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ['route' => 'reglement_interieur', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ['route' => 'sponsoring', 'priority' => '0.5', 'changefreq' => 'monthly'],
        ['route' => 'articles', 'module' => 'articles', 'priority' => '0.8', 'changefreq' => 'weekly'],
        ['route' => 'wiki', 'module' => 'wiki', 'priority' => '0.8', 'changefreq' => 'weekly'],
        ['route' => 'albums', 'module' => 'albums', 'priority' => '0.7', 'changefreq' => 'weekly'],
        ['route' => 'classifieds', 'module' => 'classifieds', 'priority' => '0.7', 'changefreq' => 'daily'],
        ['route' => 'chatbot', 'module' => 'chatbot', 'priority' => '0.6', 'changefreq' => 'weekly'],
        ['route' => 'directory', 'module' => 'directory', 'priority' => '0.7', 'changefreq' => 'weekly'],
        ['route' => 'tools', 'module' => 'tools', 'priority' => '0.8', 'changefreq' => 'monthly'],
        ['route' => 'committee', 'module' => 'committee', 'priority' => '0.6', 'changefreq' => 'monthly'],
        ['route' => 'press', 'module' => 'press', 'priority' => '0.6', 'changefreq' => 'monthly'],
        ['route' => 'schools', 'module' => 'education', 'priority' => '0.6', 'changefreq' => 'monthly'],
        ['route' => 'events', 'module' => 'events', 'priority' => '0.8', 'changefreq' => 'weekly'],
        ['route' => 'auctions', 'module' => 'auctions', 'priority' => '0.8', 'changefreq' => 'daily'],
        ['route' => 'relais', 'priority' => '0.5', 'changefreq' => 'monthly'],
        ['route' => 'footer_contact', 'priority' => '0.4', 'changefreq' => 'monthly'],
    ];

    foreach ($staticRoutes as $row) {
        $module = (string) ($row['module'] ?? '');
        if ($module !== '' && !module_enabled($module)) {
            continue;
        }

        $addEntry((string) $row['route'], (string) $row['priority'], (string) $row['changefreq']);
    }

    if (module_enabled('news') && table_exists('news_posts')) {
        try {
        $news = db()->query('SELECT slug, updated_at FROM news_posts WHERE status = "published" ORDER BY updated_at DESC LIMIT 500')->fetchAll();
        foreach ($news as $row) {
            if (empty($row['slug'])) {
                continue;
            }

                $addEntry('news_view', '0.7', 'weekly', ['slug' => (string) $row['slug']], !empty($row['updated_at']) ? date('c', strtotime((string) $row['updated_at'])) : null);
        }
        } catch (Throwable) {
        }
    }

    if (module_enabled('articles') && table_exists('articles')) {
        try {
        $articles = db()->query('SELECT slug, updated_at FROM articles WHERE status = "published" ORDER BY updated_at DESC LIMIT 500')->fetchAll();
        foreach ($articles as $row) {
            if (empty($row['slug'])) {
                continue;
            }

                $addEntry('article', '0.7', 'monthly', ['slug' => (string) $row['slug']], !empty($row['updated_at']) ? date('c', strtotime((string) $row['updated_at'])) : null);
        }
        } catch (Throwable) {
        }
    }

    if (module_enabled('wiki') && table_exists('wiki_pages')) {
        try {
        $wikiPages = db()->query('SELECT slug, updated_at FROM wiki_pages ORDER BY updated_at DESC LIMIT 500')->fetchAll();
        foreach ($wikiPages as $row) {
            if (empty($row['slug'])) {
                continue;
            }

                $addEntry('wiki_view', '0.6', 'monthly', ['slug' => (string) $row['slug']], !empty($row['updated_at']) ? date('c', strtotime((string) $row['updated_at'])) : null);
        }
        } catch (Throwable) {
        }
    }

    if (module_enabled('albums') && table_exists('albums')) {
        try {
            $albums = db()->query('SELECT id, created_at FROM albums WHERE is_public = 1 ORDER BY created_at DESC LIMIT 500')->fetchAll();
            foreach ($albums as $row) {
                $id = (int) ($row['id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }

                $addEntry('album', '0.5', 'monthly', ['id' => $id], !empty($row['created_at']) ? date('c', strtotime((string) $row['created_at'])) : null);
            }
        } catch (Throwable) {
        }
    }

    if (module_enabled('events') && table_exists('events')) {
        try {
            $events = db()->query('SELECT slug, updated_at, start_at FROM events WHERE status = "published" ORDER BY updated_at DESC LIMIT 500')->fetchAll();
            foreach ($events as $row) {
                if (empty($row['slug'])) {
                    continue;
                }

                $lastmod = !empty($row['updated_at']) ? date('c', strtotime((string) $row['updated_at'])) : (!empty($row['start_at']) ? date('c', strtotime((string) $row['start_at'])) : null);
                $addEntry('event_view', '0.6', 'weekly', ['slug' => (string) $row['slug']], $lastmod);
            }
        } catch (Throwable) {
        }
    }

    if (module_enabled('auctions') && table_exists('auction_lots')) {
        try {
        $lots = db()->query('SELECT slug, updated_at FROM auction_lots WHERE status IN ("scheduled", "active", "closed") ORDER BY updated_at DESC LIMIT 500')->fetchAll();
        foreach ($lots as $row) {
            if (empty($row['slug'])) {
                continue;
            }

                $addEntry('auction_view', '0.6', 'daily', ['slug' => (string) $row['slug']], !empty($row['updated_at']) ? date('c', strtotime((string) $row['updated_at'])) : null);
        }
    } catch (Throwable) {
        }
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
