<?php
declare(strict_types=1);

$user = require_login();
$memberId = (int) ($user['id'] ?? 0);
$locale = current_locale();
$qt = i18n_domain_translator('qsl', $locale);
$drawPresetPalettes = [
    'club_blue' => ['label' => 'Club blue (gradient)', 'primary' => '#0B1F3A', 'secondary' => '#1D4ED8'],
    'sunset' => ['label' => 'Sunset (gradient)', 'primary' => '#7C2D12', 'secondary' => '#F97316'],
    'northern' => ['label' => 'Aurora (gradient)', 'primary' => '#0F766E', 'secondary' => '#22D3EE'],
    'forest' => ['label' => 'Forest (solid color)', 'primary' => '#166534', 'secondary' => '#166534'],
    'slate' => ['label' => 'Slate (solid color)', 'primary' => '#334155', 'secondary' => '#334155'],
];

db()->exec(
    'CREATE TABLE IF NOT EXISTS qsl_background_presets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT NOT NULL,
        label VARCHAR(120) NOT NULL,
        type VARCHAR(16) NOT NULL,
        image_data_uri LONGTEXT DEFAULT NULL,
        color_primary VARCHAR(7) DEFAULT NULL,
        color_secondary VARCHAR(7) DEFAULT NULL,
        is_default TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )'
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');
        $isAjaxRequest = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

        if ($action === 'save_background_image') {
            $label = trim((string) ($_POST['background_label'] ?? 'Image background'));
            $label = mb_safe_substr($label !== '' ? $label : 'Image background', 0, 120);
            $dataUri = qsl_background_upload_to_data_uri($_FILES['background_image'] ?? null);
            if ($dataUri === '') {
                throw new RuntimeException($qt('err_select_bg'));
            }
            $setDefault = ((string) ($_POST['set_default'] ?? '') === '1');
            if ($setDefault) {
                db()->prepare('UPDATE qsl_background_presets SET is_default = 0 WHERE member_id = ?')->execute([$memberId]);
            }
            db()->prepare(
                'INSERT INTO qsl_background_presets (member_id, label, type, image_data_uri, color_primary, color_secondary, is_default)
                 VALUES (?, ?, ?, ?, NULL, NULL, ?)'
            )->execute([$memberId, $label, 'image', $dataUri, $setDefault ? 1 : 0]);
            set_flash('success', $qt('ok_bg_image'));
        } elseif ($action === 'save_background_gradient') {
            $label = trim((string) ($_POST['gradient_label'] ?? 'Gradient background'));
            $label = mb_safe_substr($label !== '' ? $label : 'Gradient background', 0, 120);
            $primary = trim((string) ($_POST['background_primary'] ?? '#0B1F3A'));
            $secondary = trim((string) ($_POST['background_secondary'] ?? '#1D4ED8'));
            if (preg_match('/^#[A-Fa-f0-9]{6}$/', $primary) !== 1 || preg_match('/^#[A-Fa-f0-9]{6}$/', $secondary) !== 1) {
                throw new RuntimeException($qt('err_gradient_invalid'));
            }
            $setDefault = ((string) ($_POST['set_default'] ?? '') === '1');
            if ($setDefault) {
                db()->prepare('UPDATE qsl_background_presets SET is_default = 0 WHERE member_id = ?')->execute([$memberId]);
            }
            db()->prepare(
                'INSERT INTO qsl_background_presets (member_id, label, type, image_data_uri, color_primary, color_secondary, is_default)
                 VALUES (?, ?, ?, NULL, ?, ?, ?)'
            )->execute([$memberId, $label, 'gradient', strtoupper($primary), strtoupper($secondary), $setDefault ? 1 : 0]);
            set_flash('success', $qt('ok_bg_gradient'));
        } elseif ($action === 'save_background_solid') {
            $label = trim((string) ($_POST['solid_label'] ?? 'Solid color background'));
            $label = mb_safe_substr($label !== '' ? $label : 'Solid color background', 0, 120);
            $solidColor = trim((string) ($_POST['background_solid'] ?? '#1E293B'));
            if (preg_match('/^#[A-Fa-f0-9]{6}$/', $solidColor) !== 1) {
                throw new RuntimeException($qt('err_solid_invalid'));
            }
            $setDefault = ((string) ($_POST['set_default'] ?? '') === '1');
            if ($setDefault) {
                db()->prepare('UPDATE qsl_background_presets SET is_default = 0 WHERE member_id = ?')->execute([$memberId]);
            }
            $normalizedColor = strtoupper($solidColor);
            db()->prepare(
                'INSERT INTO qsl_background_presets (member_id, label, type, image_data_uri, color_primary, color_secondary, is_default)
                 VALUES (?, ?, ?, NULL, ?, ?, ?)'
            )->execute([$memberId, $label, 'gradient', $normalizedColor, $normalizedColor, $setDefault ? 1 : 0]);
            set_flash('success', $qt('ok_bg_solid'));
        } elseif ($action === 'save_background_palette') {
            $paletteKey = trim((string) ($_POST['preset_palette'] ?? ''));
            $palette = $drawPresetPalettes[$paletteKey] ?? null;
            if (!is_array($palette)) {
                throw new RuntimeException($qt('err_palette_invalid'));
            }
            $label = trim((string) ($_POST['palette_label'] ?? (string) ($palette['label'] ?? 'Preset palette')));
            $label = mb_safe_substr($label !== '' ? $label : (string) ($palette['label'] ?? 'Preset palette'), 0, 120);
            $primary = (string) ($palette['primary'] ?? '#0B1F3A');
            $secondary = (string) ($palette['secondary'] ?? '#1D4ED8');
            $setDefault = ((string) ($_POST['set_default'] ?? '') === '1');
            if ($setDefault) {
                db()->prepare('UPDATE qsl_background_presets SET is_default = 0 WHERE member_id = ?')->execute([$memberId]);
            }
            db()->prepare(
                'INSERT INTO qsl_background_presets (member_id, label, type, image_data_uri, color_primary, color_secondary, is_default)
                 VALUES (?, ?, ?, NULL, ?, ?, ?)'
            )->execute([$memberId, $label, 'gradient', strtoupper($primary), strtoupper($secondary), $setDefault ? 1 : 0]);
            set_flash('success', $qt('ok_bg_palette'));
        } elseif ($action === 'set_default_background') {
            $presetId = (int) ($_POST['preset_id'] ?? 0);
            db()->prepare('UPDATE qsl_background_presets SET is_default = 0 WHERE member_id = ?')->execute([$memberId]);
            db()->prepare('UPDATE qsl_background_presets SET is_default = 1 WHERE id = ? AND member_id = ?')->execute([$presetId, $memberId]);
            set_flash('success', $qt('ok_bg_default'));
        } elseif ($action === 'delete_background') {
            $presetId = (int) ($_POST['preset_id'] ?? 0);
            db()->prepare('DELETE FROM qsl_background_presets WHERE id = ? AND member_id = ? LIMIT 1')->execute([$presetId, $memberId]);
            $hasDefault = db()->prepare('SELECT id FROM qsl_background_presets WHERE member_id = ? AND is_default = 1 LIMIT 1');
            $hasDefault->execute([$memberId]);
            if (!$hasDefault->fetch()) {
                $fallback = db()->prepare('SELECT id FROM qsl_background_presets WHERE member_id = ? ORDER BY id ASC LIMIT 1');
                $fallback->execute([$memberId]);
                $first = $fallback->fetch();
                if ($first) {
                    db()->prepare('UPDATE qsl_background_presets SET is_default = 1 WHERE id = ? AND member_id = ?')->execute([(int) $first['id'], $memberId]);
                }
            }
            set_flash('success', $qt('ok_bg_deleted'));
        } elseif ($action === 'import_adif') {
            $uploads = [];
            if (isset($_FILES['adif_files']) && is_array($_FILES['adif_files'])) {
                $batch = $_FILES['adif_files'];
                $names = (array) ($batch['name'] ?? []);
                foreach (array_keys($names) as $index) {
                    $uploads[] = [
                        'name' => (string) ($batch['name'][$index] ?? ''),
                        'type' => (string) ($batch['type'][$index] ?? ''),
                        'tmp_name' => (string) ($batch['tmp_name'][$index] ?? ''),
                        'error' => (int) ($batch['error'][$index] ?? UPLOAD_ERR_NO_FILE),
                        'size' => (int) ($batch['size'][$index] ?? 0),
                    ];
                }
            } elseif (isset($_FILES['adif_file']) && is_array($_FILES['adif_file'])) {
                $uploads[] = $_FILES['adif_file'];
            }

            if ($uploads === []) {
                throw new RuntimeException($qt('err_no_adif'));
            }
            $totalImported = 0;
            $processedFiles = 0;
            foreach ($uploads as $file) {
                if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    continue;
                }
                $tmpName = (string) ($file['tmp_name'] ?? '');
                if ($tmpName === '' || !is_uploaded_file($tmpName)) {
                    continue;
                }
                $content = file_get_contents($tmpName);
                if ($content === false) {
                    continue;
                }
                $records = parse_adif($content);
                $totalImported += import_adif_records((int) $user['id'], $records);
                $processedFiles++;
            }

            if ($processedFiles === 0) {
                throw new RuntimeException($qt('err_no_valid_adif'));
            }

            if ($isAjaxRequest) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'ok' => true,
                    'files' => $processedFiles,
                    'imported' => $totalImported,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            if ($totalImported > 0) {
                set_flash('success', $totalImported . ' ' . $qt('ok_qso_imported'));
            } else {
                set_flash('error', $qt('err_qso_none'));
            }
        } elseif ($action === 'generate_batch') {
            $ids = array_map('intval', $_POST['qso_ids'] ?? []);
            $templateName = ((string) ($_POST['qsl_template_name'] ?? 'classic')) === 'classic_duplex' ? 'classic_duplex' : 'classic';
            $count = create_qsl_cards_from_qsos((int) $user['id'], $ids, $templateName);
            if ($count > 0) {
                set_flash('success', $count . ' ' . $qt('ok_qsl_generated'));
            } else {
                set_flash('error', $qt('err_qsl_none'));
            }
        } elseif ($action === 'create_manual') {
            $presetId = (int) ($_POST['background_preset_id'] ?? 0);
            $templateName = ((string) ($_POST['template_name'] ?? 'classic')) === 'classic_duplex' ? 'classic_duplex' : 'classic';
            $presetStmt = db()->prepare('SELECT id, type, image_data_uri, color_primary, color_secondary FROM qsl_background_presets WHERE id = ? AND member_id = ? LIMIT 1');
            $presetStmt->execute([$presetId, $memberId]);
            $selectedPreset = $presetStmt->fetch();
            if (!$selectedPreset) {
                $defaultStmt = db()->prepare('SELECT id, type, image_data_uri, color_primary, color_secondary FROM qsl_background_presets WHERE member_id = ? AND is_default = 1 LIMIT 1');
                $defaultStmt->execute([$memberId]);
                $selectedPreset = $defaultStmt->fetch();
            }
            $data = [
                'own_call' => (string) ($user['callsign'] ?? ''),
                'own_name' => (string) ($user['full_name'] ?? ''),
                'own_qth' => (string) ($user['qth'] ?? ''),
                'qso_call' => trim((string) ($_POST['qso_call'] ?? '')),
                'qso_date' => trim((string) ($_POST['qso_date'] ?? '')),
                'time_on' => trim((string) ($_POST['time_on'] ?? '')),
                'band' => trim((string) ($_POST['band'] ?? '')),
                'mode' => trim((string) ($_POST['mode'] ?? '')),
                'rst_sent' => trim((string) ($_POST['rst_sent'] ?? '')),
                'rst_recv' => trim((string) ($_POST['rst_recv'] ?? '')),
                'comment' => trim((string) ($_POST['comment'] ?? 'TNX QSO 73')),
                'background_primary' => (string) ($selectedPreset['color_primary'] ?? '#0B1F3A'),
                'background_secondary' => (string) ($selectedPreset['color_secondary'] ?? '#1D4ED8'),
                'background_image_data_uri' => (string) ($selectedPreset['image_data_uri'] ?? ''),
                'template_name' => $templateName,
            ];

            $payload = build_qsl_svg_payload($user, $data, (string) $data['comment']);
            $svg = generate_qsl_svg($payload);
            $stmt = db()->prepare(
                'INSERT INTO qsl_cards (member_id, title, qso_call, qso_date, time_on, band, mode, rst_sent, rst_recv, template_name, svg_content)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $memberId,
                qsl_card_title($payload),
                $payload['qso_call'],
                $payload['qso_date'] !== '' ? $payload['qso_date'] : null,
                $payload['time_on'] !== '' ? $payload['time_on'] : null,
                $payload['band'] !== '' ? $payload['band'] : null,
                $payload['mode'] !== '' ? $payload['mode'] : null,
                $payload['rst_sent'] !== '' ? $payload['rst_sent'] : null,
                $payload['rst_recv'] !== '' ? $payload['rst_recv'] : null,
                $templateName,
                $svg,
            ]);
            set_flash('success', $qt('ok_qsl_created'));
        } elseif ($action === 'delete_qso' || isset($_POST['delete_qso_id'])) {
            $qsoId = (int) ($_POST['delete_qso_id'] ?? ($_POST['qso_id'] ?? 0));
            $stmt = db()->prepare('DELETE FROM qso_logs WHERE id = ? AND member_id = ? LIMIT 1');
            $stmt->execute([$qsoId, $memberId]);
            set_flash('success', $qt('ok_qso_deleted'));
        } elseif ($action === 'delete_qsl') {
            $stmt = db()->prepare('DELETE FROM qsl_cards WHERE id = ? AND member_id = ? LIMIT 1');
            $stmt->execute([(int) ($_POST['qsl_id'] ?? 0), $memberId]);
            set_flash('success', $qt('ok_qsl_deleted'));
        } else {
            throw new RuntimeException($qt('err_unknown_action'));
        }

        if ($isAjaxRequest) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        redirect('qsl');
    } catch (Throwable $throwable) {
        $isAjaxRequest = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
        if ($isAjaxRequest) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => $throwable->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        set_flash('error', $throwable->getMessage());
        redirect('qsl');
    }
}

