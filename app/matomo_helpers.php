<?php
declare(strict_types=1);

if (!function_exists('matomo_consent_text')) {
/**
 * @return array{title: string, body: string, accept: string, reject: string, label: string}
 */
function matomo_consent_text(?string $locale = null): array
{
    $locale = strtolower(trim((string) ($locale ?? (function_exists('current_locale') ? current_locale() : 'fr'))));
    if (str_contains($locale, '-')) {
        $locale = (string) explode('-', $locale, 2)[0];
    }

    $texts = [
        'fr' => [
            'title' => 'Mesure d\'audience',
            'body' => 'Aidez le club à comprendre l\'utilisation du site avec Matomo, sans cookies de suivi.',
            'accept' => 'Accepter',
            'reject' => 'Continuer sans statistiques',
            'label' => 'Choix de mesure d\'audience',
        ],
        'en' => [
            'title' => 'Audience measurement',
            'body' => 'Help the club understand site usage with Matomo, without tracking cookies.',
            'accept' => 'Accept',
            'reject' => 'Continue without statistics',
            'label' => 'Audience measurement choice',
        ],
    ];

    return $texts[$locale] ?? $texts['en'];
}
}

if (!function_exists('render_matomo_tracking_html')) {
/**
 * @param array{
 *   url?: string,
 *   site_id?: string|int,
 *   require_consent?: bool,
 *   disable_cookies?: bool,
 *   respect_do_not_track?: bool,
 *   consent?: string,
 *   locale?: string
 * } $options
 */
function render_matomo_tracking_html(array $options = []): string
{
    $matomoUrl = rtrim((string) ($options['url'] ?? config('tracking.matomo_url', '')), '/');
    $matomoSiteId = (string) ($options['site_id'] ?? config('tracking.matomo_site_id', ''));
    $matomoRequireConsent = (bool) ($options['require_consent'] ?? config('tracking.matomo_require_consent', true));
    $matomoDisableCookies = (bool) ($options['disable_cookies'] ?? config('tracking.matomo_disable_cookies', true));
    $matomoRespectDoNotTrack = (bool) ($options['respect_do_not_track'] ?? config('tracking.matomo_respect_do_not_track', true));
    $matomoConsentValue = (string) ($options['consent'] ?? ($_COOKIE['on4crd_tracking_consent'] ?? ''));
    $matomoConsentGiven = $matomoConsentValue === '1';
    $matomoConsentAnswered = in_array($matomoConsentValue, ['0', '1'], true);
    $matomoConfigured = $matomoUrl !== '' && $matomoSiteId !== '';

    if (!$matomoConfigured) {
        return '';
    }

    $matomoCanTrackInitialPageView = !$matomoRequireConsent || $matomoConsentGiven;
    $matomoShowConsentBanner = $matomoRequireConsent && !$matomoConsentAnswered;
    $consentText = matomo_consent_text(isset($options['locale']) ? (string) $options['locale'] : null);

    ob_start();
    ?>
<script nonce="<?= e(csp_nonce()) ?>">
  var _paq = window._paq = window._paq || [];
  var u = <?= json_encode($matomoUrl . '/', JSON_UNESCAPED_SLASHES) ?>;
  _paq.push(['setTrackerUrl', u + 'matomo.php']);
  _paq.push(['setSiteId', <?= json_encode($matomoSiteId) ?>]);
  <?php if ($matomoRespectDoNotTrack): ?>
  _paq.push(['setDoNotTrack', true]);
  <?php endif; ?>
  <?php if ($matomoDisableCookies): ?>
  _paq.push(['disableCookies']);
  <?php endif; ?>
  <?php if ($matomoRequireConsent): ?>
  _paq.push(['requireConsent']);
  <?php if ($matomoConsentGiven): ?>
  _paq.push(['rememberConsentGiven']);
  <?php endif; ?>
  <?php endif; ?>
  <?php if ($matomoCanTrackInitialPageView): ?>
  _paq.push(['trackPageView']);
  <?php endif; ?>
  _paq.push(['enableLinkTracking']);
  (function() {
    var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
    g.async = true;
    g.src = u + 'matomo.js';
    s.parentNode.insertBefore(g, s);
  })();
</script>
<?php if ($matomoShowConsentBanner): ?>
<div class="matomo-consent-banner" data-matomo-consent-banner role="region" aria-label="<?= e($consentText['label']) ?>">
  <p class="matomo-consent-copy"><strong><?= e($consentText['title']) ?></strong><span><?= e($consentText['body']) ?></span></p>
  <div class="matomo-consent-actions">
    <button class="button small" type="button" data-matomo-consent-accept><?= e($consentText['accept']) ?></button>
    <button class="button secondary small" type="button" data-matomo-consent-reject><?= e($consentText['reject']) ?></button>
  </div>
</div>
<script nonce="<?= e(csp_nonce()) ?>">
  (function() {
    var banner = document.querySelector('[data-matomo-consent-banner]');
    if (!banner) {
      return;
    }
    var acceptButton = banner.querySelector('[data-matomo-consent-accept]');
    var rejectButton = banner.querySelector('[data-matomo-consent-reject]');
    var setConsentCookie = function(value) {
      var secure = window.location.protocol === 'https:' ? '; Secure' : '';
      document.cookie = 'on4crd_tracking_consent=' + value + '; Max-Age=15552000; Path=/; SameSite=Lax' + secure;
    };
    var hideBanner = function() {
      banner.hidden = true;
      banner.setAttribute('aria-hidden', 'true');
    };
    if (acceptButton) {
      acceptButton.addEventListener('click', function() {
        setConsentCookie('1');
        window._paq = window._paq || [];
        window._paq.push(['rememberConsentGiven']);
        window._paq.push(['trackPageView']);
        hideBanner();
      });
    }
    if (rejectButton) {
      rejectButton.addEventListener('click', function() {
        setConsentCookie('0');
        window._paq = window._paq || [];
        window._paq.push(['forgetConsentGiven']);
        hideBanner();
      });
    }
  })();
</script>
<?php endif; ?>
    <?php

    return (string) ob_get_clean();
}
}
