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
        self::assertStringContainsString("_paq.push(['enableLinkTracking']);", $html);
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
        self::assertStringContainsString("window._paq.push(['enableLinkTracking']);", $html);
        self::assertDoesNotMatchRegularExpression("/^\\s+_paq\\.push\\(\\['trackPageView'\\]\\);$/m", $html);
        self::assertDoesNotMatchRegularExpression("/^\\s+_paq\\.push\\(\\['enableLinkTracking'\\]\\);$/m", $html);
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
        self::assertMatchesRegularExpression("/^\\s+_paq\\.push\\(\\['enableLinkTracking'\\]\\);$/m", $html);
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
        self::assertDoesNotMatchRegularExpression("/^\\s+_paq\\.push\\(\\['enableLinkTracking'\\]\\);$/m", $html);
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
            'respect_do_not_track' => true,
            'consent' => '',
            'locale' => 'fr',
        ], $overrides));
    }

    public function testDoNotTrackCanBeDisabled(): void
    {
        $html = $this->renderTrackingHtml([
            'require_consent' => false,
            'respect_do_not_track' => false,
            'consent' => '',
        ]);

        self::assertStringNotContainsString("_paq.push(['setDoNotTrack', true]);", $html);
        self::assertStringContainsString("_paq.push(['trackPageView']);", $html);
    }

    public function testInternalRouteUsesNormalizedTrackingUrlAndCleanQuery(): void
    {
        $html = $this->renderTrackingHtml([
            'require_consent' => false,
            'route' => 'dashboard',
            'query' => [
                'route' => 'dashboard',
                'category' => 'club',
                'p' => '2',
                '_csrf' => 'secret',
                'token' => 'reset-secret',
                'utm_source' => 'newsletter',
                'email' => 'member@example.test',
            ],
            'page_title' => 'Dashboard member',
        ]);

        self::assertStringContainsString("_paq.push(['setCustomUrl',", $html);
        self::assertStringContainsString('/dashboard?', $html);
        self::assertStringNotContainsString('route=dashboard', $html);
        self::assertStringContainsString('category=club', $html);
        self::assertStringContainsString('p=2', $html);
        self::assertStringContainsString("_paq.push(['setDocumentTitle', \"Dashboard member\"]);", $html);
        self::assertStringNotContainsString('_csrf', $html);
        self::assertStringNotContainsString('reset-secret', $html);
        self::assertStringNotContainsString('utm_source', $html);
        self::assertStringNotContainsString('member@example.test', $html);
    }

    private function assertTrackerConfigurationPrecedesInitialPageView(string $html): void
    {
        $trackerUrlPosition = strpos($html, "_paq.push(['setTrackerUrl', u + 'matomo.php']);");
        $siteIdPosition = strpos($html, "_paq.push(['setSiteId', \"4\"]);");
        $customUrlPosition = strpos($html, "_paq.push(['setCustomUrl',");
        $pageViewPosition = strpos($html, "_paq.push(['trackPageView']);");

        self::assertIsInt($trackerUrlPosition);
        self::assertIsInt($siteIdPosition);
        self::assertIsInt($customUrlPosition);
        self::assertIsInt($pageViewPosition);
        self::assertLessThan($pageViewPosition, $trackerUrlPosition);
        self::assertLessThan($pageViewPosition, $siteIdPosition);
        self::assertLessThan($pageViewPosition, $customUrlPosition);
    }
}