$qsoLogs = db()->prepare('SELECT * FROM qso_logs WHERE member_id = ? ORDER BY id DESC LIMIT 100');
$qsoLogs->execute([$memberId]);
$qsoRows = $qsoLogs->fetchAll();

$qslCards = db()->prepare('SELECT * FROM qsl_cards WHERE member_id = ? ORDER BY id DESC LIMIT 50');
$qslCards->execute([$memberId]);
$backgroundPresetsStmt = db()->prepare('SELECT id, label, type, image_data_uri, color_primary, color_secondary, is_default FROM qsl_background_presets WHERE member_id = ? ORDER BY is_default DESC, id DESC');
$backgroundPresetsStmt->execute([$memberId]);
$backgroundPresets = $backgroundPresetsStmt->fetchAll();
$qslRows = $qslCards->fetchAll();
$defaultBackgroundPresetId = 0;
foreach ($backgroundPresets as $presetRow) {
    if ((int) ($presetRow['is_default'] ?? 0) === 1) {
        $defaultBackgroundPresetId = (int) ($presetRow['id'] ?? 0);
        break;
    }
}
$hasCreatedQsl = count($qslRows) > 0;

$qsoSearch = trim((string) ($_GET['qso_search'] ?? ''));
$qsoBandFilter = mb_safe_strtoupper(trim((string) ($_GET['qso_band'] ?? '')));
$qsoModeFilter = mb_safe_strtoupper(trim((string) ($_GET['qso_mode'] ?? '')));
$qslSearch = trim((string) ($_GET['qsl_search'] ?? ''));
$qsoPage = max(1, (int) ($_GET['qso_page'] ?? 1));
$qslPage = max(1, (int) ($_GET['qsl_page'] ?? 1));
$qsoPerPage = 25;
$qslPerPage = 25;

