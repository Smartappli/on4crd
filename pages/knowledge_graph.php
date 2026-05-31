<?php
declare(strict_types=1);

header('Content-Type: application/ld+json; charset=utf-8');
header('Cache-Control: public, max-age=3600');

$base = rtrim((string) config('app.base_url', ''), '/');
if ($base === '') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $base = $scheme . '://' . $host;
}

$url = static function (string $route, array $query = []) use ($base): string {
    $query = array_merge(['route' => $route], $query);
    return $base . '/index.php?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
};

$homeUrl = $url('home');
$graph = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Organization',
            '@id' => $homeUrl . '#organization',
            'name' => 'Radio Club Durnal ON4CRD',
            'alternateName' => ['ON4CRD', 'Club Radio Durnal', 'ON4CRD.be'],
            'url' => $homeUrl,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $base . '/assets/logo/LOGO-CRD-HALO-2020.png',
            ],
            'sameAs' => [
                'https://www.facebook.com/groups/clubradiodurnal/',
                'https://www.uba.be',
            ],
            'telephone' => ['+32496260865', '+32478789193'],
            'knowsAbout' => [
                'radioamateurisme',
                'amateur radio',
                'ON4CRD',
                'QSL',
                'CW',
                'Morse',
                'propagation radio',
                'plans de bandes belges',
                'formations radioamateur',
                'activites radio club',
            ],
            'areaServed' => ['Durnal', 'Yvoir', 'Province de Namur', 'Belgique'],
            'location' => ['@id' => $homeUrl . '#place'],
        ],
        [
            '@type' => 'Place',
            '@id' => $homeUrl . '#place',
            'name' => 'Bocq Arena',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => 'Rue des Ecoles',
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
        ],
        [
            '@type' => 'WebSite',
            '@id' => $homeUrl . '#website',
            'name' => 'ON4CRD.be',
            'url' => $homeUrl,
            'publisher' => ['@id' => $homeUrl . '#organization'],
            'inLanguage' => supported_locales(),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => $url('search', ['q' => '{search_term_string}']),
                'query-input' => 'required name=search_term_string',
            ],
            'hasPart' => [
                ['@id' => $url('news') . '#webpage'],
                ['@id' => $url('events') . '#webpage'],
                ['@id' => $url('articles') . '#webpage'],
                ['@id' => $url('wiki') . '#webpage'],
                ['@id' => $url('tools') . '#webpage'],
                ['@id' => $url('membership') . '#webpage'],
            ],
        ],
    ],
];

foreach ([
    ['route' => 'news', 'name' => 'Actualites ON4CRD', 'about' => 'actualites radioamateur'],
    ['route' => 'events', 'name' => 'Agenda ON4CRD', 'about' => 'evenements radioamateurs'],
    ['route' => 'articles', 'name' => 'Articles techniques ON4CRD', 'about' => 'documentation technique radioamateur'],
    ['route' => 'wiki', 'name' => 'Wiki ON4CRD', 'about' => 'base de connaissances radioamateur'],
    ['route' => 'tools', 'name' => 'Outils radioamateurs ON4CRD', 'about' => 'outils de calcul radioamateur'],
    ['route' => 'membership', 'name' => 'Adhesion ON4CRD', 'about' => 'adhesion au Radio Club Durnal'],
] as $page) {
    $pageUrl = $url((string) $page['route']);
    $graph['@graph'][] = [
        '@type' => 'WebPage',
        '@id' => $pageUrl . '#webpage',
        'name' => (string) $page['name'],
        'url' => $pageUrl,
        'isPartOf' => ['@id' => $homeUrl . '#website'],
        'about' => [
            '@type' => 'Thing',
            'name' => (string) $page['about'],
        ],
        'publisher' => ['@id' => $homeUrl . '#organization'],
    ];
}

echo json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n";
