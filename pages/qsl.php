<?php
declare(strict_types=1);

$user = require_login();
$memberId = (int) ($user['id'] ?? 0);

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

        if ($action === 'save_background_image') {
            $label = trim((string) ($_POST['background_label'] ?? 'Fond image'));
            $label = mb_safe_substr($label !== '' ? $label : 'Fond image', 0, 120);
            $dataUri = qsl_background_upload_to_data_uri($_FILES['background_image'] ?? null);
            if ($dataUri === '') {
                throw new RuntimeException('Veuillez sélectionner une image de fond.');
            }
            $setDefault = ((string) ($_POST['set_default'] ?? '') === '1');
            if ($setDefault) {
                db()->prepare('UPDATE qsl_background_presets SET is_default = 0 WHERE member_id = ?')->execute([$memberId]);
            }
            db()->prepare(
                'INSERT INTO qsl_background_presets (member_id, label, type, image_data_uri, color_primary, color_secondary, is_default)
                 VALUES (?, ?, ?, ?, NULL, NULL, ?)'
            )->execute([$memberId, $label, 'image', $dataUri, $setDefault ? 1 : 0]);
            set_flash('success', 'Fond image enregistré.');
        } elseif ($action === 'save_background_gradient') {
            $label = trim((string) ($_POST['gradient_label'] ?? 'Fond dégradé'));
            $label = mb_safe_substr($label !== '' ? $label : 'Fond dégradé', 0, 120);
            $primary = trim((string) ($_POST['background_primary'] ?? '#0B1F3A'));
            $secondary = trim((string) ($_POST['background_secondary'] ?? '#1D4ED8'));
            if (preg_match('/^#[A-Fa-f0-9]{6}$/', $primary) !== 1 || preg_match('/^#[A-Fa-f0-9]{6}$/', $secondary) !== 1) {
                throw new RuntimeException('Couleurs de dégradé invalides.');
            }
            $setDefault = ((string) ($_POST['set_default'] ?? '') === '1');
            if ($setDefault) {
                db()->prepare('UPDATE qsl_background_presets SET is_default = 0 WHERE member_id = ?')->execute([$memberId]);
            }
            db()->prepare(
                'INSERT INTO qsl_background_presets (member_id, label, type, image_data_uri, color_primary, color_secondary, is_default)
                 VALUES (?, ?, ?, NULL, ?, ?, ?)'
            )->execute([$memberId, $label, 'gradient', strtoupper($primary), strtoupper($secondary), $setDefault ? 1 : 0]);
            set_flash('success', 'Fond dégradé enregistré.');
        } elseif ($action === 'set_default_background') {
            $presetId = (int) ($_POST['preset_id'] ?? 0);
            db()->prepare('UPDATE qsl_background_presets SET is_default = 0 WHERE member_id = ?')->execute([$memberId]);
            db()->prepare('UPDATE qsl_background_presets SET is_default = 1 WHERE id = ? AND member_id = ?')->execute([$presetId, $memberId]);
            set_flash('success', 'Fond par défaut mis à jour.');
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
            set_flash('success', 'Fond supprimé.');
        } elseif ($action === 'import_adif') {
            if (!isset($_FILES['adif_file']) || !is_array($_FILES['adif_file'])) {
                throw new RuntimeException('Aucun fichier ADIF reçu.');
            }

            $file = $_FILES['adif_file'];
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Le téléversement du fichier ADIF a échoué.');
            }

            $tmpName = (string) ($file['tmp_name'] ?? '');
            if ($tmpName === '' || !is_uploaded_file($tmpName)) {
                throw new RuntimeException('Fichier ADIF temporaire invalide.');
            }

            $content = file_get_contents($tmpName);
            if ($content === false) {
                throw new RuntimeException('Fichier ADIF illisible.');
            }

            $records = parse_adif($content);
            $count = import_adif_records((int) $user['id'], $records);
            if ($count > 0) {
                set_flash('success', $count . ' QSO(s) importé(s).');
            } else {
                set_flash('error', 'Aucun nouveau QSO importé. Les enregistrements sont peut-être déjà présents.');
            }
        } elseif ($action === 'generate_batch') {
            $ids = array_map('intval', $_POST['qso_ids'] ?? []);
            $count = create_qsl_cards_from_qsos((int) $user['id'], $ids);
            if ($count > 0) {
                set_flash('success', $count . ' QSL générée(s).');
            } else {
                set_flash('error', 'Aucune QSL générée. Sélection vide ou QSL déjà existantes.');
            }
        } elseif ($action === 'create_manual') {
            $presetId = (int) ($_POST['background_preset_id'] ?? 0);
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
            ];

            $svg = generate_qsl_svg($data);
            $payload = build_qsl_svg_payload($user, $data, (string) $data['comment']);
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
                'classic',
                $svg,
            ]);
            set_flash('success', 'QSL créée.');
        } elseif ($action === 'delete_qso' || isset($_POST['delete_qso_id'])) {
            $qsoId = (int) ($_POST['delete_qso_id'] ?? ($_POST['qso_id'] ?? 0));
            $stmt = db()->prepare('DELETE FROM qso_logs WHERE id = ? AND member_id = ? LIMIT 1');
            $stmt->execute([$qsoId, $memberId]);
            set_flash('success', 'QSO supprimé.');
        } elseif ($action === 'delete_qsl') {
            $stmt = db()->prepare('DELETE FROM qsl_cards WHERE id = ? AND member_id = ? LIMIT 1');
            $stmt->execute([(int) ($_POST['qsl_id'] ?? 0), $memberId]);
            set_flash('success', 'QSL supprimée.');
        } else {
            throw new RuntimeException('Action QSL inconnue.');
        }

        redirect('qsl');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect('qsl');
    }
}

