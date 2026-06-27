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

if (!function_exists('comics_public_boards')) {
/**
 * @return list<array{key:string,image:string,url:string,title:string,text:string,type:string,width:int,height:int,content_size:int}>
 */
function comics_public_boards(?string $locale = null): array
{
    $t = comics_public_i18n($locale);
    $boards = [
        [
            'key' => 'commandments',
            'image' => 'assets/comics/les-10-commandements-radio-amateur.png',
            'title' => (string) $t['board_commandments_title'],
            'text' => (string) $t['board_commandments_text'],
        ],
        [
            'key' => 'first_qso',
            'image' => 'assets/comics/ma-premiere-fois-premier-qso.png',
            'title' => (string) $t['board_first_qso_title'],
            'text' => (string) $t['board_first_qso_text'],
        ],
        [
            'key' => 'ohm',
            'image' => 'assets/comics/decouverte-loi-ohm.png',
            'title' => (string) $t['board_ohm_title'],
            'text' => (string) $t['board_ohm_text'],
        ],
    ];

    return array_map(static function (array $board): array {
        $path = (string) $board['image'];
        $absolutePath = dirname(__DIR__) . '/' . $path;
        $size = is_file($absolutePath) ? @getimagesize($absolutePath) : false;

        return $board + [
            'url' => asset_url($path),
            'type' => 'image/png',
            'width' => is_array($size) ? (int) ($size[0] ?? 0) : 0,
            'height' => is_array($size) ? (int) ($size[1] ?? 0) : 0,
            'content_size' => is_file($absolutePath) ? (int) filesize($absolutePath) : 0,
        ];
    }, $boards);
}
}

if (!function_exists('comics_public_collection')) {
/**
 * @return array{locale:string,title:string,layout:string,description:string,summary:string,keywords:list<string>,url:string,available_languages:list<string>,alternate_urls:array<string,string>,boards:list<array{key:string,image:string,url:string,title:string,text:string,type:string,width:int,height:int,content_size:int}>}
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