$qsoBandOptions = [];
$qsoModeOptions = [];
foreach ($qsoRows as $row) {
    $band = mb_safe_strtoupper(trim((string) ($row['band'] ?? '')));
    $mode = mb_safe_strtoupper(trim((string) ($row['mode'] ?? '')));
    if ($band !== '') {
        $qsoBandOptions[$band] = true;
    }
    if ($mode !== '') {
        $qsoModeOptions[$mode] = true;
    }
}

$matchesTextFilter = static function (string $needle, array $fields): bool {
    if ($needle === '') {
        return true;
    }
    foreach ($fields as $field) {
        if (stripos($field, $needle) !== false) {
            return true;
        }
    }

    return false;
};

$qsoEqslStatus = static function (array $row): string {
    $raw = (string) ($row['raw_payload'] ?? '');
    if ($raw === '') {
        return '-';
    }

    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        return '-';
    }

    $sent = qsl_normalize_qsl_status((string) ($payload['eqsl_qsl_sent'] ?? ''));
    $received = qsl_normalize_qsl_status((string) ($payload['eqsl_qsl_rcvd'] ?? ''));
    if ($sent === '' && $received === '') {
        return '-';
    }

    return 'S:' . ($sent !== '' ? $sent : '-') . ' / R:' . ($received !== '' ? $received : '-');
};

