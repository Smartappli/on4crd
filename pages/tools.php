<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = require __DIR__ . '/../app/i18n/tools.php';
$trCache = [];
$tr = static function (string $key, string $fallback = '') use ($i18n, $locale, &$trCache): string {
    if (array_key_exists($key, $trCache)) {
        return $trCache[$key];
    }

    $pool = [];
    foreach ($i18n as $lang => $translations) {
        if (isset($translations[$key]) && is_string($translations[$key])) {
            $pool[$lang] = $translations[$key];
        }
    }

    $value = trim(i18n_localized_value($pool, $locale, 'fr'));
    if ($value === '') {
        $value = trim((string) ($i18n['fr'][$key] ?? $fallback));
    }

    $trCache[$key] = $value;
    return $value;
};


$initialToolKeys = [
    'grid_title', 'address', 'addr_ph', 'calc_grid', 'found_address', 'coords', 'locator',
    'quarter_wave_calc', 'frequency_mhz', 'velocity_factor', 'quarter_wave_result',
    'erp_calc', 'tx_power_w', 'feedline_loss_db', 'antenna_gain_dbd', 'erp_result',
];
$t = [];
foreach (array_keys($i18n['fr']) as $key) {
    $value = trim(i18n_localized_value($i18n, $locale, $key));
    if ($value === '') {
        $value = trim((string) ($i18n['fr'][$key] ?? ''));
    }
    $t[$key] = $value;
}
$tr = static function (string $key, string $fallback = '') use ($t): string {
    if (array_key_exists($key, $t)) {
        return trim((string) $t[$key]);
    }

    return trim($fallback);
};

$labelCategoryAntenna = $tr('category_antenna', 'Antenna & propagation');
$labelQuarterWaveCalc = $tr('quarter_wave_calc', 'Quarter-wave length');
$labelErpCalc = $tr('erp_calc', 'Estimated ERP');
$labelTxPowerW = $tr('tx_power_w', 'TX power (W)');
$labelFeedlineLossDb = $tr('feedline_loss_db', 'Feedline loss (dB)');
$labelAntennaGainDbd = $tr('antenna_gain_dbd', 'Antenna gain (dBd)');
$labelErpResult = $tr('erp_result', 'Estimated ERP');
$labelQuarterWaveResult = $tr('quarter_wave_result', 'Estimated length');
$labelVelocityFactor = $tr('velocity_factor', 'Velocity factor (0-1)');
$toolCatalog = require __DIR__ . '/../app/config/tools_catalog.php';
$toolPanelMap = require __DIR__ . '/../app/config/tools_panels.php';
$toolGridFallbackPath = __DIR__ . '/tools_panels/tool_grid.php';
$hasToolGridFallback = is_file($toolGridFallbackPath);
$resolveToolTitle = static function (array $entry) use ($tr): string {
    if (isset($entry['title'])) {
        return (string) $entry['title'];
    }

    $key = (string) ($entry['title_key'] ?? '');
    if ($key !== '') {
        return $tr($key, (string) ($entry['id'] ?? $key));
    }

    if (($entry['title_pattern'] ?? '') === 'conv_dot' && isset($entry['left_key'], $entry['right_key'])) {
        $left = $tr((string) $entry['left_key']);
        $right = $tr((string) $entry['right_key']);
        if ($left !== '' && $right !== '') {
            return $left . ' · ' . $right;
        }
    }

    return (string) $entry['id'];
};

$canRenderToolId = static function (string $toolId) use ($toolPanelMap, $hasToolGridFallback): bool {
    if (isset($toolPanelMap[$toolId])) {
        $partialPath = __DIR__ . '/tools_panels/' . $toolPanelMap[$toolId];
        return is_file($partialPath);
    }

    return $toolId === 'tool-grid' && $hasToolGridFallback;
};

$buildTools = static function (array $entries) use ($resolveToolTitle, $canRenderToolId): array {
    $tools = [];
    foreach ($entries as $entry) {
        $id = (string) ($entry['id'] ?? '');
        if ($id === '') {
            continue;
        }

        if (!$canRenderToolId($id)) {
            continue;
        }

        $tools[] = [
            'id' => $id,
            'title' => $resolveToolTitle($entry),
        ];
    }

    return $tools;
};

