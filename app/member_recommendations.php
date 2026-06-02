<?php
declare(strict_types=1);

require_once __DIR__ . '/member_favorites.php';
require_once __DIR__ . '/member_preferences.php';

if (!function_exists('member_personalized_recommendations')) {
function member_personalized_recommendations(int $memberId, int $limit = 6): array
{
    $limit = max(1, min(24, $limit));
    $signalPrefs = [
        'article' => member_preference_bool($memberId, 'recommendations_signal_article_enabled', true),
        'wiki' => member_preference_bool($memberId, 'recommendations_signal_wiki_enabled', true),
        'classified' => member_preference_bool($memberId, 'recommendations_signal_classified_enabled', true),
        'album' => member_preference_bool($memberId, 'recommendations_signal_album_enabled', true),
        'library' => member_preference_bool($memberId, 'recommendations_signal_library_enabled', true),
    ];
    if (!in_array(true, $signalPrefs, true)) {
        return [];
    }

    $seedTypes = [];
    foreach (member_favorites_recent($memberId, 30) as $favorite) {
        $type = (string) ($favorite['target_type'] ?? '');
        if ($type !== '') {
            $seedTypes[$type] = true;
        }
    }

    $items = [];
    $pushUnique = static function (array $row) use (&$items, $limit): void {
        if (count($items) >= $limit) {
            return;
        }
        $key = (string) ($row['key'] ?? '');
        if ($key === '' || isset($items[$key])) {
            return;
        }
        $items[$key] = $row;
    };

    $wantsArticles = $signalPrefs['article'] && (isset($seedTypes['article']) || $seedTypes === []);
    if ($wantsArticles && table_exists('articles')) {
        $stmt = db()->query('SELECT id, slug, title, updated_at FROM articles WHERE status = "published" ORDER BY updated_at DESC, id DESC LIMIT 12');
        foreach (($stmt->fetchAll() ?: []) as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                $title = 'Article';
            }
            $pushUnique([
                'key' => 'article:' . $id,
                'type' => 'article',
                'title' => $title,
                'url' => route_url('article', ['slug' => (string) ($row['slug'] ?? '')]),
                'meta' => (string) ($row['updated_at'] ?? ''),
                'reason_key' => 'recommendation_reason_article',
            ]);
        }
    }

    $wantsWiki = $signalPrefs['wiki'] && (isset($seedTypes['wiki_page']) || $seedTypes === []);
    if ($wantsWiki && table_exists('wiki_pages')) {
        $stmt = db()->query('SELECT slug, title, updated_at FROM wiki_pages ORDER BY updated_at DESC LIMIT 12');
        foreach (($stmt->fetchAll() ?: []) as $row) {
            $slug = trim((string) ($row['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                $title = 'Wiki';
            }
            $pushUnique([
                'key' => 'wiki:' . $slug,
                'type' => 'wiki',
                'title' => $title,
                'url' => route_url('wiki_view', ['slug' => $slug]),
                'meta' => (string) ($row['updated_at'] ?? ''),
                'reason_key' => 'recommendation_reason_wiki',
            ]);
        }
    }

    $wantsClassifieds = $signalPrefs['classified'] && (isset($seedTypes['classified_ad']) || $seedTypes === []);
    if ($wantsClassifieds && table_exists('classified_ads')) {
        $stmt = db()->query('SELECT id, title, created_at FROM classified_ads WHERE status = "active" AND (expires_at IS NULL OR expires_at >= NOW()) ORDER BY created_at DESC, id DESC LIMIT 12');
        foreach (($stmt->fetchAll() ?: []) as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                $title = 'Classified';
            }
            $pushUnique([
                'key' => 'classified:' . $id,
                'type' => 'classified',
                'title' => $title,
                'url' => route_url('classifieds', ['q' => $title]),
                'meta' => (string) ($row['created_at'] ?? ''),
                'reason_key' => 'recommendation_reason_classified',
            ]);
        }
    }

    $wantsAlbums = $signalPrefs['album'] && (isset($seedTypes['album']) || $seedTypes === []);
    if ($wantsAlbums && table_exists('albums')) {
        $stmt = db()->query('SELECT id, title, created_at FROM albums WHERE is_public = 1 ORDER BY id DESC LIMIT 12');
        foreach (($stmt->fetchAll() ?: []) as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                $title = 'Album';
            }
            $pushUnique([
                'key' => 'album:' . $id,
                'type' => 'album',
                'title' => $title,
                'url' => route_url('album', ['id' => $id]),
                'meta' => (string) ($row['created_at'] ?? ''),
                'reason_key' => 'recommendation_reason_album',
            ]);
        }
    }

    $wantsLibrary = $signalPrefs['library'] && (isset($seedTypes['library_document']) || $seedTypes === []);
    if ($wantsLibrary && table_exists('member_library_documents')) {
        $stmt = db()->query('SELECT id, title, category, uploaded_at FROM member_library_documents ORDER BY uploaded_at DESC, id DESC LIMIT 12');
        foreach (($stmt->fetchAll() ?: []) as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                $title = 'Library document';
            }
            $pushUnique([
                'key' => 'library:' . $id,
                'type' => 'library',
                'title' => $title,
                'url' => route_url_clean('members_library', ['q' => $title, 'category' => (string) ($row['category'] ?? '')]),
                'meta' => (string) ($row['uploaded_at'] ?? ''),
                'reason_key' => 'recommendation_reason_library',
            ]);
        }
    }

    return array_values($items);
}
}
