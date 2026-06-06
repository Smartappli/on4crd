<?php
declare(strict_types=1);

$t = isset($t) && is_array($t) ? $t : [];
$conversionTools = isset($conversionTools) && is_array($conversionTools) ? $conversionTools : [];
$radioMathTools = isset($radioMathTools) && is_array($radioMathTools) ? $radioMathTools : [];
$unitConversionGroups = [
    'rf' => [
        'label' => (string) ($t['unit_conv_group_rf'] ?? 'Radio / RF'),
        'units' => [
            'hz' => ['label' => 'Hz', 'factor' => 1.0],
            'khz' => ['label' => 'kHz', 'factor' => 1_000.0],
            'mhz' => ['label' => 'MHz', 'factor' => 1_000_000.0],
            'ghz' => ['label' => 'GHz', 'factor' => 1_000_000_000.0],
        ],
        'presets' => ['145.5', '433.5', '7100'],
        'default_from' => 'mhz',
        'default_to' => 'khz',
    ],
    'power' => [
        'label' => (string) ($t['unit_conv_group_power'] ?? 'Puissance'),
        'units' => [
            'w' => ['label' => 'W', 'factor' => 1.0],
            'kw' => ['label' => 'kW', 'factor' => 1_000.0],
            'mw' => ['label' => 'mW', 'factor' => 0.001],
            'dbm' => ['label' => 'dBm', 'kind' => 'dbm'],
            'dbw' => ['label' => 'dBW', 'kind' => 'dbw'],
        ],
        'presets' => ['0.005', '5', '100'],
        'default_from' => 'w',
        'default_to' => 'dbm',
    ],
    'voltage' => [
        'label' => (string) ($t['unit_conv_group_voltage'] ?? 'Tension sinusoidale'),
        'units' => [
            'vrms' => ['label' => 'Vrms', 'factor' => 1.0],
            'vpp' => ['label' => 'Vpp', 'factor' => 1 / (2 * sqrt(2))],
            'vpk' => ['label' => 'Vpk', 'factor' => 1 / sqrt(2)],
        ],
        'presets' => ['1', '5', '13.8'],
        'default_from' => 'vrms',
        'default_to' => 'vpp',
    ],
    'length' => [
        'label' => (string) ($t['unit_conv_group_length'] ?? 'Longueur'),
        'units' => [
            'mm' => ['label' => 'mm', 'factor' => 0.001],
            'cm' => ['label' => 'cm', 'factor' => 0.01],
            'm' => ['label' => 'm', 'factor' => 1.0],
            'km' => ['label' => 'km', 'factor' => 1_000.0],
            'in' => ['label' => 'in', 'factor' => 0.0254],
            'ft' => ['label' => 'ft', 'factor' => 0.3048],
        ],
        'presets' => ['0.25', '10', '100'],
        'default_from' => 'm',
        'default_to' => 'ft',
    ],
    'energy' => [
        'label' => (string) ($t['unit_conv_group_energy'] ?? 'Energie'),
        'units' => [
            'j' => ['label' => 'J', 'factor' => 1.0],
            'wh' => ['label' => 'Wh', 'factor' => 3_600.0],
            'kwh' => ['label' => 'kWh', 'factor' => 3_600_000.0],
        ],
        'presets' => ['3600', '12', '1000'],
        'default_from' => 'wh',
        'default_to' => 'j',
    ],
    'time' => [
        'label' => (string) ($t['unit_conv_group_time'] ?? 'Temps'),
        'units' => [
            'ms' => ['label' => 'ms', 'factor' => 0.001],
            's' => ['label' => 's', 'factor' => 1.0],
            'min' => ['label' => 'min', 'factor' => 60.0],
            'h' => ['label' => 'h', 'factor' => 3_600.0],
        ],
        'presets' => ['1000', '60', '3600'],
        'default_from' => 'ms',
        'default_to' => 's',
    ],
    'temperature' => [
        'label' => (string) ($t['unit_conv_group_temperature'] ?? 'Temperature'),
        'units' => [
            'c' => ['label' => 'deg C', 'kind' => 'c'],
            'f' => ['label' => 'deg F', 'kind' => 'f'],
            'k' => ['label' => 'K', 'kind' => 'k'],
        ],
        'presets' => ['0', '20', '100'],
        'default_from' => 'c',
        'default_to' => 'f',
    ],
    'rotation' => [
        'label' => (string) ($t['unit_conv_group_rotation'] ?? 'Rotation'),
        'units' => [
            'rpm' => ['label' => 'RPM', 'factor' => 1 / 60],
            'rps' => ['label' => 'RPS', 'factor' => 1.0],
            'hz' => ['label' => 'Hz', 'factor' => 1.0],
        ],
        'presets' => ['60', '1200', '3000'],
        'default_from' => 'rpm',
        'default_to' => 'rps',
    ],
    'field' => [
        'label' => (string) ($t['unit_conv_group_field'] ?? 'Signal level'),
        'units' => [
            'dbuv' => ['label' => 'dBuV', 'kind' => 'dbuv'],
            'sunit' => ['label' => 'S-unit', 'kind' => 'sunit'],
        ],
        'presets' => ['9', '59', '73'],
        'default_from' => 'sunit',
        'default_to' => 'dbuv',
    ],
];
$unitConversionPanelId = (string) ($unitConversionPanelId ?? 'tool-unit-conversions');
?>
<article class="card tool-panel is-hidden" id="<?= e($unitConversionPanelId) ?>" data-tool-panel>
    <div class="section-header">
        <div>
            <h2><?= e((string) ($t['unit_conv_title'] ?? 'Unit conversion')) ?></h2>
            <p class="help"><?= e((string) ($t['unit_conv_help'] ?? 'Ham radio multi-unit converter.')) ?></p>
        </div>
        <button type="button" class="button ghost" id="unit-conv-swap"><?= e((string) ($t['unit_conv_swap'] ?? 'Inverser')) ?></button>
    </div>

    <div class="grid-3">
        <label><?= e((string) ($t['unit_conv_family'] ?? 'Famille')) ?>
            <select id="unit-conv-group">
                <?php foreach ($unitConversionGroups as $groupCode => $group): ?>
                    <option value="<?= e($groupCode) ?>"><?= e((string) $group['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label><?= e((string) ($t['value_in'] ?? 'Input value')) ?>
            <input id="unit-conv-input" type="text" data-step="any" value="145.5" inputmode="decimal">
        </label>
        <label><?= e((string) ($t['value_out'] ?? 'Output value')) ?>
            <output id="unit-conv-output" class="result-box">-</output>
        </label>
    </div>

    <div class="grid-2">
        <label><?= e((string) ($t['unit_conv_from'] ?? 'Depuis')) ?>
            <select id="unit-conv-from"></select>
        </label>
        <label><?= e((string) ($t['unit_conv_to'] ?? 'Vers')) ?>
            <select id="unit-conv-to"></select>
        </label>
    </div>

    <div class="actions" id="unit-conv-presets" aria-label="<?= e((string) ($t['unit_conv_presets'] ?? 'Valeurs rapides')) ?>"></div>

    <div class="grid-2">
        <section class="inner-card">
            <h3><?= e((string) ($t['unit_conv_reference'] ?? 'Reference')) ?></h3>
            <p id="unit-conv-reference" class="help">-</p>
        </section>
        <section class="inner-card">
            <h3><?= e((string) ($t['unit_conv_quick_links'] ?? 'Detailed converters')) ?></h3>
            <div class="actions">
                <?php
                $unitTools = [
                    'tool-power', 'tool-freq-wave', 'tool-dbuv', 'tool-gain-conv',
                    'tool-kw-w', 'tool-hz-khz', 'tool-in-mm', 'tool-c-f', 'tool-vpp-vrms', 'tool-sunit-dbuv',
                ];
                $unitToolFallbackLabels = [
                    'tool-power' => (string) ($t['power'] ?? 'Power (W <-> dBm)'),
                    'tool-freq-wave' => (string) ($t['freq_wave'] ?? 'Frequency to wavelength'),
                    'tool-dbuv' => (string) ($t['dbuv_calc'] ?? 'dBm to dBuV'),
                    'tool-gain-conv' => (string) ($t['gain_conv_calc'] ?? 'dBd to dBi'),
                    'tool-kw-w' => (string) ($t['kw_w_calc'] ?? 'kW to W'),
                    'tool-hz-khz' => (string) ($t['hz_khz_calc'] ?? 'Hz to kHz'),
                    'tool-in-mm' => (string) ($t['in_mm_calc'] ?? 'Inch to mm'),
                    'tool-c-f' => (string) ($t['c_f_calc'] ?? 'C to F'),
                    'tool-vpp-vrms' => (string) ($t['vpp_vrms_calc'] ?? 'Vpp to Vrms'),
                    'tool-sunit-dbuv' => (string) ($t['sunit_dbuv_calc'] ?? 'S-unit to dBuV'),
                ];
                foreach ($unitTools as $unitToolId):
                    $unitLabel = '';
                    foreach ($conversionTools as $tool) {
                        if ((string) ($tool['id'] ?? '') === $unitToolId) {
                            $unitLabel = (string) ($tool['title'] ?? '');
                            break;
                        }
                    }
                    foreach ($radioMathTools as $tool) {
                        if ($unitLabel === '' && (string) ($tool['id'] ?? '') === $unitToolId) {
                            $unitLabel = (string) ($tool['title'] ?? '');
                            break;
                        }
                    }
                    if ($unitLabel === '') {
                        $unitLabel = $unitToolFallbackLabels[$unitToolId] ?? $unitToolId;
                    }
                ?>
                    <a class="button secondary" href="#<?= e($unitToolId) ?>" data-tool-target="<?= e($unitToolId) ?>"><?= e($unitLabel) ?></a>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <script type="application/json" id="unit-conv-data">
        <?= json_encode($unitConversionGroups, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    </script>
</article>
