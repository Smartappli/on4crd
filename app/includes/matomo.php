<?php
declare(strict_types=1);
?>
<!-- Matomo tracking include: collez ici votre code Matomo unique. Ce fichier est inclus dans toutes les pages HTML via render_layout(). -->
<?php if (!empty(config('tracking.matomo_site_id')) && !empty(config('tracking.matomo_url'))): ?>
<script>
  var _paq = window._paq = window._paq || [];
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    var u = <?= json_encode(rtrim((string) config('tracking.matomo_url'), '/') . '/') ?>;
    _paq.push(['setTrackerUrl', u + 'matomo.php']);
    _paq.push(['setSiteId', <?= json_encode((string) config('tracking.matomo_site_id')) ?>]);
    var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
    g.async = true;
    g.src = u + 'matomo.js';
    s.parentNode.insertBefore(g, s);
  })();
</script>
<?php endif; ?>