$qsoLogs = db()->prepare('SELECT * FROM qso_logs WHERE member_id = ? ORDER BY id DESC LIMIT 100');
$qsoLogs->execute([$memberId]);
$qsoRows = $qsoLogs->fetchAll();

$qslCards = db()->prepare('SELECT * FROM qsl_cards WHERE member_id = ? ORDER BY id DESC LIMIT 50');
$qslCards->execute([$memberId]);
$backgroundPresetsStmt = db()->prepare('SELECT id, label, type, color_primary, color_secondary, is_default FROM qsl_background_presets WHERE member_id = ? ORDER BY is_default DESC, id DESC');
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
        return '—';
    }

    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        return '—';
    }

    $sent = qsl_normalize_qsl_status((string) ($payload['eqsl_qsl_sent'] ?? ''));
    $received = qsl_normalize_qsl_status((string) ($payload['eqsl_qsl_rcvd'] ?? ''));
    if ($sent === '' && $received === '') {
        return '—';
    }

    return 'S:' . ($sent !== '' ? $sent : '—') . ' / R:' . ($received !== '' ? $received : '—');
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
<section class="card">
    <h2>QSL Studio</h2>
    <div class="grid-3">
        <article class="inner-card">
            <h3>Dessiner</h3>
            <p class="help">Préparez vos fonds (image ou dégradé), puis choisissez votre fond par défaut.</p>
            <p><a class="button secondary small" href="#qsl-draw">Accéder</a></p>
        </article>
        <article class="inner-card">
            <h3>Créer</h3>
            <p class="help">Créez une QSL manuelle en sélectionnant un fond et en remplissant les informations QSO.</p>
            <p>
                <?php if ($hasCreatedQsl): ?>
                    <a class="button secondary small" href="#qsl-create">Accéder</a>
                <?php else: ?>
                    <span class="button secondary small disabled" aria-disabled="true">Accéder</span>
                <?php endif; ?>
            </p>
        </article>
        <article class="inner-card">
            <h3>Consulter</h3>
            <p class="help">Consultez vos QSO importés, vos eQSL et les QSL déjà générées.</p>
            <p>
                <?php if ($hasCreatedQsl): ?>
                    <a class="button secondary small" href="#qsl-view">Accéder</a>
                <?php else: ?>
                    <span class="button secondary small disabled" aria-disabled="true">Accéder</span>
                <?php endif; ?>
            </p>
        </article>
    </div>
    <?php if (!$hasCreatedQsl): ?>
        <p class="help">Les accès « Créer » et « Consulter » seront activés après la création de votre première QSL.</p>
    <?php endif; ?>
</section>

