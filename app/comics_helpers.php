<?php
declare(strict_types=1);

if (!function_exists('comics_public_locale')) {
function comics_public_locale(?string $locale = null): string
{
    $fallback = (string) config('app.default_locale', 'fr');
    $candidate = strtolower(trim((string) ($locale ?? '')));
    if ($candidate === '' && function_exists('current_locale')) {
        $candidate = current_locale();
    }

    return in_array($candidate, supported_locales(), true) ? $candidate : $fallback;
}
}

if (!function_exists('comics_public_i18n')) {
/**
 * @return array<string, mixed>
 */
function comics_public_i18n(?string $locale = null): array
{
    return i18n_domain_locale('comics', comics_public_locale($locale));
}
}

if (!function_exists('comics_public_document_type')) {
function comics_public_document_type(string $path): string
{
    $extension = strtolower((string) pathinfo(parse_url($path, PHP_URL_PATH) ?: $path, PATHINFO_EXTENSION));

    return match ($extension) {
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'html', 'htm' => 'text/html',
        'md', 'markdown' => 'text/markdown',
        'txt' => 'text/plain',
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        default => 'application/octet-stream',
    };
}
}

if (!function_exists('comics_public_related_documents')) {
/**
 * @param list<array{path?:string,url?:string,title:string,text?:string,type?:string,download_name?:string}> $documents
 * @return list<array{title:string,text:string,url:string,path:string,type:string,content_size:int,download_name:string,external:bool}>
 */
function comics_public_related_documents(array $documents): array
{
    $root = dirname(__DIR__) . '/';
    $normalized = [];

    foreach ($documents as $document) {
        $title = trim((string) ($document['title'] ?? ''));
        $path = trim(str_replace('\\', '/', (string) ($document['path'] ?? '')));
        $url = trim((string) ($document['url'] ?? ''));
        if ($title === '' || ($path === '' && $url === '')) {
            continue;
        }

        $external = $url !== '';
        if (!$external) {
            $path = ltrim($path, '/');
            if ($path === '' || str_contains($path, '../')) {
                continue;
            }
            $url = asset_url($path);
        } else {
            $safeUrl = normalize_http_url($url);
            if ($safeUrl === null) {
                continue;
            }
            $url = $safeUrl;
        }

        $absolutePath = $external ? '' : $root . $path;
        $downloadName = trim((string) ($document['download_name'] ?? ''));
        if ($downloadName === '') {
            $downloadName = basename((string) (parse_url($external ? $url : $path, PHP_URL_PATH) ?: ($external ? $url : $path)));
        }

        $normalized[] = [
            'title' => $title,
            'text' => trim((string) ($document['text'] ?? '')),
            'url' => $url,
            'path' => $path,
            'type' => (string) ($document['type'] ?? comics_public_document_type($external ? $url : $path)),
            'content_size' => $absolutePath !== '' && is_file($absolutePath) ? (int) filesize($absolutePath) : 0,
            'download_name' => $downloadName,
            'external' => $external,
        ];
    }

    return $normalized;
}
}

if (!function_exists('comics_public_related_links')) {
/**
 * @param list<array{url?:string,route?:string,fragment?:string,title:string,text?:string}> $links
 * @return list<array{title:string,text:string,url:string,external:bool}>
 */
function comics_public_related_links(array $links): array
{
    $normalized = [];

    foreach ($links as $link) {
        $title = trim((string) ($link['title'] ?? ''));
        $url = trim((string) ($link['url'] ?? ''));
        $route = trim((string) ($link['route'] ?? ''));
        if ($title === '' || ($url === '' && $route === '')) {
            continue;
        }

        $external = $url !== '';
        if ($external) {
            $safeUrl = normalize_http_url($url);
            if ($safeUrl === null) {
                continue;
            }
            $url = $safeUrl;
        } else {
            $url = route_url($route);
        }

        $fragment = trim((string) ($link['fragment'] ?? ''));
        if ($fragment !== '') {
            $url .= '#' . rawurlencode($fragment);
        }

        $normalized[] = [
            'title' => $title,
            'text' => trim((string) ($link['text'] ?? '')),
            'url' => $url,
            'external' => $external,
        ];
    }

    return $normalized;
}
}