$locatorTools = $buildTools($toolCatalog['locators'] ?? []);
$conversionTools = $buildTools($toolCatalog['conversion'] ?? []);
$antennaTools = $buildTools($toolCatalog['antenna'] ?? []);
$powerTools = $buildTools($toolCatalog['power'] ?? []);
$advancedPropagationTools = $buildTools($toolCatalog['advanced_propagation'] ?? []);
$rfMeasureTools = $buildTools($toolCatalog['rf_measures'] ?? []);
$radioMathTools = $buildTools($toolCatalog['radio_math'] ?? []);
set_page_meta([
    'title' => $tr('title', 'Outils radioamateur'),
    'description' => $tr('grid_title', 'Maidenhead locator map and converter'),
    'schema_type' => 'WebPage',
]);
$jsI18n = [
    'err_enter_address' => $tr('err_enter_address'),
    'err_geocode_unavailable' => $tr('err_geocode_unavailable'),
    'err_address_not_found' => $tr('err_address_not_found'),
    'err_invalid_coords' => $tr('err_invalid_coords'),
    'err_grid_calc' => $tr('err_grid_calc'),
    'err_tool_load' => $tr('err_tool_load'),
    'meters_unit' => $tr('meters_unit'),
    'km_unit' => $tr('km_unit'),
    'watts_out_label' => $tr('watts_out_label'),
    'dbuv_label' => $tr('dbuv_label', 'dBµV'),
];

$renderFallbackToolGridPanel = static function () use ($toolGridFallbackPath, $hasToolGridFallback): bool {
    $fallbackPath = $toolGridFallbackPath;
    if (!$hasToolGridFallback) {
        return false;
    }

    require $fallbackPath;
    return true;
};


$toolPanelTranslationKeys = [];
$catalogGroups = ['locators', 'conversion', 'antenna', 'power', 'advanced_propagation', 'rf_measures', 'radio_math'];
foreach ($catalogGroups as $group) {
    foreach (($toolCatalog[$group] ?? []) as $entry) {
        $id = (string) ($entry['id'] ?? '');
        if ($id === '') {
            continue;
        }

        $keys = [];
        if (isset($entry['title_key']) && is_string($entry['title_key']) && $entry['title_key'] !== '') {
            $keys[] = $entry['title_key'];
        }
        if (isset($entry['left_key']) && is_string($entry['left_key']) && $entry['left_key'] !== '') {
            $keys[] = $entry['left_key'];
        }
        if (isset($entry['right_key']) && is_string($entry['right_key']) && $entry['right_key'] !== '') {
            $keys[] = $entry['right_key'];
        }
        if ($keys !== []) {
            $toolPanelTranslationKeys[$id] = array_values(array_unique($keys));
        }
    }
}
$toolPanelTranslationKeys['tool-grid'] = array_values(array_unique(array_merge(
    $toolPanelTranslationKeys['tool-grid'] ?? [],
    ['grid_title', 'address', 'addr_ph', 'calc_grid', 'found_address', 'coords', 'locator']
)));
$toolPanelTranslationKeys['tool-quarter-wave'] = array_values(array_unique(array_merge(
    $toolPanelTranslationKeys['tool-quarter-wave'] ?? [],
    ['quarter_wave_calc', 'freq_mhz', 'velocity_factor', 'calc', 'quarter_wave_result']
)));
$toolPanelTranslationKeys['tool-erp'] = array_values(array_unique(array_merge(
    $toolPanelTranslationKeys['tool-erp'] ?? [],
    ['erp_calc', 'tx_power_w', 'feedline_loss_db', 'antenna_gain_dbd', 'calc', 'erp_result']
)));

$extractPanelTranslationKeys = static function (string $toolId) use ($toolPanelMap): array {
    static $panelKeyCache = [];
    if (array_key_exists($toolId, $panelKeyCache)) {
        return $panelKeyCache[$toolId];
    }

    $partialFile = $toolPanelMap[$toolId] ?? null;
    if ($partialFile === null) {
        $panelKeyCache[$toolId] = [];
        return $panelKeyCache[$toolId];
    }

    $partialPath = __DIR__ . '/tools_panels/' . $partialFile;
    if (!is_file($partialPath)) {
        $panelKeyCache[$toolId] = [];
        return $panelKeyCache[$toolId];
    }

    $content = (string) @file_get_contents($partialPath);
    if ($content === '') {
        $panelKeyCache[$toolId] = [];
        return $panelKeyCache[$toolId];
    }

    preg_match_all('/\$t\[(?:\'|")([a-z0-9_]+)(?:\'|")\]/i', $content, $matches);
    $panelKeyCache[$toolId] = isset($matches[1]) ? array_values(array_unique(array_map('strval', $matches[1]))) : [];

    return $panelKeyCache[$toolId];
};

$renderToolPanel = static function (string $toolId) use ($toolPanelMap): bool {
    $partialFile = $toolPanelMap[$toolId] ?? null;
    if ($partialFile === null) {
        return false;
    }

    $partialPath = __DIR__ . '/tools_panels/' . $partialFile;
    if (!is_file($partialPath)) {
        return false;
    }

    require $partialPath;
    return true;
};

