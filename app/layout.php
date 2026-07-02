<?php
declare(strict_types=1);

if (!function_exists('set_page_meta')) {
function set_page_meta(string|array $title = '', string $description = ''): void
{
    if (is_array($title)) {
        $_SESSION['_page_meta'] = $title;
        return;
    }
    $_SESSION['_page_meta'] = ['title' => $title, 'description' => $description];
}
}

if (!function_exists('route_url_with_locale')) {
function route_url_with_locale(string $route, string $locale, array $query = []): string
{
    $query['lang'] = $locale;
    return route_url_clean($route, $query);
}

function seo_public_current_query(): array
{
    $query = (array) $_GET;
    foreach (['route', 'lang', 'locale', '_csrf', 'maintenance_bypass', 'fbclid', 'gclid', 'msclkid', 'mc_cid', 'mc_eid'] as $key) {
        unset($query[$key]);
    }
    foreach (array_keys($query) as $key) {
        if (str_starts_with(strtolower((string) $key), 'utm_')) {
            unset($query[$key]);
        }
    }

    ksort($query);
    return clean_query_params($query);
}

/**
 * @return array<string, mixed>
 */
function club_place_schema(string $id = ''): array
{
    $place = [
        '@type' => 'Place',
        'name' => 'Bocq Arena',
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => 'Rue des Écoles',
            'postalCode' => '5530',
            'addressLocality' => 'Purnode',
            'addressRegion' => 'Namur',
            'addressCountry' => 'BE',
        ],
        'geo' => [
            '@type' => 'GeoCoordinates',
            'latitude' => 50.3150,
            'longitude' => 4.9452,
        ],
    ];

    if ($id !== '') {
        $place['@id'] = $id;
    }

    return $place;
}

function localized_seo_defaults(string $route, string $locale, array $pageMeta, string $siteName): array
{
    $seo = i18n_domain_locale('seo', $locale);
    $routeKey = function_exists('seo_i18n_route_key') ? seo_i18n_route_key($route) : (preg_replace('/[^a-z0-9_]/', '', strtolower($route)) ?: 'home');
    $discoveryRoutes = ['install.php', 'sitemap.xml', 'robots.txt', 'llms.txt', 'ai-index.json', 'knowledge-graph.jsonld'];
    $canonicalRoute = in_array($route, $discoveryRoutes, true) ? $route : $routeKey;
    $titleKey = $routeKey . '_title';
    $descriptionKey = $routeKey . '_description';
    $title = trim((string) ($pageMeta['title'] ?? ''));
    $description = trim((string) ($pageMeta['description'] ?? ''));

    if ($title === '') {
        $title = trim((string) ($seo[$titleKey] ?? $seo['default_title'] ?? $siteName));
    }
    if ($description === '') {
        $description = trim((string) ($seo[$descriptionKey] ?? $seo['default_description'] ?? ''));
    }

    $canonicalQuery = seo_public_current_query();
    $alternates = isset($pageMeta['alternates']) && is_array($pageMeta['alternates']) ? $pageMeta['alternates'] : [];
    foreach (supported_locales() as $supportedLocale) {
        $alternates[$supportedLocale] = route_url_with_locale($canonicalRoute, $supportedLocale, $canonicalQuery);
    }
    $alternates['x-default'] = route_url_with_locale($canonicalRoute, 'fr', $canonicalQuery);

    $defaults = array_replace([
        'title' => $title,
        'description' => $description,
        'canonical' => route_url_with_locale($canonicalRoute, $locale, $canonicalQuery),
        'locale' => str_replace('-', '_', locale_open_graph_code($locale)),
        'geo_region' => 'BE-WNA',
        'geo_placename' => (string) ($seo['geo_placename'] ?? 'Durnal, Yvoir, Namur, Belgium'),
        'geo_position' => '50.3150;4.9452',
        'icbm' => '50.3150, 4.9452',
        'latitude' => '50.3150',
        'longitude' => '4.9452',
        'schema_type' => 'WebPage',
        'content_type' => 'public_webpage',
        'ai_summary' => $description,
        'alternates' => $alternates,
        'robots' => function_exists('seo_default_robots_for_route') ? seo_default_robots_for_route($route) : 'index,follow',
    ], array_filter($pageMeta, static fn($value): bool => $value !== null && $value !== ''));
    $defaults['alternates'] = $alternates;
    if (!isset($defaults['json_ld'])) {
        $defaults['json_ld'] = [
            '@context' => 'https://schema.org',
            '@type' => (string) ($defaults['schema_type'] ?? 'WebPage'),
            '@id' => (string) $defaults['canonical'] . '#webpage',
            'name' => (string) $defaults['title'],
            'description' => (string) $defaults['description'],
            'url' => (string) $defaults['canonical'],
            'inLanguage' => $locale,
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => $siteName,
                'url' => route_url_with_locale('home', $locale),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Radio Club Durnal ON4CRD',
                'url' => route_url_with_locale('home', $locale),
            ],
            'about' => [
                '@type' => 'Organization',
                'name' => 'Radio Club Durnal ON4CRD',
                'url' => route_url_with_locale('home', $locale),
                'location' => club_place_schema(),
            ],
        ];
        if (!empty($defaults['image'])) {
            $defaults['json_ld']['primaryImageOfPage'] = [
                '@type' => 'ImageObject',
                'url' => (string) $defaults['image'],
            ];
        }
    }

    return $defaults;
}

