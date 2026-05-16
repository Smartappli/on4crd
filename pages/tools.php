<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = require __DIR__ . '/../app/i18n/tools.php';
$t = $i18n[$locale] ?? $i18n['fr'];
$labelCategoryAntenna = (string) ($t['category_antenna'] ?? 'Antenna & propagation');
$labelQuarterWaveCalc = (string) ($t['quarter_wave_calc'] ?? 'Quarter-wave length');
$labelErpCalc = (string) ($t['erp_calc'] ?? 'Estimated ERP');
$labelTxPowerW = (string) ($t['tx_power_w'] ?? 'TX power (W)');
$labelFeedlineLossDb = (string) ($t['feedline_loss_db'] ?? 'Feedline loss (dB)');
$labelAntennaGainDbd = (string) ($t['antenna_gain_dbd'] ?? 'Antenna gain (dBd)');
$labelErpResult = (string) ($t['erp_result'] ?? 'Estimated ERP');
$labelQuarterWaveResult = (string) ($t['quarter_wave_result'] ?? 'Estimated length');
$labelVelocityFactor = (string) ($t['velocity_factor'] ?? 'Velocity factor (0-1)');

$toolCatalog = require __DIR__ . '/../app/config/tools_catalog.php';
$toolPanelMap = require __DIR__ . '/../app/config/tools_panels.php';
$resolveToolTitle = static function (array $entry) use ($t): string {
    if (isset($entry['title'])) {
        return (string) $entry['title'];
    }

    $key = (string) ($entry['title_key'] ?? '');
    if ($key !== '' && isset($t[$key])) {
        return (string) $t[$key];
    }

    if (($entry['title_pattern'] ?? '') === 'conv_dot' && isset($entry['left_key'], $entry['right_key'])) {
        return ((string) ($t[(string) $entry['left_key']] ?? $entry['left_key'])) . ' · ' . ((string) ($t[(string) $entry['right_key']] ?? $entry['right_key']));
    }

    return (string) $entry['id'];
};

$buildTools = static function (array $entries) use ($resolveToolTitle, $toolPanelMap): array {
    $tools = [];
    foreach ($entries as $entry) {
        $id = (string) ($entry['id'] ?? '');
        $partialFile = $toolPanelMap[$id] ?? null;
        if ($id === '' || $partialFile === null) {
            continue;
        }

        $partialPath = __DIR__ . '/tools_panels/' . $partialFile;
        if (!is_file($partialPath)) {
            continue;
        }

        $tools[] = [
            'id' => $id,
            'title' => $resolveToolTitle($entry),
        ];
    }

    return $tools;
};

$conversionTools = $buildTools($toolCatalog['conversion'] ?? []);
$antennaTools = $buildTools($toolCatalog['antenna'] ?? []);
$powerTools = $buildTools($toolCatalog['power'] ?? []);
$advancedPropagationTools = $buildTools($toolCatalog['advanced_propagation'] ?? []);
$rfMeasureTools = $buildTools($toolCatalog['rf_measures'] ?? []);
$radioMathTools = $buildTools($toolCatalog['radio_math'] ?? []);
set_page_meta([
    'title' => (string) ($t['title'] ?? $i18n['fr']['title']),
    'description' => (string) ($t['grid_title'] ?? $i18n['fr']['grid_title']),
    'schema_type' => 'WebPage',
]);
$jsI18n = [
    'err_enter_address' => (string) ($t['err_enter_address'] ?? $i18n['fr']['err_enter_address']),
    'err_geocode_unavailable' => (string) ($t['err_geocode_unavailable'] ?? $i18n['fr']['err_geocode_unavailable']),
    'err_address_not_found' => (string) ($t['err_address_not_found'] ?? $i18n['fr']['err_address_not_found']),
    'err_invalid_coords' => (string) ($t['err_invalid_coords'] ?? $i18n['fr']['err_invalid_coords']),
    'err_grid_calc' => (string) ($t['err_grid_calc'] ?? $i18n['fr']['err_grid_calc']),
    'err_tool_load' => (string) ($t['err_tool_load'] ?? $i18n['fr']['err_tool_load']),
    'meters_unit' => (string) ($t['meters_unit'] ?? $i18n['fr']['meters_unit']),
    'km_unit' => (string) ($t['km_unit'] ?? $i18n['fr']['km_unit']),
    'watts_out_label' => (string) ($t['watts_out_label'] ?? $i18n['fr']['watts_out_label']),
    'dbuv_label' => (string) ($t['dbuv_label'] ?? ($i18n['fr']['dbuv_label'] ?? 'dBµV')),
];

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
        echo 'Invalid tool panel id';
        return;
    }

    if (!isset($toolPanelMap[$toolId])) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Unknown tool panel';
        return;
    }

    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: public, max-age=600');

    if (!$renderToolPanel($toolId)) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Missing tool panel';
    }

    return;
}