if (($_GET['ajax'] ?? '') === 'tool_panel') {
    $toolId = (string) ($_GET['id'] ?? '');
    if ($toolId === '' || preg_match('/^tool-[a-z0-9-]+$/', $toolId) !== 1) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=UTF-8');
        echo $tr('err_tool_load', 'Tool loading error');
        return;
    }

    $panelKeys = $toolPanelTranslationKeys[$toolId] ?? [];
    $panelKeys = array_values(array_unique(array_merge($panelKeys, $extractPanelTranslationKeys($toolId))));
    if ($panelKeys !== []) {
        $panelTranslations = [];
        foreach ($panelKeys as $k) {
            $panelTranslations[$k] = $tr($k, (string) ($i18n['fr'][$k] ?? ''));
        }
        $t = $panelTranslations + $t;
    }

    if (!$canRenderToolId($toolId)) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=UTF-8');
        echo $tr('err_tool_load', 'Tool loading error');
        return;
    }

    ob_start();
    $rendered = $renderToolPanel($toolId) || ($toolId === 'tool-grid' && $renderFallbackToolGridPanel());
    $panelHtml = (string) ob_get_clean();

    if (!$rendered) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo $tr('err_tool_load', 'Tool loading error');
        return;
    }

    header('Cache-Control: public, max-age=600');
    header('Content-Type: text/html; charset=UTF-8');
    echo $panelHtml;
    return;
}

ob_start();
?>
<section class="card">
    <h1 class="tools-page-title"><?= e($tr('title', 'Outils radioamateur')) ?></h1>
    <div class="tools-layout">
    <aside class="tools-index card">
        <h2><?= e($tr('tool_index')) ?></h2>
        <p class="help"><?= e($tr('choose_tool')) ?></p>
        <details class="tools-index-group">
            <summary><?= e($tr('category_locators')) ?></summary>
            <ul>
                <?php foreach ($locatorTools as $tool): ?>
                    <li><a href="#<?= e((string) $tool['id']) ?>" data-tool-target="<?= e((string) $tool['id']) ?>"><?= e((string) $tool['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </details>
        <details class="tools-index-group">
            <summary><?= e($tr('category_conversions')) ?></summary>
            <ul>
                <?php foreach ($conversionTools as $tool): ?>
                    <li><a href="#<?= e((string) $tool['id']) ?>" data-tool-target="<?= e((string) $tool['id']) ?>"><?= e((string) $tool['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </details>
        <details class="tools-index-group">
            <summary><?= e($tr('category_antenna', 'Antenna & propagation')) ?></summary>
            <ul>
                <?php foreach ($antennaTools as $tool): ?>
                    <li><a href="#<?= e((string) $tool['id']) ?>" data-tool-target="<?= e((string) $tool['id']) ?>"><?= e((string) $tool['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </details>

        <details class="tools-index-group">
            <summary><?= e($tr('category_power')) ?></summary>
            <ul>
                <?php foreach ($powerTools as $tool): ?>
                    <li><a href="#<?= e((string) $tool['id']) ?>" data-tool-target="<?= e((string) $tool['id']) ?>"><?= e((string) $tool['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </details>
        <details class="tools-index-group">
            <summary><?= e($tr('category_propagation_adv')) ?></summary>
            <ul>
                <?php foreach ($advancedPropagationTools as $tool): ?>
                    <li><a href="#<?= e((string) $tool['id']) ?>" data-tool-target="<?= e((string) $tool['id']) ?>"><?= e((string) $tool['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </details>

        <details class="tools-index-group">
            <summary><?= e($tr('category_rf_measures')) ?></summary>
            <ul>
                <?php foreach ($rfMeasureTools as $tool): ?>
                    <li><a href="#<?= e((string) $tool['id']) ?>" data-tool-target="<?= e((string) $tool['id']) ?>"><?= e((string) $tool['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </details>

        <details class="tools-index-group">
            <summary><?= e($tr('category_radio_math')) ?></summary>
            <ul>
                <?php foreach ($radioMathTools as $tool): ?>
                    <li><a href="#<?= e((string) $tool['id']) ?>" data-tool-target="<?= e((string) $tool['id']) ?>"><?= e((string) $tool['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </details>
    </aside>
    <div id="tools-content" class="tools-content">
        <?php
        if (!$renderToolPanel('tool-grid') && !$renderFallbackToolGridPanel()) {
            trigger_error('Missing tools panel partial for id: tool-grid', E_USER_WARNING);
        }
        ?>
    </div>
    </div>
    <p id="grid-tool-error" class="flash flash-error is-hidden" style="margin-top:1rem;"></p>
</section>

<script nonce="<?= e(csp_nonce()) ?>">
<?php require __DIR__ . '/tools_script.js.php'; ?>
</script>
<?php
echo render_layout((string) ob_get_clean(), $tr('title', 'Outils radioamateur'));