function locale_open_graph_code(string $locale): string
{
    return match ($locale) {
        'fr' => 'fr_BE',
        'en' => 'en_US',
        'de' => 'de_DE',
        'nl' => 'nl_BE',
        'it' => 'it_IT',
        'es' => 'es_ES',
        'pt' => 'pt_PT',
        'ar' => 'ar_AR',
        'hi' => 'hi_IN',
        'ja' => 'ja_JP',
        'zh' => 'zh_CN',
        'bn' => 'bn_BD',
        'ru' => 'ru_RU',
        'id' => 'id_ID',
        default => 'fr_BE',
    };
}
}

if (!function_exists('render_layout')) {
function render_layout(string $content, string $title = ''): string
{
    require_once __DIR__ . '/layout_renderer.php';

    return render_layout_impl($content, $title);
}
}

function is_https_request(): bool
{
    $trustForwardedHeaders = function_exists('request_is_from_trusted_proxy') && request_is_from_trusted_proxy();
    $forwardedProtoHeader = $trustForwardedHeaders
        ? strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')))
        : '';
    $forwardedProto = $forwardedProtoHeader !== '' ? trim(explode(',', $forwardedProtoHeader)[0]) : '';
    $serverPort = (string) ($_SERVER['SERVER_PORT'] ?? '');

    return (
        (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || ($serverPort === '443')
        || ($forwardedProto === 'https')
    );
}

function csp_nonce(): string
{
    static $nonce = null;
    if ($nonce === null) {
        $nonce = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
    }

    return $nonce;
}


function mb_safe_substr(string $value, int $start, ?int $length = null): string
{
    if (function_exists('mb_substr')) {
        return $length === null ? mb_substr($value, $start) : mb_substr($value, $start, $length);
    }

    return $length === null ? substr($value, $start) : substr($value, $start, $length);
}

function mb_safe_strtolower(string $value): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
}

function mb_safe_strtoupper(string $value): string
{
    return function_exists('mb_strtoupper') ? mb_strtoupper($value) : strtoupper($value);
}

function mb_safe_strimwidth(string $value, int $start, int $width, string $trimMarker = ''): string
{
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($value, $start, $width, $trimMarker);
    }

    $slice = substr($value, $start, $width);

    if (strlen($value) > ($start + $width) && $trimMarker !== '') {
        return rtrim($slice) . $trimMarker;
    }

    return $slice;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function slugify(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return 'n-a';
    }

    if (function_exists('iconv')) {
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($transliterated !== false) {
            $value = $transliterated;
        }
    }

    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? '';
    $value = trim($value, '-');

    return $value !== '' ? $value : 'n-a';
}

