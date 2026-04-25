<?php
declare(strict_types=1);

return [
    'db' => [
        'dsn' => 'mysql:host=localhost;port=3306;dbname=on4crd;charset=utf8mb4',
        'user' => 'db_user',
        'pass' => 'db_password',
    ],
    'app' => [
        'site_name' => 'ON4CRD v3.6.1',
        'base_url' => '',
        'default_locale' => 'fr',
        'supported_locales' => ['fr', 'en', 'de', 'nl'],
        'session_name' => 'on4crd_session',
        'allow_install' => false,
        'maintenance' => [
            'enabled' => false,
            'secret' => '',
            'allowed_routes' => ['login', 'robots.txt', 'sitemap.xml'],
        ],
    ],
    'security' => [
        'csrf_key' => 'change-me-please',
    ],
    'cache' => [
        'enabled' => true,
        'default_ttl' => 300,
        'directory' => __DIR__ . '/../storage/cache/data',
    ],
    'tracking' => [
        'matomo_url' => 'https://stats.smartappli.eu/',
        'matomo_site_id' => '4',
    ],
    'social' => [
        'album_webhooks' => [
            // 'https://hook.example.com/public-album'
        ],
    ],

    'translation' => [
        'provider' => 'deepl', // deepl|none
        'deepl_api_key' => '',
        'cache_ttl' => 604800,
    ],
    'radio_data' => [
        'cache_ttl' => 900,
        'noaa_scales_url' => 'https://services.swpc.noaa.gov/products/noaa-scales.json',
        'noaa_kp_url' => 'https://services.swpc.noaa.gov/products/noaa-planetary-k-index.json',
        'noaa_flux_url' => 'https://services.swpc.noaa.gov/json/solar-radio-flux.json',
        'noaa_alerts_url' => 'https://services.swpc.noaa.gov/products/alerts.json',
        'hamqth_dx_url' => 'https://www.hamqth.com/dxc_csv.php?limit=12',
        'satnogs_tle_url' => 'https://db.satnogs.org/api/tle/',
        'contest_rss_url' => 'https://www.contestcalendar.com/weeklycont.php/calendar.rss',
    ],
    'chatbot' => [
        'provider' => 'local', // local|external
        'external_api_url' => '',
        'external_api_key' => '',
    ],
    'observability' => [
        'enabled' => true,
        'display_error_details' => false,
    ],
];
