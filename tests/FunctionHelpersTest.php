<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class FunctionHelpersTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $_SERVER = [];
        $_POST = [];
        $_GET = [];
    }

    public function testEscapeHelperEscapesHtml(): void
    {
        self::assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', e('<script>alert(1)</script>'));
    }

    public function testSlugifyTransliteratesAndNormalizes(): void
    {
        self::assertSame('ecole-radio-club-2026', slugify('École Radio Club 2026'));
    }

    public function testWikiSlugBaseFallsBackAndTrimsToDatabaseLimit(): void
    {
        self::assertSame('wiki', wiki_slug_base('!!!'));
        self::assertSame(190, strlen(wiki_slug_base(str_repeat('a', 250))));
    }

    public function testWikiSlugCandidateKeepsCollisionSuffixWithinDatabaseLimit(): void
    {
        $candidate = wiki_slug_candidate(str_repeat('a', 190), 2);

        self::assertSame(190, strlen($candidate));
        self::assertSame(str_repeat('a', 188) . '-2', $candidate);
    }

    public function testNewsSlugCandidateKeepsCollisionSuffixWithinDatabaseLimit(): void
    {
        $candidate = news_slug_candidate(str_repeat('n', 190), 12);

        self::assertSame(190, strlen($candidate));
        self::assertSame(str_repeat('n', 187) . '-12', $candidate);
    }

    public function testArticleSlugCandidateKeepsCollisionSuffixWithinDatabaseLimit(): void
    {
        $candidate = article_slug_candidate(str_repeat('a', 190), 12);

        self::assertSame(190, strlen($candidate));
        self::assertSame(str_repeat('a', 187) . '-12', $candidate);
    }

    public function testNormalizeHttpUrlRejectsJavascriptScheme(): void
    {
        $this->expectException(RuntimeException::class);
        normalize_http_url('javascript:alert(1)');
    }

    public function testSanitizeHrefAttributeAllowsRelativeAndBlocksJavascript(): void
    {
        self::assertSame('/agenda', sanitize_href_attribute('/agenda'));
        self::assertNull(sanitize_href_attribute('javascript:alert(1)'));
    }

    public function testSanitizeRichHtmlRemovesDangerousMarkup(): void
    {
        $html = '<p><a href="javascript:alert(1)" onclick="alert(1)">Lien</a>'
            . '<img src="https://example.org/test.jpg" onerror="alert(1)"></p><script>alert(1)</script>';

        $sanitized = sanitize_rich_html($html);

        self::assertStringNotContainsString('<script', $sanitized);
        self::assertStringNotContainsString('onclick=', $sanitized);
        self::assertStringNotContainsString('javascript:', $sanitized);
        self::assertStringContainsString('<img', $sanitized);
        self::assertStringContainsString('loading="lazy"', $sanitized);
    }

    public function testSafeStoragePublicPathAcceptsOnlyWhitelistedPrefixes(): void
    {
        self::assertSame('storage/press/doc.pdf', safe_storage_public_path('storage/press/doc.pdf'));

        $this->expectException(RuntimeException::class);
        safe_storage_public_path('storage/uploads/doc.pdf');
    }

    public function testCsrfTokenAndVerificationWork(): void
    {
        $token = csrf_token();
        $_POST['_csrf'] = $token;

        verify_csrf();

        self::assertSame(64, strlen($token));
    }

    public function testLoginNextUrlForRoutePreservesSafeQuery(): void
    {
        $next = login_next_url_for_route('auction_view', [
            'route' => 'auction_view',
            'id' => 42,
            'next' => 'https://evil.test/',
            '_csrf' => 'token',
        ]);

        self::assertSame(route_url('auction_view', ['id' => 42]), $next);
    }

    public function testSafeLoginNextUrlRejectsExternalUrl(): void
    {
        self::assertNull(safe_login_next_url('https://evil.test/index.php?route=qsl'));
    }

    public function testSafeLoginNextUrlRebuildsInternalRouteUrl(): void
    {
        $next = safe_login_next_url('/index.php?route=qsl_export&id=7&next=https%3A%2F%2Fevil.test');

        self::assertSame(route_url('qsl_export', ['id' => '7']), $next);
    }

    public function testRouteUrlUsesDirectDiscoveryFiles(): void
    {
        self::assertStringEndsWith('/sitemap.xml', route_url('sitemap.xml'));
        self::assertStringEndsWith('/robots.txt', route_url('robots.txt'));
        self::assertStringEndsWith('/llms.txt', route_url('llms.txt'));
        self::assertStringEndsWith('/ai-index.json', route_url('ai-index.json'));
        self::assertStringEndsWith('/knowledge-graph.jsonld', route_url('knowledge-graph.jsonld'));
        self::assertStringEndsWith('/ai-index.json?fresh=1', route_url('ai-index.json', ['fresh' => '1']));
    }

    public function testValidateRemoteFeedUrlRejectsLocalNetworks(): void
    {
        $this->expectException(RuntimeException::class);
        validate_remote_feed_url('http://127.0.0.1/feed.xml');
    }

    public function testIsHttpsRequestReturnsTrueWhenServerPortIsInteger443(): void
    {
        $_SERVER['SERVER_PORT'] = 443;

        self::assertTrue(is_https_request());
    }

    public function testIsHttpsRequestUsesFirstForwardedProtoValue(): void
    {
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https, http';

        self::assertTrue(is_https_request());
    }

    public function testMaidenheadToCoordinatesConvertsFourCharactersLocator(): void
    {
        $coordinates = maidenhead_to_coordinates('JO20');

        self::assertIsArray($coordinates);
        self::assertSame(50.5, round((float) $coordinates['latitude'], 1));
        self::assertSame(5.0, round((float) $coordinates['longitude'], 1));
    }

    public function testMaidenheadToCoordinatesReturnsNullForInvalidLocator(): void
    {
        self::assertNull(maidenhead_to_coordinates('INVALID'));
    }

    public function testMaidenheadToCoordinatesConvertsSixCharactersLocator(): void
    {
        $coordinates = maidenhead_to_coordinates('JO20LI');

        self::assertIsArray($coordinates);
        self::assertSame(50.4, round((float) $coordinates['latitude'], 1));
        self::assertSame(4.9, round((float) $coordinates['longitude'], 1));
    }

    public function testExtractLatestKpMeasurementReturnsLatestNumericRow(): void
    {
        $payload = [
            ['time_tag', 'kp_index'],
            ['2026-04-26 00:00:00.000', '2.00'],
            ['2026-04-26 03:00:00.000', '3.33'],
        ];

        $result = extract_latest_kp_measurement($payload);

        self::assertIsArray($result);
        self::assertSame('2026-04-26 03:00:00.000', $result['timestamp']);
        self::assertSame(3.33, round((float) $result['kp'], 2));
    }

    public function testExtractKpTrendComparesLatestWithOlderMeasurement(): void
    {
        $payload = [
            ['time_tag', 'kp_index'],
            ['2026-04-26 00:00:00.000', '2.00'],
            ['2026-04-26 03:00:00.000', '2.33'],
            ['2026-04-26 06:00:00.000', '2.67'],
            ['2026-04-26 09:00:00.000', '3.33'],
        ];

        self::assertSame(1.33, round((float) extract_kp_trend($payload, 3), 2));
    }

    public function testExtractLatestKpMeasurementSkipsInvalidTrailingRows(): void
    {
        $payload = [
            ['time_tag', 'kp_index'],
            ['2026-04-26 00:00:00.000', '1.67'],
            ['2026-04-26 03:00:00.000', ''],
            ['bad-row'],
        ];

        $result = extract_latest_kp_measurement($payload);

        self::assertIsArray($result);
        self::assertSame('2026-04-26 00:00:00.000', $result['timestamp']);
        self::assertSame(1.67, round((float) $result['kp'], 2));
    }

    public function testExtractLatestKpMeasurementSupportsNoaaObjectRows(): void
    {
        $payload = [
            ['time_tag' => '2026-05-30T00:00:00', 'Kp' => 3.33, 'station_count' => 8],
            ['time_tag' => '2026-05-30T03:00:00', 'Kp' => 3.00, 'station_count' => 8],
        ];

        $result = extract_latest_kp_measurement($payload);

        self::assertIsArray($result);
        self::assertSame('2026-05-30T03:00:00', $result['timestamp']);
        self::assertSame(3.00, round((float) $result['kp'], 2));
    }

    public function testExtractLatestKpMeasurementSupportsSingleNoaaObjectRow(): void
    {
        $payload = [
            ['time_tag' => '2026-05-30T06:00:00', 'Kp' => 1.33, 'station_count' => 8],
        ];

        $result = extract_latest_kp_measurement($payload);

        self::assertIsArray($result);
        self::assertSame('2026-05-30T06:00:00', $result['timestamp']);
        self::assertSame(1.33, round((float) $result['kp'], 2));
    }

    public function testHamqslCatalogIsDerivedFromSingleVariantRegistry(): void
    {
        $variants = hamqsl_widget_variants();
        $catalog = hamqsl_widget_catalog();

        self::assertArrayHasKey('hamqsl_hf_vhf', $variants);
        self::assertSame(array_keys($variants), array_keys($catalog));
        self::assertSame($variants['hamqsl_hf_vhf']['title'], $catalog['hamqsl_hf_vhf']['title']);
    }

    public function testHamqslWidgetRenderKeepsSourceCreditAndManualRefreshPolicy(): void
    {
        $html = render_widget('hamqsl_band_conditions');

        self::assertStringContainsString('href="https://www.hamqsl.com/solar.html"', $html);
        self::assertStringContainsString('src="https://www.hamqsl.com/solarbc.php"', $html);
        self::assertStringContainsString('data-widget-refresh="manual"', $html);
        self::assertStringContainsString('HAMQSL / N0NBH', $html);
    }

    public function testWidgetCatalogCoversDashboardRendererSlugs(): void
    {
        $catalog = widget_catalog();
        $renderer = file_get_contents(__DIR__ . '/../app/widget_renderer.php');
        self::assertIsString($renderer);
        preg_match_all('/case\s+\'([^\']+)\'\s*:/', $renderer, $matches);

        foreach ($matches[1] as $slug) {
            if ($slug === 'chatbot') {
                continue;
            }
            self::assertArrayHasKey($slug, $catalog, sprintf('Widget "%s" is renderable but missing from widget_catalog().', $slug));
        }
    }

    public function testDashboardCatalogExposesHamWeatherAdviceWidget(): void
    {
        $catalog = widget_catalog();
        $renderer = file_get_contents(__DIR__ . '/../app/widget_renderer.php');
        $dashboard = file_get_contents(__DIR__ . '/../pages/dashboard.php');
        self::assertIsString($renderer);
        self::assertIsString($dashboard);

        self::assertArrayHasKey('ham_weather_advice', $catalog);
        self::assertStringContainsString("case 'ham_weather_advice':", $renderer);
        self::assertStringContainsString('render_ham_weather_advice($user)', $renderer);
        self::assertStringContainsString("array_merge(['welcome', 'ham_weather_advice'], array_keys(hamqsl_widget_catalog()))", $dashboard);
    }

    public function testHamWeatherAdviceWidgetHidesHeadingAndCalculationDetails(): void
    {
        $source = file_get_contents(__DIR__ . '/../app/ham_weather_advice.php');
        self::assertIsString($source);
        $returnBlock = strstr($source, 'return \'<div class="grid gap-4">\'');
        self::assertIsString($returnBlock);

        foreach (['radio_info', 'input_info', 'location', 'local_hour', 'local_weather', 'geomagnetic', 'updated_at'] as $detailKey) {
            self::assertStringNotContainsString('$i18n[\'' . $detailKey . '\']', $returnBlock);
        }
    }

    public function testHamWeatherAdviceUsesAgrometTokenFromEnvironment(): void
    {
        $source = file_get_contents(__DIR__ . '/../app/ham_weather_advice.php');
        self::assertIsString($source);

        self::assertStringContainsString("env('AGROMET_API_TOKEN'", $source);
        self::assertStringContainsString("sprintf('Authorization: Token %s', \$token)", $source);
        self::assertStringNotContainsString('my secret token', $source);
    }

    public function testAgrometHourlyUrlUsesConfigurableStationsAndRecentDates(): void
    {
        $_SERVER['AGROMET_API_BASE_URL'] = 'https://example.test/agromet';
        $_SERVER['AGROMET_STATION_SIDS'] = '2,3';

        $url = ham_agromet_hourly_url(new DateTimeImmutable('2026-06-06T12:00:00+02:00'));

        self::assertSame('https://example.test/agromet/tsa,plu,hra,vvt/2,3/2026-06-05/2026-06-06/', $url);
    }

    public function testAgrometCurrentWeatherMapsLatestHourlyMeasurements(): void
    {
        $payload = [
            'results' => [
                ['datetime' => '2026-06-06T10:00:00+02:00', 'sid' => 1, 'tsa' => 12.0, 'hra' => 80, 'plu' => 0.0, 'vvt' => 2.0],
                ['datetime' => '2026-06-06T11:00:00+02:00', 'sid' => 26, 'tsa' => 14.1, 'hra' => 91, 'plu' => 0.1, 'vvt' => 3.7],
            ],
        ];

        $current = ham_agromet_current_weather($payload);

        self::assertIsArray($current);
        self::assertSame(14.1, $current['temperature_2m']);
        self::assertSame(91, $current['relative_humidity_2m']);
        self::assertSame(0.1, $current['precipitation']);
        self::assertEqualsWithDelta(13.32, $current['wind_speed_10m'], 0.001);
        self::assertSame('2026-06-06T11:00:00+02:00', $current['time']);

        $nestedPayload = [
            'results' => [[
                'datetime' => '2026-06-06T12:00:00+02:00',
                'station' => ['sid' => 26],
                'measurements' => [
                    ['sensor' => ['code' => 'tsa'], 'value' => 15.2],
                    ['sensor' => ['code' => 'hra'], 'value' => 87],
                ],
            ]],
        ];
        $nestedCurrent = ham_agromet_current_weather($nestedPayload);
        self::assertIsArray($nestedCurrent);
        self::assertSame(15.2, $nestedCurrent['temperature_2m']);
        self::assertSame(87, $nestedCurrent['relative_humidity_2m']);
    }

    public function testDashboardWeatherWidgetUsesAgrometAsPrimarySource(): void
    {
        $catalog = widget_catalog();
        $renderer = file_get_contents(__DIR__ . '/../app/widget_renderer.php');
        self::assertIsString($renderer);

        self::assertArrayHasKey('open_meteo', $catalog);
        self::assertStringContainsString('Agromet', (string) ($catalog['open_meteo']['description'] ?? ''));
        self::assertStringContainsString("case 'open_meteo':", $renderer);
        self::assertStringContainsString("env('AGROMET_API_TOKEN'", $renderer);
        self::assertStringContainsString('ham_agromet_hourly_url()', $renderer);
        self::assertStringContainsString('ham_agromet_current_weather($payload)', $renderer);
        self::assertStringContainsString('widget:weather:agromet:', $renderer);
        self::assertStringContainsString("'temperature' => 'Temperature'", $renderer);
        self::assertStringContainsString("'humidity' => 'Humidite'", $renderer);
        self::assertStringContainsString("'rain' => 'Pluie'", $renderer);
    }

    public function testDashboardWidgetCatalogScrollsFromLeftInsidePanel(): void
    {
        $css = file_get_contents(__DIR__ . '/../assets/css/app.css');
        self::assertIsString($css);
        $css = str_replace("\r\n", "\n", $css);

        self::assertStringContainsString(".dashboard-offcanvas {\n", $css);
        self::assertStringContainsString("  direction: rtl;\n", $css);
        self::assertStringContainsString('.dashboard-offcanvas > * { direction: ltr; }', $css);
    }

    public function testDashboardCatalogRestoresHamqslAndRemovesLegacyUtilityWidgets(): void
    {
        $catalog = widget_catalog();
        $renderer = file_get_contents(__DIR__ . '/../app/widget_renderer.php');
        $dashboard = file_get_contents(__DIR__ . '/../pages/dashboard.php');
        self::assertIsString($renderer);
        self::assertIsString($dashboard);

        foreach (array_keys(hamqsl_widget_catalog()) as $hamqslKey) {
            self::assertArrayHasKey($hamqslKey, $catalog);
        }
        foreach (['club_status', 'events', 'quick_links'] as $removedKey) {
            self::assertArrayNotHasKey($removedKey, $catalog);
            self::assertStringNotContainsString("case '" . $removedKey . "':", $renderer);
        }
        self::assertStringContainsString("\$legacyUtilityWidgetKeys = ['club_status', 'events', 'quick_links'];", $dashboard);
        self::assertStringContainsString('$hadLegacyUtilityWidget', $dashboard);
        self::assertStringContainsString('$radioDefaultWidgetKeys', $dashboard);
    }

    public function testApplicationPhpFilesDoNotContainCommonMojibakeSequences(): void
    {
        $root = dirname(__DIR__);
        $iterator = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
                static function (SplFileInfo $file): bool {
                    $path = str_replace('\\', '/', $file->getPathname());
                    return !str_contains($path, '/vendor/')
                        && !str_contains($path, '/.git/')
                        && !str_contains($path, '/tests/')
                        && !str_contains($path, '/.phpunit.cache/');
                }
            )
        );
        $pattern = '/(?:\x{00C3}[\x{0080}-\x{00BF}\x{0192}\x{2030}]|\x{00C2}[\x{0080}-\x{00BF}]|\x{00E2}[\x{0080}-\x{00BF}\x{201A}\x{20AC}\x{201C}\x{201D}\x{2122}]{2})/u';
        $findings = [];

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $path = str_replace('\\', '/', $file->getPathname());
            if (str_ends_with($path, '/pages/admin_translation_reviews.php')) {
                continue;
            }
            $source = file_get_contents($path);
            if (!is_string($source)) {
                continue;
            }
            if (preg_match($pattern, $source) === 1) {
                $findings[] = str_replace(str_replace('\\', '/', $root) . '/', '', $path);
            }
        }

        self::assertSame([], $findings);
    }
}