function sanitize_href_attribute(string $url): ?string
{
    $trimmed = trim($url);
    if ($trimmed === '') {
        return null;
    }

    if (preg_match('/^(?:javascript|data|vbscript):/i', $trimmed) === 1) {
        return null;
    }

    try {
        return normalize_http_url($trimmed, true);
    } catch (Throwable) {
        return null;
    }
}

function sanitize_image_src_attribute(string $url): ?string
{
    $trimmed = trim($url);
    if ($trimmed === '') {
        return null;
    }
    if (preg_match('/^data:image\\/(?:png|jpe?g|gif|webp);base64,[a-z0-9+\\/=]+$/i', $trimmed) === 1) {
        return $trimmed;
    }

    if (preg_match('~^/(?!/)~', $trimmed) === 1) {
        return normalize_http_url($trimmed, true);
    }

    $safe = sanitize_href_attribute($trimmed);
    if ($safe === null) {
        return null;
    }

    $imageHost = strtolower((string) parse_url($safe, PHP_URL_HOST));
    $baseHost = strtolower((string) parse_url(base_url('/'), PHP_URL_HOST));

    return $imageHost !== '' && $baseHost !== '' && hash_equals($baseHost, $imageHost) ? $safe : null;
}

function sanitize_rich_html_unwrap_element(DOMElement $element): void
{
    $parent = $element->parentNode;
    if (!$parent) {
        return;
    }

    while ($element->firstChild) {
        $parent->insertBefore($element->firstChild, $element);
    }
    $parent->removeChild($element);
}

function sanitize_rich_html_attributes(DOMElement $node): void
{
    $tag = strtolower($node->tagName);
    $allowedByTag = [
        'a' => ['href' => true, 'rel' => true, 'target' => true, 'title' => true],
        'h2' => ['align' => true, 'title' => true],
        'h3' => ['align' => true, 'title' => true],
        'h4' => ['align' => true, 'title' => true],
        'h5' => ['align' => true, 'title' => true],
        'h6' => ['align' => true, 'title' => true],
        'img' => ['align' => true, 'alt' => true, 'height' => true, 'loading' => true, 'src' => true, 'title' => true, 'width' => true],
        'ol' => ['start' => true, 'title' => true],
        'p' => ['align' => true, 'title' => true],
        'td' => ['colspan' => true, 'rowspan' => true, 'title' => true],
        'th' => ['colspan' => true, 'rowspan' => true, 'scope' => true, 'title' => true],
    ];
    $globalAllowed = ['title' => true];
    $allowedAttributes = array_replace($globalAllowed, $allowedByTag[$tag] ?? []);
    $toRemove = [];

    foreach ($node->attributes as $attribute) {
        $originalName = $attribute->name;
        $name = strtolower($originalName);
        $value = trim($attribute->value);
        if (!isset($allowedAttributes[$name])) {
            $toRemove[] = $originalName;
            continue;
        }

        if ($name === 'href') {
            $safe = sanitize_href_attribute($value);
            if ($safe === null) {
                $toRemove[] = $originalName;
            } else {
                $node->setAttribute('href', $safe);
            }
            continue;
        }

        if ($name === 'src') {
            $safe = sanitize_image_src_attribute($value);
            if ($safe === null) {
                $toRemove[] = $originalName;
            } else {
                $node->setAttribute('src', $safe);
            }
            continue;
        }

        if ($name === 'target') {
            if ($tag !== 'a' || $value !== '_blank') {
                $toRemove[] = $originalName;
            }
            continue;
        }

        if ($name === 'rel') {
            $safeTokens = ['nofollow' => true, 'noopener' => true, 'noreferrer' => true, 'sponsored' => true, 'ugc' => true];
            $tokens = preg_split('/\s+/', strtolower($value)) ?: [];
            $tokens = array_values(array_unique(array_filter($tokens, static fn(string $token): bool => isset($safeTokens[$token]))));
            if ($tokens === []) {
                $toRemove[] = $originalName;
            } else {
                $node->setAttribute('rel', implode(' ', $tokens));
            }
            continue;
        }

        if (in_array($name, ['width', 'height', 'colspan', 'rowspan', 'start'], true) && preg_match('/^\d{1,4}$/', $value) !== 1) {
            $toRemove[] = $originalName;
            continue;
        }

        if ($name === 'scope' && !in_array(strtolower($value), ['col', 'row', 'colgroup', 'rowgroup'], true)) {
            $toRemove[] = $originalName;
            continue;
        }

        if ($name === 'align') {
            $safeAlignment = strtolower($value);
            if (!in_array($safeAlignment, ['left', 'right', 'center', 'justify', 'middle', 'top', 'bottom'], true)) {
                $toRemove[] = $originalName;
            } else {
                $node->setAttribute('align', $safeAlignment);
            }
            continue;
        }

        if ($name === 'loading' && !in_array(strtolower($value), ['lazy', 'eager'], true)) {
            $node->setAttribute('loading', 'lazy');
        }
    }

    foreach ($toRemove as $attrName) {
        $node->removeAttribute($attrName);
    }

    if ($tag === 'a' && $node->getAttribute('target') === '_blank') {
        $node->setAttribute('rel', 'noopener noreferrer');
    }
    if ($tag === 'img' && !$node->hasAttribute('loading')) {
        $node->setAttribute('loading', 'lazy');
    }
}