if (!function_exists('comics_public_boards')) {
/**
 * @return list<array{key:string,image:string,thumbnail:string,url:string,thumbnail_url:string,title:string,text:string,type:string,thumbnail_type:string,width:int,height:int,thumbnail_width:int,thumbnail_height:int,content_size:int,thumbnail_content_size:int,documents:list<array{title:string,text:string,url:string,path:string,type:string,content_size:int,download_name:string,external:bool}>,links:list<array{title:string,text:string,url:string,external:bool}>}>
 */
function comics_public_boards(?string $locale = null): array
{
    $t = comics_public_i18n($locale);
    $boards = [
        [
            'key' => 'commandments',
            'image' => 'assets/comics/les-10-commandements-radio-amateur.png',
            'thumbnail' => 'assets/comics/les-10-commandements-radio-amateur-thumb.jpg',
            'title' => (string) $t['board_commandments_title'],
            'text' => (string) $t['board_commandments_text'],
            'documents' => [],
            'links' => [],
        ],
        [
            'key' => 'first_qso',
            'image' => 'assets/comics/ma-premiere-fois-premier-qso.png',
            'thumbnail' => 'assets/comics/ma-premiere-fois-premier-qso-thumb.jpg',
            'title' => (string) $t['board_first_qso_title'],
            'text' => (string) $t['board_first_qso_text'],
            'documents' => [],
            'links' => [],
        ],
        [
            'key' => 'ohm',
            'image' => 'assets/comics/decouverte-loi-ohm.png',
            'thumbnail' => 'assets/comics/decouverte-loi-ohm-thumb.jpg',
            'title' => (string) $t['board_ohm_title'],
            'text' => (string) $t['board_ohm_text'],
            'documents' => [
                [
                    'path' => 'assets/comics/loi-ohm-fiche-memo.md',
                    'title' => (string) $t['related_document_sheet_label'],
                    'text' => (string) $t['related_document_sheet_text'],
                ],
            ],
            'links' => [
                [
                    'route' => 'tools',
                    'fragment' => 'tool-ohm-law',
                    'title' => (string) $t['related_link_ohm_tool_label'],
                    'text' => (string) $t['related_link_ohm_tool_text'],
                ],
            ],
        ],
    ];

    return array_map(static function (array $board): array {
        $path = (string) $board['image'];
        $thumbnailPath = (string) $board['thumbnail'];
        $absolutePath = dirname(__DIR__) . '/' . $path;
        $absoluteThumbnailPath = dirname(__DIR__) . '/' . $thumbnailPath;
        $size = is_file($absolutePath) ? @getimagesize($absolutePath) : false;
        $thumbnailSize = is_file($absoluteThumbnailPath) ? @getimagesize($absoluteThumbnailPath) : false;
        $documents = isset($board['documents']) && is_array($board['documents'])
            ? comics_public_related_documents($board['documents'])
            : [];
        $links = isset($board['links']) && is_array($board['links'])
            ? comics_public_related_links($board['links'])
            : [];

        return array_merge($board, [
            'url' => asset_url($path),
            'thumbnail_url' => asset_url($thumbnailPath),
            'type' => 'image/png',
            'thumbnail_type' => 'image/jpeg',
            'width' => is_array($size) ? (int) ($size[0] ?? 0) : 0,
            'height' => is_array($size) ? (int) ($size[1] ?? 0) : 0,
            'thumbnail_width' => is_array($thumbnailSize) ? (int) ($thumbnailSize[0] ?? 0) : 0,
            'thumbnail_height' => is_array($thumbnailSize) ? (int) ($thumbnailSize[1] ?? 0) : 0,
            'content_size' => is_file($absolutePath) ? (int) filesize($absolutePath) : 0,
            'thumbnail_content_size' => is_file($absoluteThumbnailPath) ? (int) filesize($absoluteThumbnailPath) : 0,
            'documents' => $documents,
            'links' => $links,
        ]);
    }, $boards);
}
}

if (!function_exists('comics_public_image_object')) {
/**
 * @param array{url:string,thumbnail_url:string,title:string,type:string,thumbnail_type:string,width:int,height:int,thumbnail_width:int,thumbnail_height:int,content_size:int,thumbnail_content_size:int} $board
 * @return array<string, mixed>
 */
function comics_public_image_object(array $board, bool $thumbnail = false): array
{
    $url = (string) ($thumbnail ? $board['thumbnail_url'] : $board['url']);
    $type = (string) ($thumbnail ? $board['thumbnail_type'] : $board['type']);
    $width = (int) ($thumbnail ? $board['thumbnail_width'] : $board['width']);
    $height = (int) ($thumbnail ? $board['thumbnail_height'] : $board['height']);
    $contentSize = (int) ($thumbnail ? $board['thumbnail_content_size'] : $board['content_size']);

    return [
        '@type' => 'ImageObject',
        '@id' => $url . '#image',
        'url' => $url,
        'contentUrl' => $url,
        'encodingFormat' => $type,
        'width' => $width,
        'height' => $height,
        'contentSize' => $contentSize,
        'caption' => (string) $board['title'],
    ];
}
}

