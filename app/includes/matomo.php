<?php
declare(strict_types=1);

require_once __DIR__ . '/../matomo_helpers.php';

$options = isset($matomoOptions) && is_array($matomoOptions) ? $matomoOptions : [];

echo render_matomo_tracking_html($options);
