<?php
declare(strict_types=1);

header('Content-Type: application/ld+json; charset=utf-8');
header('Cache-Control: public, max-age=3600');

$base = base_url();

$url = static function (string $route, array $query = []): string {
    return route_url($route, $query);
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
            ...club_place_schema($homeUrl . '#place'),
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
                ['@id' => $url('donation') . '#webpage'],
                ['@id' => $url('llms.txt') . '#dataset'],
                ['@id' => $url('ai-index.json') . '#dataset'],
            ],
        ],
        [
            '@type' => 'Dataset',
            '@id' => $url('llms.txt') . '#dataset',
            'name' => 'ON4CRD LLM context',
            'description' => 'Public text context file for answer engines and assistants.',
            'url' => $url('llms.txt'),
            'inLanguage' => 'en',
            'publisher' => ['@id' => $homeUrl . '#organization'],
        ],
        [
            '@type' => 'DataCatalog',
            '@id' => $url('ai-index.json') . '#dataset',
            'name' => 'ON4CRD AI content index',
            'description' => 'Public JSON index of ON4CRD canonical public pages and recent public content.',
            'url' => $url('ai-index.json'),
            'publisher' => ['@id' => $homeUrl . '#organization'],
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
    ['route' => 'donation', 'name' => 'Don ON4CRD', 'about' => 'soutien financier au Radio Club Durnal'],
    ['route' => 'code_q', 'name' => 'Code Q radioamateur ON4CRD', 'about' => 'codes Q utiles en trafic radioamateur'],
    ['route' => 'code_cw', 'name' => 'Code CW et Morse ON4CRD', 'about' => 'apprentissage du code Morse CW'],
    ['route' => 'bandplan_on3', 'name' => 'Plan de bandes ON3 ON4CRD', 'about' => 'plan de bandes belge ON3'],
    ['route' => 'bandplan_on2', 'name' => 'Plan de bandes ON2 ON4CRD', 'about' => 'plan de bandes belge ON2'],
    ['route' => 'bandplan_harec', 'name' => 'Plan de bandes HAREC ON4CRD', 'about' => 'plan de bandes belge HAREC'],
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