<section class="card" id="qsl-draw">
    <h2>Dessiner sa QSL</h2>
    <p>Section de préparation des fonds, sur toute la largeur, avec création à gauche et prévisualisation à droite.</p>
    <div class="split qsl-background-workbench">
        <div>
            <div class="stack">
                <form method="post" enctype="multipart/form-data" class="stack" data-preview-form="image">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="save_background_image">
                    <label>Nom du fond image<input type="text" name="background_label" maxlength="120" placeholder="Ex: Shack ON4CRD"></label>
                    <label>Image
                        <input type="file" name="background_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required data-preview-image-input>
                    </label>
                    <label><input type="checkbox" name="set_default" value="1"> Définir comme fond par défaut</label>
                    <button type="submit" class="button secondary">Ajouter le fond image</button>
                </form>
                <hr>
                <form method="post" class="stack" data-preview-form="gradient">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="save_background_gradient">
                    <label>Nom du fond dégradé<input type="text" name="gradient_label" maxlength="120" placeholder="Ex: Bleu club"></label>
                    <label>Couleur de fond 1<input type="color" name="background_primary" value="#0B1F3A" data-preview-color-primary></label>
                    <label>Couleur de fond 2<input type="color" name="background_secondary" value="#1D4ED8" data-preview-color-secondary></label>
                    <label><input type="checkbox" name="set_default" value="1"> Définir comme fond par défaut</label>
                    <button type="submit" class="button secondary">Ajouter le fond dégradé</button>
                </form>
            </div>
        </div>
        <div class="qsl-live-preview-wrap">
            <h3>Prévisualisation de la QSL</h3>
            <div class="qsl-live-preview" data-qsl-preview>
                <div class="qsl-live-preview-card" data-qsl-preview-card>
                    <p class="qsl-live-preview-title">QSL Preview</p>
                    <p class="qsl-live-preview-meta">DE: <?= e((string) ($user['callsign'] ?? 'ON4CRD')) ?> → TO: F4XYZ</p>
                </div>
            </div>
            <p class="help">Aperçu du fond en cours de création (image ou dégradé).</p>
        </div>
    </div>
    <?php if ($backgroundPresets !== []): ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr><th>Fond</th><th>Type</th><th>Défaut</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($backgroundPresets as $preset): ?>
                    <tr>
                        <td><?= e((string) ($preset['label'] ?? 'Fond')) ?></td>
                        <td><?= e(((string) ($preset['type'] ?? 'gradient')) === 'image' ? 'Image' : 'Dégradé') ?></td>
                        <td><?= ((int) ($preset['is_default'] ?? 0) === 1) ? '✅' : '—' ?></td>
                        <td>
                            <form method="post" class="inline-form">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="preset_id" value="<?= (int) ($preset['id'] ?? 0) ?>">
                                <button type="submit" name="action" value="set_default_background" class="button secondary small">Par défaut</button>
                                <button type="submit" name="action" value="delete_background" class="button secondary small">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<div class="grid-2">
    <section class="card" id="qsl-create">
        <h1>QSL Creator</h1>
        <p>Crée une carte QSL manuelle ou génère un lot à partir d’un fichier ADIF importé.</p>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="create_manual">
            <div class="form-grid">
                <label>Indicatif correspondant<input type="text" name="qso_call" maxlength="64" required></label>
                <label>Date QSO<input type="text" name="qso_date" placeholder="YYYYMMDD ou YYYY-MM-DD"></label>
                <label>UTC<input type="text" name="time_on" placeholder="HHMM ou HH:MM"></label>
                <label>Bande<input type="text" name="band" maxlength="32" placeholder="20M"></label>
                <label>Mode<input type="text" name="mode" maxlength="32" placeholder="SSB"></label>
                <label>RST envoyé<input type="text" name="rst_sent" maxlength="16" placeholder="59"></label>
                <label>RST reçu<input type="text" name="rst_recv" maxlength="16" placeholder="59"></label>
                <label>Commentaire</label>
                <div class="wysiwyg" data-wysiwyg data-max-length="180">
                    <div class="wysiwyg-toolbar" role="toolbar" aria-label="Outils de mise en forme du commentaire QSL">
                        <button type="button" class="button secondary small" data-wysiwyg-command="bold" aria-label="Gras"><strong>B</strong></button>
                        <button type="button" class="button secondary small" data-wysiwyg-command="italic" aria-label="Italique"><em>I</em></button>
                        <button type="button" class="button secondary small" data-wysiwyg-command="underline" aria-label="Souligné"><span style="text-decoration:underline;">U</span></button>
                    </div>
                    <div class="wysiwyg-editor" contenteditable="true" data-wysiwyg-editor aria-label="Éditeur WYSIWYG du commentaire QSL">TNX QSO 73</div>
                    <input type="hidden" name="comment" value="TNX QSO 73" data-wysiwyg-input>
                    <p class="help" data-wysiwyg-counter>180 caractères restants.</p>
                </div>
                <label>Fond QSL
                    <select name="background_preset_id">
                        <option value="0" <?= $defaultBackgroundPresetId === 0 ? 'selected' : '' ?>>Fond par défaut système</option>
                        <?php foreach ($backgroundPresets as $preset): ?>
                            <?php
                            $presetId = (int) ($preset['id'] ?? 0);
                            $isDefaultPreset = (int) ($preset['is_default'] ?? 0) === 1;
                            $presetLabel = (string) ($preset['label'] ?? 'Fond');
                            $presetType = (string) ($preset['type'] ?? 'gradient');
                            ?>
                            <option value="<?= $presetId ?>" <?= ($presetId === $defaultBackgroundPresetId) ? 'selected' : '' ?>>
                                <?= e($presetLabel) ?><?= $isDefaultPreset ? ' (défaut)' : '' ?> — <?= e($presetType === 'image' ? 'Image' : 'Dégradé') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <p class="help">Choisissez un seul fond enregistré pour cette QSL. Les préférences de fond se gèrent dans la section dédiée ci-dessous.</p>
            <p><button class="button">Créer une QSL</button></p>
        </form>
    </section>

    <section class="card">
        <h2>Import ADIF</h2>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="import_adif">
            <label>Fichier ADIF
                <input type="file" name="adif_file" accept=".adi,.adif,text/plain" required>
            </label>
            <p><button class="button secondary">Importer les QSO</button></p>
        </form>
        <p class="help">Les doublons exacts sont ignorés automatiquement lors de l’import.</p>
    </section>
