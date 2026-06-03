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

    public function testCleanQueryParamsDropsOnlyEmptyValues(): void
    {
        self::assertSame(
            ['zero' => 0, 'string_zero' => '0', 'valid' => 'abc'],
            clean_query_params(['empty' => '', 'null' => null, 'false' => false, 'zero' => 0, 'string_zero' => '0', 'valid' => 'abc'])
        );
    }

    public function testPaginationStateClampsPageAndComputesOffset(): void
    {
        self::assertSame(
            ['page' => 3, 'per_page' => 10, 'total_pages' => 3, 'offset' => 20],
            pagination_state(25, 9, 10)
        );
        self::assertSame(
            ['page' => 1, 'per_page' => 1, 'total_pages' => 1, 'offset' => 0],
            pagination_state(0, -2, 0)
        );
    }

    public function testI18nDomainTranslatorReturnsKeyWhenMissing(): void
    {
        $translator = i18n_domain_translator('missing_test_domain', 'fr');

        self::assertSame('unknown_key', $translator('unknown_key'));
    }

    public function testArticleImportTextToHtmlBuildsStructuredSafeHtml(): void
    {
        $html = article_import_text_to_html("# Title\n\nFirst line\ncontinues here\n\n- one\n- two\n\n<script>alert(1)</script>");

        self::assertStringContainsString('<h2>Title</h2>', $html);
        self::assertStringContainsString('<p>First line continues here</p>', $html);
        self::assertStringContainsString('<ul>', $html);
        self::assertStringContainsString('<li>one</li>', $html);
        self::assertStringNotContainsString('<script', $html);
    }

    public function testMemberCountryHelpersFormatKnownCountryWithFlag(): void
    {
        self::assertSame('BE', member_country_code_for('Belgique'));
        self::assertNotSame('', member_country_flag('Belgique'));

        $html = member_country_html('Belgique');
        self::assertStringContainsString('country-with-flag', $html);
        self::assertStringContainsString('Belgique', $html);
    }

    public function testCoordinatesToMaidenheadBuildsSixCharacterGrid(): void
    {
        self::assertSame('JO20LH', coordinates_to_maidenhead(50.3150, 4.9452));
        self::assertSame('JO20', coordinates_to_maidenhead(50.3150, 4.9452, 4));
        self::assertNull(coordinates_to_maidenhead(91.0, 4.9452));
    }

    public function testMemberProfilePostalAddressRequiresAllParts(): void
    {
        self::assertTrue(member_profile_postal_address_is_complete('Belgique', 'Rue des Ecoles', '5530', 'Purnode'));
        self::assertFalse(member_profile_postal_address_is_complete('Belgique', '', '5530', 'Purnode'));
    }

    public function testMemberProfileRadioZonesResolveKnownCountries(): void
    {
        self::assertSame(
            ['cq_zone' => '14', 'itu_zone' => '27'],
            member_profile_radio_zones_for_coordinates(50.85, 4.35, 'BE')
        );
        self::assertSame(
            ['cq_zone' => '15', 'itu_zone' => '28'],
            member_profile_radio_zones_for_coordinates(48.2, 16.37, 'Autriche')
        );
        self::assertSame(
            ['cq_zone' => '3', 'itu_zone' => '6'],
            member_profile_radio_zones_for_coordinates(37.77, -122.42, 'US')
        );
    }

    public function testMemberNameHelpersSplitAndComposeFullName(): void
    {
        self::assertSame('Jean Dupont', member_full_name_from_parts(' Jean ', ' Dupont '));
        self::assertSame(
            ['first_name' => 'Jean', 'last_name' => 'Dupont'],
            member_name_parts_from_full_name(' Jean   Dupont ')
        );
        self::assertSame(
            ['first_name' => 'Jean', 'last_name' => ''],
            member_name_parts_from_full_name('Jean')
        );
    }

    public function testMemberWithNamePartsBackfillsFromFullName(): void
    {
        $member = member_with_name_parts(['full_name' => 'Jean Dupont']);

        self::assertSame('Jean', $member['first_name']);
        self::assertSame('Dupont', $member['last_name']);
    }

    public function testMemberProfileVisibilityDefaultsProtectSensitiveIdentityFields(): void
    {
        $t = static fn(string $key): string => $key;
        $fields = member_profile_visibility_fields($t);

        self::assertSame('members', $fields['visibility_first_name']['default']);
        self::assertSame('private', $fields['visibility_last_name']['default']);
        self::assertSame('private', $fields['visibility_address']['default']);
        self::assertSame('private', $fields['visibility_postal_code']['default']);
    }

    public function testMemberProfilePreviewRowsHideSensitiveFieldsByDefault(): void
    {
        $t = static fn(string $key): string => $key;
        $member = [
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'address' => 'Rue de la Radio 1',
            'postal_code' => '5530',
        ];

        $memberRows = member_profile_preview_rows($member, 'members', $t);
        $memberLabels = array_column($memberRows, 'label');
        self::assertContains('first_name', $memberLabels);
        self::assertNotContains('last_name', $memberLabels);
        self::assertNotContains('address', $memberLabels);
        self::assertNotContains('postal_code', $memberLabels);

        $privateRows = member_profile_preview_rows($member, 'private', $t);
        $privateLabels = array_column($privateRows, 'label');
        self::assertContains('last_name', $privateLabels);
        self::assertContains('address', $privateLabels);
        self::assertContains('postal_code', $privateLabels);
    }

    public function testMemberProfilePreviewRowsIncludeNonEmptyExtendedFields(): void
    {
        $t = static fn(string $key): string => $key;
        $member = [
            'country' => 'Belgique',
            'operator_since' => '2020',
            'cq_zone' => '14',
            'qrz_url' => 'https://www.qrz.com/db/ON4DG',
            'visibility_country' => 'public',
            'visibility_licence_class' => 'public',
            'visibility_qrz' => 'public',
        ];

        $rows = member_profile_preview_rows($member, 'public', $t);
        $labels = array_column($rows, 'label');

        self::assertContains('country', $labels);
        self::assertContains('operator_since', $labels);
        self::assertContains('cq_zone', $labels);
        self::assertContains('qrz_url', $labels);
        self::assertStringContainsString('country-with-flag', (string) $rows[0]['html']);
    }

    public function testMemberQrzSaveKeepsExistingUrlForSameCallsign(): void
    {
        self::assertSame(
            'https://www.qrz.com/db/ON4DG',
            member_qrz_url_for_profile_save('ON4DG', 'ON4DG', 'https://www.qrz.com/db/ON4DG')
        );
    }

    public function testMemberLotwUsernameDefaultsToCallsignWhenEmpty(): void
    {
        self::assertSame('ON4DG', member_lotw_username_for_profile_save(' on4dg ', ''));
        self::assertSame('custom-lotw', member_lotw_username_for_profile_save('ON4DG', ' custom-lotw '));
        self::assertNull(member_lotw_username_for_profile_save('', ''));
    }

}