ob_start();
?>
<section class="card">
    <h1 class="tools-page-title"><?= e((string) $t['title']) ?></h1>
    <div class="tools-layout">
    <aside class="tools-index card">
        <h2><?= e((string) $t['tool_index']) ?></h2>
        <p class="help"><?= e((string) $t['choose_tool']) ?></p>
        <details class="tools-index-group">
            <summary><?= e((string) $t['category_locators']) ?></summary>
            <ul>
                <li><a href="#tool-grid" data-tool-target="tool-grid"><?= e((string) $t['grid_title']) ?></a></li>
                <li><a href="#tool-distance" data-tool-target="tool-distance"><?= e((string) $t['distance']) ?></a></li>
            </ul>
        </details>
        <details class="tools-index-group">
            <summary><?= e((string) $t['category_conversions']) ?></summary>
            <ul>
                <?php foreach ($conversionTools as $tool): ?>
                    <li><a href="#<?= e((string) $tool['id']) ?>" data-tool-target="<?= e((string) $tool['id']) ?>"><?= e((string) $tool['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </details>
        <details class="tools-index-group">
            <summary><?= e($labelCategoryAntenna) ?></summary>
            <ul>
                <?php foreach ($antennaTools as $tool): ?>
                    <li><a href="#<?= e((string) $tool['id']) ?>" data-tool-target="<?= e((string) $tool['id']) ?>"><?= e((string) $tool['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </details>

        <details class="tools-index-group">
            <summary><?= e((string) $t['category_power']) ?></summary>
            <ul>
                <?php foreach ($powerTools as $tool): ?>
                    <li><a href="#<?= e((string) $tool['id']) ?>" data-tool-target="<?= e((string) $tool['id']) ?>"><?= e((string) $tool['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </details>
        <details class="tools-index-group">
            <summary><?= e((string) $t['category_propagation_adv']) ?></summary>
            <ul>
                <?php foreach ($advancedPropagationTools as $tool): ?>
                    <li><a href="#<?= e((string) $tool['id']) ?>" data-tool-target="<?= e((string) $tool['id']) ?>"><?= e((string) $tool['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </details>

        <details class="tools-index-group">
            <summary><?= e((string) $t['category_rf_measures']) ?></summary>
            <ul>
                <?php foreach ($rfMeasureTools as $tool): ?>
                    <li><a href="#<?= e((string) $tool['id']) ?>" data-tool-target="<?= e((string) $tool['id']) ?>"><?= e((string) $tool['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </details>

        <details class="tools-index-group">
            <summary><?= e((string) $t['category_radio_math']) ?></summary>
            <ul>
                <?php foreach ($radioMathTools as $tool): ?>
                    <li><a href="#<?= e((string) $tool['id']) ?>" data-tool-target="<?= e((string) $tool['id']) ?>"><?= e((string) $tool['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </details>
    </aside>
    <div id="tools-content" class="tools-content">
        <?php
        if (!$renderToolPanel('tool-grid')) {
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
echo render_layout((string) ob_get_clean(), (string) ($t['title'] ?? 'Outils'));
