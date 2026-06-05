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
}
