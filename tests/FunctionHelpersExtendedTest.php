<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class FunctionHelpersExtendedTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $_SERVER = [];
        $_POST = [];
        $_GET = [];
    }

    public function testSafeStoragePublicPathOrNullReturnsNullForUnauthorizedPath(): void
    {
        self::assertNull(safe_storage_public_path_or_null('storage/uploads/file.png'));
    }

    public function testMbSafeStrimwidthTrimsWithMarker(): void
    {
        self::assertSame('ABCD…', mb_safe_strimwidth('ABCDEFGHIJ', 0, 5, '…'));
    }

    public function testSanitizeHrefAttributeRejectsDataAndVbscriptSchemes(): void
    {
        self::assertNull(sanitize_href_attribute('data:text/html;base64,PHNjcmlwdD4='));
        self::assertNull(sanitize_href_attribute('vbscript:msgbox(1)'));
    }

    public function testSanitizeHrefAttributeRejectsProtocolRelativeUrls(): void
    {
        self::assertNull(sanitize_href_attribute('//evil.example/path'));
    }

    public function testSanitizeHrefAttributeRejectsUrlsWithLineBreaks(): void
    {
        self::assertNull(sanitize_href_attribute("/ok\r\njavascript:alert(1)"));
    }

    public function testExtractLatestKpMeasurementReturnsNullWhenPayloadHasOnlyHeader(): void
    {
        $payload = [
            ['time_tag', 'kp_index'],
        ];

        self::assertNull(extract_latest_kp_measurement($payload));
    }

    public function testExtractLatestKpMeasurementReturnsNullWhenNoRowContainsNumericKp(): void
    {
        $payload = [
            ['time_tag', 'kp_index'],
            ['2026-04-26 00:00:00.000', ''],
            ['2026-04-26 03:00:00.000', 'n/a'],
        ];

        self::assertNull(extract_latest_kp_measurement($payload));
    }

    public function testEnvReturnsDefaultWhenMissingAndServerValueWhenPresent(): void
    {
        self::assertSame('fallback', env('ON4CRD_TEST_ENV', 'fallback'));
        $_SERVER['ON4CRD_TEST_ENV'] = 'server-value';
        self::assertSame('server-value', env('ON4CRD_TEST_ENV', 'fallback'));
    }

    public function testStoragePathBuildsAbsolutePath(): void
    {
        $base = storage_path();
        self::assertStringEndsWith('/storage', $base);
        self::assertSame($base . '/uploads/library', storage_path('uploads/library'));
    }

    public function testAssetUrlAddsVersionParameterForExistingFile(): void
    {
        $_SERVER['HTTP_HOST'] = 'example.test';
        $_SERVER['HTTPS'] = 'on';

        $url = asset_url('assets/css/app.css');
        self::assertStringContainsString('/assets/css/app.css', $url);
        self::assertMatchesRegularExpression('/[?&]v=\\d+$/', $url);
    }

    public function testLocaleFallbackChainHandlesRegionalTags(): void
    {
        self::assertSame(['pt', 'en', 'fr'], locale_fallback_chain('pt-BR'));
        self::assertSame(['en', 'fr'], locale_fallback_chain('en-US'));
    }

    public function testI18nLocalizedValueUsesFallbackChain(): void
    {
        $localized = [
            'fr' => 'Bonjour',
            'en' => 'Hello',
        ];

        self::assertSame('Hello', i18n_localized_value($localized, 'pt-BR'));
        self::assertSame('Bonjour', i18n_localized_value(['fr' => 'Bonjour'], 'ja-JP'));
    }

    public function testCurrentLocaleUsesAcceptLanguageWhenSessionEmpty(): void
    {
        unset($_SESSION['locale']);
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-DE,de;q=0.9,en;q=0.8';

        self::assertSame('de', current_locale());
    }

}
