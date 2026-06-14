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

if (!function_exists('matomo_tracking_query')) {
/**
 * @param array<string|int, mixed> $query
 * @return array<string, mixed>
 */
function matomo_tracking_query(array $query): array
{
    $clean = [];
    $blockedNames = [
        '_csrf',
        'csrf',
        'maintenance_bypass',
        'next',
        'return_url',
    ];

    foreach ($query as $key => $value) {
        $key = trim((string) $key);
        $normalizedKey = strtolower($key);
        if (
            $key === ''
            || $normalizedKey === 'route'
            || str_starts_with($normalizedKey, 'utm_')
            || in_array($normalizedKey, $blockedNames, true)
            || str_contains($normalizedKey, 'token')
            || str_contains($normalizedKey, 'password')
            || str_contains($normalizedKey, 'secret')
            || str_contains($normalizedKey, 'email')
        ) {
            continue;
        }

        if (is_array($value)) {
            $items = [];
            foreach ($value as $item) {
                if (is_scalar($item)) {
                    $item = trim((string) $item);
                    if ($item !== '') {
                        $items[] = $item;
                    }
                }
            }
            if ($items !== []) {
                $clean[$key] = $items;
            }
            continue;
        }

        if (is_scalar($value)) {
            $clean[$key] = $value;
        }
    }

    ksort($clean);

    return function_exists('clean_query_params') ? clean_query_params($clean) : array_filter($clean, static fn(mixed $value): bool => $value !== '' && $value !== null && $value !== false);
}
}

if (!function_exists('matomo_tracking_page_url')) {
/**
 * @param array<string, mixed> $options
 */
function matomo_tracking_page_url(array $options = []): string
{
    $pageUrl = trim((string) ($options['page_url'] ?? ''));
    if ($pageUrl !== '') {
        return $pageUrl;
    }

    $route = trim((string) ($options['route'] ?? ($_GET['route'] ?? 'home')));
    if ($route === '' || preg_match('/^[a-z0-9_.-]+$/', $route) !== 1) {
        $route = 'home';
    }

    $querySource = isset($options['query']) && is_array($options['query'])
        ? $options['query']
        : (array) $_GET;
    $query = matomo_tracking_query($querySource);

    $path = $route === 'home' ? '/' : ('/' . rawurlencode($route));
    $queryString = http_build_query($query);
    $separator = $queryString === '' ? '' : '?' . $queryString;

    return base_url($path) . $separator;
}
}

if (!function_exists('matomo_tracking_document_title')) {
/**
 * @param array<string, mixed> $options
 */
function matomo_tracking_document_title(array $options = []): string
{
    $title = trim((string) ($options['document_title'] ?? $options['page_title'] ?? ''));
    if ($title !== '') {
        return $title;
    }

    $pageMeta = isset($_SESSION['_page_meta']) && is_array($_SESSION['_page_meta']) ? $_SESSION['_page_meta'] : [];
    $title = trim((string) ($pageMeta['title'] ?? ''));

    return $title !== '' ? $title : (string) config('app.site_name', 'ON4CRD');
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
 *   page_url?: string,
 *   page_title?: string,
 *   document_title?: string,
 *   route?: string,
 *   query?: array<string, mixed>,
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
    $matomoPageUrl = matomo_tracking_page_url($options);
    $matomoDocumentTitle = matomo_tracking_document_title($options);
    $consentText = matomo_consent_text(isset($options['locale']) ? (string) $options['locale'] : null);

    ob_start();
    ?>
<script nonce="<?= e(csp_nonce()) ?>">
  var _paq = window._paq = window._paq || [];
  var u = <?= json_encode($matomoUrl . '/', JSON_UNESCAPED_SLASHES) ?>;
  _paq.push(['setTrackerUrl', u + 'matomo.php']);
  _paq.push(['setSiteId', <?= json_encode($matomoSiteId) ?>]);
  _paq.push(['setCustomUrl', <?= json_encode($matomoPageUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>]);
  _paq.push(['setDocumentTitle', <?= json_encode($matomoDocumentTitle, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>]);
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