</div>

<section class="card" id="qsl-view">
    <div class="row-between">
        <h2>QSO importés</h2>
        <span><?= count($qsoRows) ?> enregistrement(s)</span>
    </div>
    <?php if ($qsoRows === []): ?>
        <p>Aucun QSO importé pour le moment.</p>
    <?php else: ?>
        <form method="get" class="inline-form qsl-filters">
            <input type="hidden" name="route" value="qsl">
            <input type="text" name="qso_search" value="<?= e($qsoSearch) ?>" placeholder="Filtrer par call, date, mode...">
            <select name="qso_band">
                <option value="">Toutes bandes</option>
                <?php foreach (array_keys($qsoBandOptions) as $option): ?>
                    <option value="<?= e($option) ?>" <?= $qsoBandFilter === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="qso_mode">
                <option value="">Tous modes</option>
                <?php foreach (array_keys($qsoModeOptions) as $option): ?>
                    <option value="<?= e($option) ?>" <?= $qsoModeFilter === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="button secondary small">Filtrer</button>
            <a href="<?= e(base_url('index.php?route=qsl')) ?>" class="ghost">Réinitialiser</a>
        </form>
        <form method="post">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="generate_batch">
            <div class="actions">
                <button type="button" class="button secondary small" data-qso-toggle="all">Tout sélectionner</button>
                <button type="button" class="button secondary small" data-qso-toggle="none">Tout désélectionner</button>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr><th></th><th>Call</th><th>Date</th><th>UTC</th><th>Bande</th><th>Mode</th><th>RST</th><th>eQSL</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($filteredQsoRows as $row): ?>
                        <tr>
                            <td><input type="checkbox" name="qso_ids[]" value="<?= (int) $row['id'] ?>"></td>
                            <td><?= e((string) $row['qso_call']) ?></td>
                            <td><?= e(qsl_format_display_date((string) ($row['qso_date'] ?? ''))) ?></td>
                            <td><?= e(qsl_format_display_time((string) ($row['time_on'] ?? ''))) ?></td>
                            <td><?= e((string) $row['band']) ?></td>
                            <td><?= e((string) $row['mode']) ?></td>
                            <td><?= e((string) $row['rst_sent']) ?>/<?= e((string) $row['rst_recv']) ?></td>
                            <td><?= e($qsoEqslStatus($row)) ?></td>
                            <td><button class="button secondary small" type="submit" name="delete_qso_id" value="<?= (int) $row['id'] ?>">Supprimer</button></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($filteredQsoRows === []): ?>
                <p class="help">Aucun QSO ne correspond aux filtres actifs.</p>
            <?php endif; ?>
            <p><button class="button">Générer les QSL sélectionnées</button></p>
        </form>
    <?php endif; ?>
</section>

