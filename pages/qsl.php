<?php
declare(strict_types=1);

$user = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'import_adif') {
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
            $backgroundImageDataUri = qsl_background_upload_to_data_uri($_FILES['background_image'] ?? null);
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
                'background_primary' => trim((string) ($_POST['background_primary'] ?? '#0B1F3A')),
                'background_secondary' => trim((string) ($_POST['background_secondary'] ?? '#1D4ED8')),
                'background_image_data_uri' => $backgroundImageDataUri,
            ];

            $svg = generate_qsl_svg($data);
            $payload = build_qsl_svg_payload($user, $data, (string) $data['comment']);
            $stmt = db()->prepare(
                'INSERT INTO qsl_cards (member_id, title, qso_call, qso_date, time_on, band, mode, rst_sent, rst_recv, template_name, svg_content)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                (int) $user['id'],
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
            $stmt->execute([$qsoId, (int) $user['id']]);
            set_flash('success', 'QSO supprimé.');
        } elseif ($action === 'delete_qsl') {
            $stmt = db()->prepare('DELETE FROM qsl_cards WHERE id = ? AND member_id = ? LIMIT 1');
            $stmt->execute([(int) ($_POST['qsl_id'] ?? 0), (int) $user['id']]);
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
$qsoLogs->execute([(int) $user['id']]);
$qsoRows = $qsoLogs->fetchAll();

$qslCards = db()->prepare('SELECT * FROM qsl_cards WHERE member_id = ? ORDER BY id DESC LIMIT 50');
$qslCards->execute([(int) $user['id']]);
$qslRows = $qslCards->fetchAll();

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

$pendingCount = 0;
foreach ($qsoRows as $row) {
    $key = qsl_normalize_callsign((string) ($row['qso_call'] ?? '')) . '|'
        . qsl_normalize_date((string) ($row['qso_date'] ?? '')) . '|'
        . qsl_normalize_time((string) ($row['time_on'] ?? ''));
    if (!isset($generatedByQsoId[$key])) {
        $pendingCount++;
    }
}

ksort($qsoBandOptions);
ksort($qsoModeOptions);

ob_start();
?>
<div class="qsl-page">
<section class="card qsl-kpis">
    <div class="qsl-kpi">
        <p class="help">QSO enregistrés</p>
        <strong><?= count($qsoRows) ?></strong>
    </div>
    <div class="qsl-kpi">
        <p class="help">QSL créées</p>
        <strong><?= count($qslRows) ?></strong>
    </div>
    <div class="qsl-kpi">
        <p class="help">QSO sans QSL</p>
        <strong><?= $pendingCount ?></strong>
    </div>
</section>

<div class="grid-2">
    <section class="card">
        <h1>QSL Designer</h1>
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
                <label>Commentaire<textarea name="comment" rows="3" maxlength="180">TNX QSO 73</textarea></label>
                <label>Couleur de fond 1<input type="color" name="background_primary" value="#0B1F3A"></label>
                <label>Couleur de fond 2<input type="color" name="background_secondary" value="#1D4ED8"></label>
                <label>Image de fond (optionnel)
                    <input type="file" name="background_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                </label>
            </div>
            <p class="help">Sans image, un dégradé est généré automatiquement. Avec image, la photo est utilisée comme arrière-plan de la QSL.</p>
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

<section class="card">
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
                    <tr><th></th><th>Call</th><th>Date</th><th>UTC</th><th>Bande</th><th>Mode</th><th>RST</th><th>Action</th></tr>
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
</script>
<?php
echo render_layout((string) ob_get_clean(), 'QSL');