$filteredQsoRows = array_values(array_filter($qsoRows, static function (array $row) use ($matchesTextFilter, $qsoSearch, $qsoBandFilter, $qsoModeFilter): bool {
    $band = mb_safe_strtoupper(trim((string) ($row['band'] ?? '')));
    $mode = mb_safe_strtoupper(trim((string) ($row['mode'] ?? '')));
    if ($qsoBandFilter !== '' && $band !== $qsoBandFilter) {
        return false;
    }
    if ($qsoModeFilter !== '' && $mode !== $qsoModeFilter) {
        return false;
    }

    return $matchesTextFilter($qsoSearch, [
        (string) ($row['qso_call'] ?? ''),
        (string) ($row['qso_date'] ?? ''),
        (string) ($row['band'] ?? ''),
        (string) ($row['mode'] ?? ''),
        (string) ($row['comment'] ?? ''),
    ]);
}));

$filteredQslRows = array_values(array_filter($qslRows, static function (array $row) use ($matchesTextFilter, $qslSearch): bool {
    return $matchesTextFilter($qslSearch, [
        (string) ($row['title'] ?? ''),
        (string) ($row['qso_call'] ?? ''),
        (string) ($row['qso_date'] ?? ''),
        (string) ($row['band'] ?? ''),
        (string) ($row['mode'] ?? ''),
    ]);
}));

$qsoTotal = count($filteredQsoRows);
$qslTotal = count($filteredQslRows);
$qsoPagination = pagination_state($qsoTotal, $qsoPage, $qsoPerPage);
$qslPagination = pagination_state($qslTotal, $qslPage, $qslPerPage);
$qsoPage = $qsoPagination['page'];
$qslPage = $qslPagination['page'];
$qsoTotalPages = $qsoPagination['total_pages'];
$qslTotalPages = $qslPagination['total_pages'];
$pagedQsoRows = array_slice($filteredQsoRows, $qsoPagination['offset'], $qsoPerPage);
$pagedQslRows = array_slice($filteredQslRows, $qslPagination['offset'], $qslPerPage);

$buildQslPageUrl = static function (int $targetQsoPage, int $targetQslPage) use ($qsoSearch, $qsoBandFilter, $qsoModeFilter, $qslSearch): string {
    return route_url_clean('qsl', [
        'qso_search' => $qsoSearch,
        'qso_band' => $qsoBandFilter,
        'qso_mode' => $qsoModeFilter,
        'qsl_search' => $qslSearch,
        'qso_page' => $targetQsoPage > 1 ? $targetQsoPage : null,
        'qsl_page' => $targetQslPage > 1 ? $targetQslPage : null,
    ]);
};

$generatedByQsoId = [];
foreach ($qslRows as $card) {
    $key = qsl_normalize_callsign((string) ($card['qso_call'] ?? '')) . '|'
        . qsl_normalize_date((string) ($card['qso_date'] ?? '')) . '|'
        . qsl_normalize_time((string) ($card['time_on'] ?? ''));
    if ($key !== '||') {
        $generatedByQsoId[$key] = true;
    }
}

ksort($qsoBandOptions);
ksort($qsoModeOptions);

