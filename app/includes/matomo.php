<?php
declare(strict_types=1);

$matomoUrl = rtrim((string) config('tracking.matomo_url', ''), '/');
$matomoSiteId = (string) config('tracking.matomo_site_id', '');
$matomoRequireConsent = (bool) config('tracking.matomo_require_consent', true);
$matomoDisableCookies = (bool) config('tracking.matomo_disable_cookies', true);
$matomoConsentGiven = (string) ($_COOKIE['on4crd_tracking_consent'] ?? '') === '1';
$matomoCanTrack = $matomoUrl !== '' && $matomoSiteId !== '' && (!$matomoRequireConsent || $matomoConsentGiven);

if (!$matomoCanTrack) {
    return;
}
?>
<script nonce="<?= e(csp_nonce()) ?>">
  var _paq = window._paq = window._paq || [];
  _paq.push(['setDoNotTrack', true]);
  _paq.push(['setUserIsAnonymous', true]);
  <?php if ($matomoDisableCookies): ?>
  _paq.push(['disableCookies']);
  <?php endif; ?>
  <?php if ($matomoRequireConsent): ?>
  _paq.push(['requireConsent']);
  _paq.push(['rememberConsentGiven']);
  <?php endif; ?>
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    var u = <?= json_encode($matomoUrl . '/') ?>;
    _paq.push(['setTrackerUrl', u + 'matomo.php']);
    _paq.push(['setSiteId', <?= json_encode($matomoSiteId) ?>]);
    var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
    g.async = true;
    g.src = u + 'matomo.js';
    s.parentNode.insertBefore(g, s);
  })();
</script>