function sanitize_rich_html(string $html): string
{
    if ($html === '') {
        return '';
    }

    $dom = new DOMDocument();
    $previousUseInternalErrors = libxml_use_internal_errors(true);
    $wrapped = '<?xml encoding="UTF-8"><!doctype html><html><body>' . $html . '</body></html>';
    $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previousUseInternalErrors);

    $allowedTags = array_fill_keys([
        'a', 'b', 'blockquote', 'br', 'caption', 'code', 'div', 'em', 'figcaption', 'figure', 'h2', 'h3', 'h4',
        'h5', 'h6', 'hr', 'i', 'img', 'li', 'ol', 'p', 'pre', 's', 'span', 'strong', 'sub', 'sup', 'table',
        'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', 'u', 'ul',
    ], true);
    $removeWithContent = array_fill_keys([
        'audio', 'base', 'canvas', 'embed', 'iframe', 'link', 'math', 'meta', 'noscript', 'object', 'picture',
        'script', 'source', 'style', 'svg', 'template', 'video',
    ], true);
    $removeElementOnly = array_fill_keys(['input', 'option', 'select', 'textarea'], true);

    $allNodes = $dom->getElementsByTagName('*');
    for ($i = $allNodes->length - 1; $i >= 0; $i--) {
        $node = $allNodes->item($i);
        if (!$node instanceof DOMElement) {
            continue;
        }

        $tag = strtolower($node->tagName);
        if ($tag === 'html' || $tag === 'body') {
            continue;
        }

        if (isset($removeWithContent[$tag]) || isset($removeElementOnly[$tag])) {
            if ($node->parentNode !== null) {
                $node->parentNode->removeChild($node);
            }
            continue;
        }

        if (!isset($allowedTags[$tag])) {
            sanitize_rich_html_unwrap_element($node);
            continue;
        }

        sanitize_rich_html_attributes($node);
    }

    $body = $dom->getElementsByTagName('body')->item(0);
    if (!$body) {
        return '';
    }

    $result = '';
    foreach ($body->childNodes as $child) {
        $result .= $dom->saveHTML($child);
    }

    return $result;
}
