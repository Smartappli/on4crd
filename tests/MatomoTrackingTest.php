<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../app/matomo_helpers.php';

final class MatomoTrackingTest extends TestCase
{
    public function testTrackerLoadsConfiguredEndpointWhenConsentIsNotRequired(): void
    {
        $html = $this->renderTrackingHtml([
            'require_consent' => false,
            'consent' => '',
        ]);

        self::assertStringContainsString("var _paq = window._paq = window._paq || [];", $html);
        self::assertStringContainsString("_paq.push(['setDoNotTrack', true]);", $html);
        self::assertStringNotContainsString('setUserIsAnonymous', $html);
        self::assertStringContainsString("_paq.push(['disableCookies']);", $html);
        self::assertStringContainsString("_paq.push(['trackPageView']);", $html);
        self::assertStringContainsString("u + 'matomo.php'", $html);
        self::assertStringContainsString("u + 'matomo.js'", $html);
        self::assertStringContainsString("_paq.push(['setSiteId', \"4\"]);", $html);
        $this->assertTrackerConfigurationPrecedesInitialPageView($html);
        self::assertStringNotContainsString("_paq.push(['requireConsent']);", $html);
        self::assertStringNotContainsString('data-matomo-consent-banner', $html);
    }

    public function testRequiredConsentRendersBannerWithoutTrackingInitialPageView(): void
    {
        $html = $this->renderTrackingHtml([
            'require_consent' => true,
            'consent' => '',
        ]);

        self::assertStringContainsString("_paq.push(['requireConsent']);", $html);
        self::assertStringContainsString('data-matomo-consent-banner', $html);
        self::assertStringContainsString('data-matomo-consent-accept', $html);
        self::assertStringContainsString('data-matomo-consent-reject', $html);
        self::assertStringContainsString('on4crd_tracking_consent=', $html);
        self::assertStringContainsString("window._paq.push(['rememberConsentGiven']);", $html);
        self::assertStringContainsString("window._paq.push(['trackPageView']);", $html);
        self::assertDoesNotMatchRegularExpression("/^\\s+_paq\\.push\\(\\['trackPageView'\\]\\);$/m", $html);
    }

    public function testRememberedConsentTracksInitialPageViewWithoutBanner(): void
    {
        $html = $this->renderTrackingHtml([
            'require_consent' => true,
            'consent' => '1',
        ]);

        self::assertStringContainsString("_paq.push(['requireConsent']);", $html);
        self::assertStringContainsString("_paq.push(['rememberConsentGiven']);", $html);
        self::assertMatchesRegularExpression("/^\\s+_paq\\.push\\(\\['trackPageView'\\]\\);$/m", $html);
        $this->assertTrackerConfigurationPrecedesInitialPageView($html);
        self::assertStringNotContainsString('data-matomo-consent-banner', $html);
    }

    public function testRejectedConsentDoesNotTrackOrRenderBanner(): void
    {
        $html = $this->renderTrackingHtml([
            'require_consent' => true,
            'consent' => '0',
        ]);

        self::assertStringContainsString("_paq.push(['requireConsent']);", $html);
        self::assertDoesNotMatchRegularExpression("/^\\s+_paq\\.push\\(\\['trackPageView'\\]\\);$/m", $html);
        self::assertStringNotContainsString("_paq.push(['rememberConsentGiven']);", $html);
        self::assertStringNotContainsString('data-matomo-consent-banner', $html);
    }

    /**
     * @param array{require_consent: bool, consent: string} $overrides
     */
    private function renderTrackingHtml(array $overrides): string
    {
        return render_matomo_tracking_html(array_replace([
            'url' => 'https://stats.example.test/',
            'site_id' => '4',
            'require_consent' => true,
            'disable_cookies' => true,
            'consent' => '',
            'locale' => 'fr',
        ], $overrides));
    }

    private function assertTrackerConfigurationPrecedesInitialPageView(string $html): void
    {
        $trackerUrlPosition = strpos($html, "_paq.push(['setTrackerUrl', u + 'matomo.php']);");
        $siteIdPosition = strpos($html, "_paq.push(['setSiteId', \"4\"]);");
        $pageViewPosition = strpos($html, "_paq.push(['trackPageView']);");

        self::assertIsInt($trackerUrlPosition);
        self::assertIsInt($siteIdPosition);
        self::assertIsInt($pageViewPosition);
        self::assertLessThan($pageViewPosition, $trackerUrlPosition);
        self::assertLessThan($pageViewPosition, $siteIdPosition);
    }
}