if (!function_exists('comics_public_document_object')) {
/**
 * @param array{title:string,text:string,url:string,type:string,content_size:int} $document
 * @return array<string, mixed>
 */
function comics_public_document_object(array $document): array
{
    return [
        '@type' => 'DigitalDocument',
        '@id' => (string) $document['url'] . '#document',
        'name' => (string) $document['title'],
        'description' => (string) $document['text'],
        'url' => (string) $document['url'],
        'encodingFormat' => (string) $document['type'],
        'contentSize' => (int) $document['content_size'],
    ];
}
}

if (!function_exists('comics_public_link_object')) {
/**
 * @param array{title:string,text:string,url:string} $link
 * @return array<string, mixed>
 */
function comics_public_link_object(array $link): array
{
    return [
        '@type' => 'WebPage',
        '@id' => (string) $link['url'] . '#webpage',
        'name' => (string) $link['title'],
        'description' => (string) $link['text'],
        'url' => (string) $link['url'],
    ];
}
}

if (!function_exists('comics_public_creative_work')) {
/**
 * @param array{url:string,thumbnail_url:string,title:string,text:string,type:string,thumbnail_type:string,width:int,height:int,thumbnail_width:int,thumbnail_height:int,content_size:int,thumbnail_content_size:int,documents?:list<array{title:string,text:string,url:string,type:string,content_size:int}>,links?:list<array{title:string,text:string,url:string}>} $board
 * @return array<string, mixed>
 */
function comics_public_creative_work(array $board, string $locale, string $collectionId, string $publisherId): array
{
    $work = [
        '@type' => 'CreativeWork',
        '@id' => (string) $board['url'] . '#creativework',
        'name' => (string) $board['title'],
        'description' => (string) $board['text'],
        'url' => (string) $board['url'],
        'image' => comics_public_image_object($board),
        'thumbnail' => comics_public_image_object($board, true),
        'thumbnailUrl' => (string) $board['thumbnail_url'],
        'encodingFormat' => (string) $board['type'],
        'inLanguage' => $locale,
        'isPartOf' => ['@id' => $collectionId],
        'publisher' => ['@id' => $publisherId],
        'creator' => ['@id' => $publisherId],
        'about' => [
            ['@type' => 'Thing', 'name' => 'radioamateurisme'],
            ['@type' => 'Thing', 'name' => 'amateur radio education'],
        ],
    ];

    $documents = isset($board['documents']) && is_array($board['documents']) ? $board['documents'] : [];
    if ($documents !== []) {
        $work['hasPart'] = array_map(
            static fn(array $document): array => comics_public_document_object($document),
            $documents
        );
    }

    $links = isset($board['links']) && is_array($board['links']) ? $board['links'] : [];
    if ($links !== []) {
        $work['subjectOf'] = array_map(
            static fn(array $link): array => comics_public_link_object($link),
            $links
        );
    }

    return $work;
}
}

if (!function_exists('comics_public_collection')) {
/**
 * @return array{locale:string,title:string,layout:string,description:string,summary:string,keywords:list<string>,url:string,available_languages:list<string>,alternate_urls:array<string,string>,boards:list<array{key:string,image:string,thumbnail:string,url:string,thumbnail_url:string,title:string,text:string,type:string,thumbnail_type:string,width:int,height:int,thumbnail_width:int,thumbnail_height:int,content_size:int,thumbnail_content_size:int,documents:list<array{title:string,text:string,url:string,path:string,type:string,content_size:int,download_name:string,external:bool}>,links:list<array{title:string,text:string,url:string,external:bool}>}>}
 */
function comics_public_collection(?string $locale = null): array
{
    $locale = comics_public_locale($locale);
    $t = comics_public_i18n($locale);
    $supportedLocales = supported_locales();
    $alternateUrls = [];
    foreach ($supportedLocales as $supportedLocale) {
        $alternateUrls[$supportedLocale] = route_url_with_locale('comics', $supportedLocale);
    }

    return [
        'locale' => $locale,
        'title' => (string) $t['meta_title'],
        'layout' => (string) $t['layout'],
        'description' => (string) $t['meta_desc'],
        'summary' => (string) $t['ai_summary'],
        'keywords' => array_values(array_filter(array_map('trim', explode(',', (string) $t['keywords'])))),
        'url' => route_url('comics'),
        'available_languages' => $supportedLocales,
        'alternate_urls' => $alternateUrls,
        'boards' => comics_public_boards($locale),
    ];
}
}
