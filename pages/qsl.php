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

ob_start();
?>
<div class="grid-2">
    <section class="card">
        <h1>QSL Designer</h1>
        <p>Crée une carte QSL manuelle ou génère un lot à partir d’un fichier ADIF importé.</p>
        <form method="post">
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
            </div>
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
        <form method="post">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="generate_batch">
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr><th></th><th>Call</th><th>Date</th><th>UTC</th><th>Bande</th><th>Mode</th><th>RST</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($qsoRows as $row): ?>
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
        <div class="table-wrap">
            <table>
                <thead>
                <tr><th>Titre</th><th>QSO</th><th>Date</th><th>Bande</th><th>Mode</th><th>Aperçu</th><th>Export</th><th>Action</th></tr>
                </thead>
                <tbody>
                <?php foreach ($qslRows as $row): ?>
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
    <?php endif; ?>
</section>
<?php
echo render_layout((string) ob_get_clean(), 'QSL');