<section class="card">
    <div class="row-between">
        <h2>QSL générées</h2>
        <span><?= count($qslRows) ?> carte(s)</span>
    </div>
    <?php if ($qslRows === []): ?>
        <p>Aucune QSL générée pour le moment.</p>
    <?php else: ?>
        <form method="get" class="inline-form qsl-filters">
            <input type="hidden" name="route" value="qsl">
            <input type="text" name="qsl_search" value="<?= e($qslSearch) ?>" placeholder="Rechercher une QSL (titre, call, bande...)">
            <button type="submit" class="button secondary small">Filtrer</button>
            <a href="<?= e(base_url('index.php?route=qsl')) ?>" class="ghost">Réinitialiser</a>
        </form>
        <div class="table-wrap">
            <table>
                <thead>
                <tr><th>Titre</th><th>QSO</th><th>Date</th><th>Bande</th><th>Mode</th><th>Aperçu</th><th>Export</th><th>Action</th></tr>
                </thead>
                <tbody>
                <?php foreach ($filteredQslRows as $row): ?>
                    <tr>
                        <td><?= e((string) $row['title']) ?></td>
                        <td><?= e((string) $row['qso_call']) ?></td>
                        <td><?= e(qsl_format_display_date((string) ($row['qso_date'] ?? ''))) ?></td>
                        <td><?= e((string) $row['band']) ?></td>
                        <td><?= e((string) $row['mode']) ?></td>
                        <td><a href="<?= e(base_url('index.php?route=qsl_preview&id=' . (int) $row['id'])) ?>">Voir</a></td>
                        <td><a href="<?= e(base_url('index.php?route=qsl_export&id=' . (int) $row['id'])) ?>">Télécharger SVG</a></td>
                        <td>
                            <form method="post" class="inline-form">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete_qsl">
                                <input type="hidden" name="qsl_id" value="<?= (int) $row['id'] ?>">
                                <button class="button secondary small" type="submit">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($filteredQslRows === []): ?>
            <p class="help">Aucune QSL ne correspond à la recherche.</p>
        <?php endif; ?>
    <?php endif; ?>
</section>
</div>
<script nonce="<?= e(csp_nonce()) ?>">
document.querySelectorAll('[data-qso-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const table = button.closest('form');
        if (!table) {
            return;
        }
        const checked = button.dataset.qsoToggle === 'all';
        table.querySelectorAll('input[name="qso_ids[]"]').forEach((checkbox) => {
            checkbox.checked = checked;
        });
    });
});

document.querySelectorAll('[data-wysiwyg]').forEach((wrapper) => {
    const editor = wrapper.querySelector('[data-wysiwyg-editor]');
    const hiddenInput = wrapper.querySelector('[data-wysiwyg-input]');
    const counter = wrapper.querySelector('[data-wysiwyg-counter]');
    if (!editor || !hiddenInput || !counter) {
        return;
    }

    const maxLength = Number(wrapper.getAttribute('data-max-length') || '180');
    const sync = () => {
        const plainText = (editor.textContent || '').replace(/\s+/g, ' ').trim();
        const normalized = plainText.slice(0, maxLength);
        hiddenInput.value = normalized;
        if (plainText !== normalized) {
            editor.textContent = normalized;
            const range = document.createRange();
            range.selectNodeContents(editor);
            range.collapse(false);
            const selection = window.getSelection();
            selection?.removeAllRanges();
            selection?.addRange(range);
        }
        const remaining = Math.max(0, maxLength - normalized.length);
        counter.textContent = `${remaining} caractères restants.`;
    };

    wrapper.querySelectorAll('[data-wysiwyg-command]').forEach((control) => {
        control.addEventListener('click', () => {
            const command = control.getAttribute('data-wysiwyg-command');
            if (!command) {
                return;
            }
            editor.focus();
            document.execCommand(command, false);
            sync();
        });
    });

    editor.addEventListener('input', sync);
    const form = wrapper.closest('form');
    form?.addEventListener('submit', sync);
    sync();
});

(() => {
    const previewCard = document.querySelector('[data-qsl-preview-card]');
    if (!previewCard) {
        return;
    }

    const primaryInput = document.querySelector('[data-preview-color-primary]');
    const secondaryInput = document.querySelector('[data-preview-color-secondary]');
    const imageInput = document.querySelector('[data-preview-image-input]');
    const applyGradient = () => {
        const primary = primaryInput?.value || '#0B1F3A';
        const secondary = secondaryInput?.value || '#1D4ED8';
        previewCard.style.backgroundImage = `linear-gradient(135deg, ${primary}, ${secondary})`;
    };

    primaryInput?.addEventListener('input', applyGradient);
    secondaryInput?.addEventListener('input', applyGradient);
    applyGradient();

    imageInput?.addEventListener('change', () => {
        const file = imageInput.files?.[0];
        if (!file) {
            applyGradient();
            return;
        }
        const reader = new FileReader();
        reader.onload = () => {
            if (typeof reader.result === 'string') {
                previewCard.style.backgroundImage = `linear-gradient(rgba(5, 10, 25, .35), rgba(5, 10, 25, .35)), url('${reader.result}')`;
                previewCard.style.backgroundSize = 'cover';
                previewCard.style.backgroundPosition = 'center';
            }
        };
        reader.readAsDataURL(file);
    });
})();
</script>
<?php
echo render_layout((string) ob_get_clean(), 'QSL');