ob_start();
?>
<div class="qsl-page">
<section class="card qsl-studio-overview">
    <h2><?= e($qt('studio')) ?></h2>
    <p class="help"><?= e($qt('studio_help')) ?></p>
    <div class="grid-3">
        <a class="inner-card qsl-studio-link-card" href="#qsl-draw" data-qsl-nav-target="design">
            <span class="badge muted"><?= e($qt('nav_design')) ?></span>
            <p class="help"><?= e($qt('nav_design_help')) ?></p>
        </a>
        <a class="inner-card qsl-studio-link-card" href="#qsl-create" data-qsl-nav-target="create">
            <span class="badge muted"><?= e($qt('nav_create')) ?></span>
            <p class="help"><?= e($qt('nav_create_help')) ?></p>
        </a>
        <a class="inner-card qsl-studio-link-card" href="#qsl-view" data-qsl-nav-target="manage">
            <span class="badge muted"><?= e($qt('nav_manage')) ?></span>
            <p class="help"><?= e($qt('nav_manage_help')) ?></p>
        </a>
    </div>
</section>

<section class="card" id="qsl-draw" data-qsl-draw-assistant data-qsl-panel="design">
    <h2><?= e($qt('design')) ?></h2>
    <p class="help">Choose a background type. The form updates automatically and the preview refreshes live.</p>
    <div class="actions">
        <label><input type="radio" name="qsl_draw_flow" value="image" data-qsl-draw-choice> <?= e($qt('label_bg_image')) ?></label>
        <label><input type="radio" name="qsl_draw_flow" value="solid" data-qsl-draw-choice> Solid color</label>
        <label><input type="radio" name="qsl_draw_flow" value="gradient" data-qsl-draw-choice checked> <?= e($qt('label_gradient')) ?></label>
        <label><input type="radio" name="qsl_draw_flow" value="palette" data-qsl-draw-choice> Preset colors</label>
    </div>
    <div class="split qsl-background-workbench">
        <div>
            <div class="stack">
                <form method="post" enctype="multipart/form-data" class="stack" data-preview-form="image" data-qsl-draw-panel="image">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="save_background_image">
                    <label>Image background name<input type="text" name="background_label" maxlength="120" placeholder="Ex: Shack ON4CRD"></label>
                    <label>Image
                        <input type="file" name="background_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required data-preview-image-input>
                    </label>
                    <label><input type="checkbox" name="set_default" value="1"> Set as default background</label>
                    <button type="submit" class="button secondary">Add image background</button>
                </form>
                <form method="post" class="stack" data-preview-form="gradient" data-qsl-draw-panel="gradient">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="save_background_gradient">
                    <label>Gradient background name<input type="text" name="gradient_label" maxlength="120" placeholder="Ex: Club blue"></label>
                    <label><span>Background color 1</span><input class="qsl-color-input" type="color" name="background_primary" value="#0B1F3A" data-preview-color-primary></label>
                    <label><span>Background color 2</span><input class="qsl-color-input" type="color" name="background_secondary" value="#1D4ED8" data-preview-color-secondary></label>
                    <label><input type="checkbox" name="set_default" value="1"> Set as default background</label>
                    <button type="submit" class="button secondary">Add gradient background</button>
                </form>
                <form method="post" class="stack is-hidden" data-preview-form="solid" data-qsl-draw-panel="solid">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="save_background_solid">
                    <label>Color name<input type="text" name="solid_label" maxlength="120" placeholder="Ex: Night blue"></label>
                    <label>Solid color<input type="color" name="background_solid" value="#1E293B" data-preview-solid-color></label>
                    <label><input type="checkbox" name="set_default" value="1"> Set as default background</label>
                    <button type="submit" class="button secondary">Add solid color</button>
                </form>
                <form method="post" class="stack is-hidden" data-preview-form="palette" data-qsl-draw-panel="palette">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="save_background_palette">
                    <label>Preset palette
                        <select name="preset_palette" data-preview-palette-select>
                            <?php foreach ($drawPresetPalettes as $paletteKey => $palette): ?>
                                <option
                                    value="<?= e($paletteKey) ?>"
                                    data-primary="<?= e((string) ($palette['primary'] ?? '#0B1F3A')) ?>"
                                    data-secondary="<?= e((string) ($palette['secondary'] ?? '#1D4ED8')) ?>"
                                >
                                    <?= e((string) ($palette['label'] ?? 'Palette')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Custom name (optional)<input type="text" name="palette_label" maxlength="120" placeholder="Ex: Aurora palette"></label>
                    <label><input type="checkbox" name="set_default" value="1"> Set as default background</label>
                    <button type="submit" class="button secondary">Add palette</button>
                </form>
            </div>
        </div>
        <div class="qsl-live-preview-wrap">
            <h3>Live preview</h3>
            <div class="qsl-live-preview" data-qsl-preview>
                <div class="qsl-live-preview-card" data-qsl-preview-card>
                    <p class="qsl-live-preview-title">QSL Preview</p>
                    <p class="qsl-live-preview-meta">DE: <?= e((string) ($user['callsign'] ?? 'ON4CRD')) ?> -> TO: F4XYZ</p>
                </div>
            </div>
            <p class="help">Preview of the background being created (image, solid color, gradient or preset palette).</p>
        </div>
    </div>
    <?php if ($backgroundPresets !== []): ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr><th>Background</th><th>Type</th><th>Default</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($backgroundPresets as $preset): ?>
                    <tr>
                        <td><?= e((string) ($preset['label'] ?? 'Background')) ?></td>
                        <td><?= e(((string) ($preset['type'] ?? 'gradient')) === 'image' ? 'Image' : 'Gradient') ?></td>
                        <td><?= ((int) ($preset['is_default'] ?? 0) === 1) ? 'Yes' : '-' ?></td>
                        <td>
                            <form method="post" class="inline-form">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="preset_id" value="<?= (int) ($preset['id'] ?? 0) ?>">
                                <button type="submit" name="action" value="set_default_background" class="button secondary small">Set default</button>
                                <button type="submit" name="action" value="delete_background" class="button secondary small"><?= e($qt('label_delete')) ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="card" id="qsl-create" data-qsl-assistant data-qsl-panel="create">
    <h1><?= e($qt('create')) ?></h1>
    <p class="help">Choose your goal: detailed manual creation or instant ADIF import.</p>

    <div class="stack">
        <div>
            <span class="badge muted">Step A</span>
            <h2>What do you need now?</h2>
            <div class="actions">
                <label><input type="radio" name="qsl_assistant_flow" value="manual" data-qsl-assistant-choice checked> Create a manual QSL</label>
                <label><input type="radio" name="qsl_assistant_flow" value="adif" data-qsl-assistant-choice> Import ADIF QSOs</label>
            </div>
        </div>

        <section class="stack" data-qsl-assistant-panel="manual">
            <div>
                <span class="badge muted">Step B</span>
                <h2>Guided manual form</h2>
            </div>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="create_manual">
                <div class="form-grid">
                    <label>Contact callsign<input type="text" name="qso_call" maxlength="64" required data-manual-preview-source="qso_call"></label>
                    <label>QSO date<input type="date" name="qso_date" data-manual-preview-source="qso_date"></label>
                    <label>UTC<input type="time" name="time_on" step="60" data-manual-preview-source="time_on"></label>
                    <label>Band<input type="text" name="band" maxlength="32" placeholder="20M" data-manual-preview-source="band"></label>
                    <label>Mode<input type="text" name="mode" maxlength="32" placeholder="SSB" data-manual-preview-source="mode"></label>
                    <label>RST sent<input type="text" name="rst_sent" maxlength="16" placeholder="59" data-manual-preview-source="rst_sent"></label>
                    <label>RST received<input type="text" name="rst_recv" maxlength="16" placeholder="59" data-manual-preview-source="rst_recv"></label>
                    <label>Comment
                        <textarea name="comment" rows="3" maxlength="180" data-manual-preview-source="comment">TNX QSO 73</textarea>
                    </label>
                    <label>QSL background
                        <select name="background_preset_id" data-manual-preview-source="background_preset_id">
                            <option value="0" data-bg-type="gradient" data-bg-primary="#0B1F3A" data-bg-secondary="#1D4ED8" <?= $defaultBackgroundPresetId === 0 ? 'selected' : '' ?>>System default background</option>
                            <?php foreach ($backgroundPresets as $preset): ?>
                                <?php
                                $presetId = (int) ($preset['id'] ?? 0);
                                $isDefaultPreset = (int) ($preset['is_default'] ?? 0) === 1;
                                $presetLabel = (string) ($preset['label'] ?? 'Background');
                                $presetType = (string) ($preset['type'] ?? 'gradient');
                                $presetPrimary = (string) ($preset['color_primary'] ?? '#0B1F3A');
                                $presetSecondary = (string) ($preset['color_secondary'] ?? '#1D4ED8');
                                ?>
                                <option
                                    value="<?= $presetId ?>"
                                    data-bg-type="<?= e($presetType) ?>"
                                    data-bg-image="<?= e((string) ($preset['image_data_uri'] ?? '')) ?>"
                                    data-bg-primary="<?= e($presetPrimary) ?>"
                                    data-bg-secondary="<?= e($presetSecondary) ?>"
                                    <?= ($presetId === $defaultBackgroundPresetId) ? 'selected' : '' ?>
                                >
                            <?= e($presetLabel) ?><?= $isDefaultPreset ? ' (default)' : '' ?> - <?= e($presetType === 'image' ? 'Image' : 'Gradient') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Print format
                        <select name="template_name">
                            <option value="classic">Front only</option>
                            <option value="classic_duplex">Front and back</option>
                        </select>
                    </label>
                </div>
                <p class="help">Choose one saved background for this QSL.</p>
                <div class="qsl-live-preview-wrap" data-qsl-manual-preview data-preview-note="<?= e($qt('preview_dynamic')) ?>">
                    <h3>QSL preview</h3>
                    <div class="grid-2" data-manual-preview-layout>
                        <div class="qsl-live-preview">
                            <div class="qsl-live-preview-card" data-manual-preview-card>
                                <p class="qsl-live-preview-title">Front preview</p>
                    <p class="qsl-live-preview-meta">DE: <?= e((string) ($user['callsign'] ?? 'ON4CRD')) ?> -> TO: <span data-manual-preview-field="qso_call">F4XYZ</span></p>
                                <p class="qsl-live-preview-meta" data-manual-preview-front-detail>DATE: <span data-manual-preview-field="qso_date">20260412</span> UTC: <span data-manual-preview-field="time_on">09:15</span></p>
                                <p class="qsl-live-preview-meta" data-manual-preview-front-detail>BAND: <span data-manual-preview-field="band">20M</span> MODE: <span data-manual-preview-field="mode">SSB</span></p>
                                <p class="qsl-live-preview-meta" data-manual-preview-front-detail>RST S/R: <span data-manual-preview-field="rst_sent">59</span>/<span data-manual-preview-field="rst_recv">59</span></p>
                                <p class="qsl-live-preview-meta" data-manual-preview-front-detail><span data-manual-preview-field="comment">TNX QSO 73</span></p>
                                <p class="qsl-live-preview-meta is-hidden" data-manual-preview-front-message>Front side - details on back side</p>
                            </div>
                        </div>
                        <div class="qsl-live-preview is-hidden" data-manual-preview-back-wrap>
                            <div class="qsl-live-preview-card" data-manual-preview-back-card>
                                <p class="qsl-live-preview-title">Back preview</p>
                    <p class="qsl-live-preview-meta">DE: <?= e((string) ($user['callsign'] ?? 'ON4CRD')) ?> -> TO: <span data-manual-preview-back-field="qso_call">F4XYZ</span></p>
                                <p class="qsl-live-preview-meta">DATE: <span data-manual-preview-back-field="qso_date">20260412</span> UTC: <span data-manual-preview-back-field="time_on">09:15</span></p>
                                <p class="qsl-live-preview-meta">BAND: <span data-manual-preview-back-field="band">20M</span> MODE: <span data-manual-preview-back-field="mode">SSB</span></p>
                                <p class="qsl-live-preview-meta">RST S/R: <span data-manual-preview-back-field="rst_sent">59</span>/<span data-manual-preview-back-field="rst_recv">59</span></p>
                                <p class="qsl-live-preview-meta"><span data-manual-preview-back-field="comment">TNX QSO 73</span></p>
                            </div>
                        </div>
                    </div>
                    <p class="help" data-manual-preview-note><?= e($qt('preview_dynamic')) ?></p>
                </div>
                <p><button class="button">Create my QSL</button></p>
            </form>
        </section>

        <section class="stack" data-qsl-assistant-panel="adif">
            <div>
                <span class="badge muted">Step B</span>
                <h2>Fast ADIF import</h2>
            </div>
            <form method="post" enctype="multipart/form-data" id="adif-dropzone-form" class="stack" data-adif-processing="<?= e($qt('adif_processing')) ?>" data-adif-import-error="<?= e($qt('adif_import_error')) ?>" data-adif-imported-status="<?= e($qt('adif_imported_status')) ?>">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="import_adif">
                <div id="adif-dropzone" class="dropzone qsl-adif-dropzone">
                    <div class="dz-message">
                        Drag and drop your ADIF files here
                        <small>or click to select multiple files (.adi, .adif)</small>
                    </div>
                </div>
                <input type="file" name="adif_files[]" id="adif-fallback-input" accept=".adi,.adif,text/plain" multiple hidden>
                <p class="help" id="adif-dropzone-status">Files are processed automatically when added.</p>
            </form>
            <p class="help">Exact duplicates are ignored automatically during import.</p>
        </section>
    </div>
</section>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.css">
<script nonce="<?= e(csp_nonce()) ?>" src="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.js"></script>

<section class="card" id="qsl-view" data-qsl-panel="manage">
    <div class="row-between">
        <h2><?= e($qt('manage')) ?></h2>
        <span><?= count($qsoRows) ?> enregistrement(s)</span>
    </div>
    <?php if ($qsoRows === []): ?>
        <p><?= e($qt('empty_qso')) ?></p>
    <?php else: ?>
        <form method="get" class="inline-form qsl-filters">
            <input type="hidden" name="route" value="qsl">
            <input type="text" name="qso_search" value="<?= e($qsoSearch) ?>" placeholder="<?= e($qt('qso_search_ph')) ?>">
            <select name="qso_band">
                <option value=""><?= e($qt('all_bands')) ?></option>
                <?php foreach (array_keys($qsoBandOptions) as $option): ?>
                    <option value="<?= e($option) ?>" <?= $qsoBandFilter === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="qso_mode">
                <option value=""><?= e($qt('all_modes')) ?></option>
                <?php foreach (array_keys($qsoModeOptions) as $option): ?>
                    <option value="<?= e($option) ?>" <?= $qsoModeFilter === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="button secondary small"><?= e($qt('filter')) ?></button>
            <a href="<?= e(route_url('qsl')) ?>" class="ghost"><?= e($qt('reset')) ?></a>
        </form>
        <form method="post">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="generate_batch">
            <div class="actions">
                <button type="button" class="button secondary small" data-qso-toggle="all"><?= e($qt('select_all')) ?></button>
                <button type="button" class="button secondary small" data-qso-toggle="none"><?= e($qt('select_none')) ?></button>
                <label>Format
                    <select name="qsl_template_name">
                        <option value="classic">Recto</option>
                        <option value="classic_duplex">Recto-verso</option>
                    </select>
                </label>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr><th></th><th>Call</th><th>Date</th><th>UTC</th><th>Band</th><th>Mode</th><th>RST</th><th>eQSL</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($pagedQsoRows as $row): ?>
                        <tr>
                            <td><input type="checkbox" name="qso_ids[]" value="<?= (int) $row['id'] ?>"></td>
                            <td><?= e((string) $row['qso_call']) ?></td>
                            <td><?= e(qsl_format_display_date((string) ($row['qso_date'] ?? ''))) ?></td>
                            <td><?= e(qsl_format_display_time((string) ($row['time_on'] ?? ''))) ?></td>
                            <td><?= e((string) $row['band']) ?></td>
                            <td><?= e((string) $row['mode']) ?></td>
                            <td><?= e((string) $row['rst_sent']) ?>/<?= e((string) $row['rst_recv']) ?></td>
                            <td><?= e($qsoEqslStatus($row)) ?></td>
                            <td><button class="button secondary small" type="submit" name="delete_qso_id" value="<?= (int) $row['id'] ?>"><?= e($qt('label_delete')) ?></button></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($filteredQsoRows === []): ?>
                <p class="help"><?= e($qt('empty_qso_filtered')) ?></p>
            <?php endif; ?>
            <?php if ($qsoTotalPages > 1): ?>
                <div class="actions">
                    <span class="help"><?= e($qt('page')) ?> <?= $qsoPage ?> / <?= $qsoTotalPages ?></span>
                    <?php if ($qsoPage > 1): ?><a class="button secondary small" href="<?= e($buildQslPageUrl($qsoPage - 1, $qslPage)) ?>">&lt;- <?= e($qt('previous')) ?></a><?php endif; ?>
                    <?php if ($qsoPage < $qsoTotalPages): ?><a class="button secondary small" href="<?= e($buildQslPageUrl($qsoPage + 1, $qslPage)) ?>"><?= e($qt('next')) ?> -&gt;</a><?php endif; ?>
                </div>
            <?php endif; ?>
            <p><button class="button"><?= e($qt('bulk_generate')) ?></button></p>
        </form>
    <?php endif; ?>
</section>

<section class="card" data-qsl-panel="manage">
    <div class="row-between">
        <h2><?= e($qt('generated')) ?></h2>
        <span><?= count($qslRows) ?> carte(s)</span>
    </div>
    <?php if ($qslRows === []): ?>
        <p><?= e($qt('empty_qsl')) ?></p>
    <?php else: ?>
        <form method="get" class="inline-form qsl-filters">
            <input type="hidden" name="route" value="qsl">
            <input type="text" name="qsl_search" value="<?= e($qslSearch) ?>" placeholder="<?= e($qt('qsl_search_ph')) ?>">
            <button type="submit" class="button secondary small"><?= e($qt('filter')) ?></button>
            <a href="<?= e(route_url('qsl')) ?>" class="ghost"><?= e($qt('reset')) ?></a>
        </form>
        <div class="table-wrap">
            <table>
                <thead>
                <tr><th>Title</th><th>QSO</th><th>Date</th><th>Band</th><th>Mode</th><th>Format</th><th>Preview</th><th>Export</th><th>Action</th></tr>
                </thead>
                <tbody>
                <?php foreach ($pagedQslRows as $row): ?>
                    <tr>
                        <td><?= e((string) $row['title']) ?></td>
                        <td><?= e((string) $row['qso_call']) ?></td>
                        <td><?= e(qsl_format_display_date((string) ($row['qso_date'] ?? ''))) ?></td>
                        <td><?= e((string) $row['band']) ?></td>
                        <td><?= e((string) $row['mode']) ?></td>
                        <td><?= qsl_template_supports_back((string) ($row['template_name'] ?? 'classic')) ? 'Recto-verso' : 'Recto' ?></td>
                        <td><a href="<?= e(route_url('qsl_preview', ['id' => (int) $row['id']])) ?>">Voir</a></td>
                        <td>
                            <a href="<?= e(route_url('qsl_export', ['id' => (int) $row['id']])) ?>">Recto SVG</a>
                            <?php if (qsl_template_supports_back((string) ($row['template_name'] ?? 'classic'))): ?>
                                - <a href="<?= e(route_url('qsl_export', ['id' => (int) $row['id'], 'side' => 'back'])) ?>">Verso SVG</a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" class="inline-form">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete_qsl">
                                <input type="hidden" name="qsl_id" value="<?= (int) $row['id'] ?>">
                                <button class="button secondary small" type="submit"><?= e($qt('label_delete')) ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($filteredQslRows === []): ?>
            <p class="help"><?= e($qt('empty_qsl_filtered')) ?></p>
        <?php endif; ?>
        <?php if ($qslTotalPages > 1): ?>
            <div class="actions">
                <span class="help"><?= e($qt('page')) ?> <?= $qslPage ?> / <?= $qslTotalPages ?></span>
                <?php if ($qslPage > 1): ?><a class="button secondary small" href="<?= e($buildQslPageUrl($qsoPage, $qslPage - 1)) ?>">&lt;- <?= e($qt('previous')) ?></a><?php endif; ?>
                <?php if ($qslPage < $qslTotalPages): ?><a class="button secondary small" href="<?= e($buildQslPageUrl($qsoPage, $qslPage + 1)) ?>"><?= e($qt('next')) ?> -&gt;</a><?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
</div>
<?php include __DIR__ . '/qsl_script.js.php'; ?>

<?php
echo render_layout((string) ob_get_clean(), 'QSL');


