<?php
declare(strict_types=1);

require_permission('admin.access');
$user = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'save_post') {
            $postId = (int) ($_POST['post_id'] ?? 0);
            $sectionId = (int) ($_POST['section_id'] ?? 0);
            if (!has_permission('news.moderate') && !can_submit_news_in_section((int) $user['id'], $sectionId)) {
                throw new RuntimeException('Vous ne pouvez pas publier dans cette section.');
            }
            $status = has_permission('news.moderate')
                ? (string) ($_POST['status'] ?? 'published')
                : 'pending';
            $title = trim((string) ($_POST['title'] ?? ''));
            if ($title === '') {
                throw new RuntimeException('Titre obligatoire.');
            }
            $slug = trim((string) ($_POST['slug'] ?? ''));
            if ($slug === '') {
                $slug = slugify($title);
            }
            $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
            $content = sanitize_rich_html((string) ($_POST['content'] ?? ''));
            if ($postId > 0) {
                db()->prepare('UPDATE news_posts SET section_id = ?, slug = ?, title = ?, excerpt = ?, content = ?, status = ?, moderation_note = NULL, updated_at = NOW() WHERE id = ?')->execute([$sectionId, $slug, $title, $excerpt, $content, $status, $postId]);
            } else {
                db()->prepare('INSERT INTO news_posts (section_id, author_id, slug, title, excerpt, content, status, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')->execute([$sectionId, (int) $user['id'], $slug, $title, $excerpt, $content, $status, $status === 'published' ? date('Y-m-d H:i:s') : null]);
                $postId = (int) db()->lastInsertId();
            }
            news_translation_upsert($postId, 'en');
            news_translation_upsert($postId, 'de');
            news_translation_upsert($postId, 'nl');
            set_flash('success', $status === 'pending' ? 'Actualité enregistrée et envoyée en validation.' : 'Actualité enregistrée.');
            redirect('admin_news');
        }

        if ($action === 'moderate_post') {
            require_permission('news.moderate');
            $postId = (int) ($_POST['post_id'] ?? 0);
            $status = (string) ($_POST['status'] ?? 'pending');
            if (!in_array($status, ['draft', 'pending', 'published', 'rejected'], true)) {
                throw new RuntimeException('Statut invalide.');
            }
            $note = trim((string) ($_POST['moderation_note'] ?? ''));
            db()->prepare('UPDATE news_posts SET status = ?, moderation_note = ?, moderator_id = ?, published_at = CASE WHEN ? = "published" THEN NOW() ELSE published_at END WHERE id = ?')->execute([$status, $note, (int) $user['id'], $status, $postId]);
            set_flash('success', 'Décision de modération enregistrée.');
            redirect('admin_news');
        }

        if ($action === 'assign_section_manager') {
            require_permission('news.moderate');
            db()->prepare('INSERT IGNORE INTO news_section_managers (member_id, section_id) VALUES (?, ?)')->execute([(int) ($_POST['member_id'] ?? 0), (int) ($_POST['section_id'] ?? 0)]);
            set_flash('success', 'Responsable de section ajouté.');
            redirect('admin_news');
        }
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect('admin_news');
    }
}

$sections = db()->query('SELECT * FROM news_sections ORDER BY sort_order, name')->fetchAll();
$editing = null;
if (!empty($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM news_posts WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_GET['edit']]);
    $editing = $stmt->fetch() ?: null;
}

$params = [];
$sql = 'SELECT p.*, s.name AS section_name, m.callsign AS author_callsign FROM news_posts p INNER JOIN news_sections s ON s.id = p.section_id LEFT JOIN members m ON m.id = p.author_id';
if (!has_permission('news.moderate')) {
    $managedSections = managed_section_ids_for_member((int) $user['id']);
    $sql .= ' WHERE p.author_id = ?';
    $params[] = (int) $user['id'];
    if ($managedSections !== []) {
        $placeholders = implode(',', array_fill(0, count($managedSections), '?'));
        $sql .= ' OR p.section_id IN (' . $placeholders . ')';
        array_push($params, ...$managedSections);
    }
}
$sql .= ' ORDER BY p.updated_at DESC, p.id DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();
$queue = has_permission('news.moderate')
    ? db()->query('SELECT p.*, s.name AS section_name, m.callsign AS author_callsign FROM news_posts p INNER JOIN news_sections s ON s.id = p.section_id LEFT JOIN members m ON m.id = p.author_id WHERE p.status IN ("pending", "rejected") ORDER BY p.updated_at ASC')->fetchAll()
    : [];
$members = has_permission('news.moderate') ? db()->query('SELECT id, callsign, full_name FROM members WHERE is_active = 1 ORDER BY callsign')->fetchAll() : [];
$sectionManagers = has_permission('news.moderate') ? db()->query('SELECT nsm.section_id, nsm.member_id, ns.name AS section_name, m.callsign FROM news_section_managers nsm INNER JOIN news_sections ns ON ns.id = nsm.section_id INNER JOIN members m ON m.id = nsm.member_id ORDER BY ns.sort_order, m.callsign')->fetchAll() : [];

ob_start();
?>
<div class="grid-2">
    <section class="card">
        <h1><?= $editing ? 'Modifier' : 'Rédiger' ?> une actualité</h1>
        <p class="help">Les textes sont rédigés en français. Les versions EN/DE/NL sont générées automatiquement, puis relues si nécessaire. Les responsables de section peuvent soumettre des contenus, les modérateurs les valident avant publication.</p>
        <form method="post">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save_post">
            <input type="hidden" name="post_id" value="<?= (int) ($editing['id'] ?? 0) ?>">
            <label>Section
                <select name="section_id">
                    <?php foreach ($sections as $section): ?>
                        <?php
                        $sid = (int) $section['id'];
                        if (!has_permission('news.moderate') && !can_submit_news_in_section((int) $user['id'], $sid)) {
                            continue;
                        }
                        ?>
                        <option value="<?= $sid ?>" <?= (int) ($editing['section_id'] ?? 0) === $sid ? 'selected' : '' ?>><?= e((string) $section['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Titre
                <input type="text" name="title" value="<?= e((string) ($editing['title'] ?? '')) ?>" required>
            </label>
            <label>Slug
                <input type="text" name="slug" value="<?= e((string) ($editing['slug'] ?? '')) ?>"></label>
            <label>Extrait
                <textarea name="excerpt" rows="3"><?= e((string) ($editing['excerpt'] ?? '')) ?></textarea>
            </label>
            <label>Contenu HTML simple
                <textarea name="content" rows="12"><?= e((string) ($editing['content'] ?? '<p></p>')) ?></textarea>
            </label>
            <?php if (has_permission('news.moderate')): ?>
                <label>Statut
                    <select name="status">
                        <?php foreach (['draft', 'pending', 'published', 'rejected'] as $status): ?>
                            <option value="<?= e($status) ?>" <?= (($editing['status'] ?? 'published') === $status) ? 'selected' : '' ?>><?= e(news_status_label($status)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php endif; ?>
            <p><button class="button">Enregistrer</button></p>
        </form>
    </section>

    <section class="card">
        <h2>Mes actualités / sections gérées</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Titre</th><th>Section</th><th>Statut</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($posts as $post): ?>
                    <tr>
                        <td><?= e((string) $post['title']) ?></td>
                        <td><?= e((string) $post['section_name']) ?></td>
                        <td><span class="badge muted"><?= e(news_status_label((string) $post['status'])) ?></span></td>
                        <td><a href="<?= e(route_url('admin_news', ['edit' => (int) $post['id']])) ?>">Éditer</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($posts === []): ?>
                    <tr><td colspan="4">Aucune actualité.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php if (has_permission('news.moderate')): ?>
<div class="grid-2">
    <section class="card">
        <h2>File de modération</h2>
        <?php foreach ($queue as $post): ?>
            <article class="card inner-card">
                <div class="row-between">
                    <div>
                        <h3><?= e((string) $post['title']) ?></h3>
                        <p class="help"><?= e((string) $post['section_name']) ?> — <?= e((string) ($post['author_callsign'] ?: 'inconnu')) ?></p>
                    </div>
                    <span class="badge muted"><?= e(news_status_label((string) $post['status'])) ?></span>
                </div>
                <p><?= e((string) $post['excerpt']) ?></p>
                <form method="post" class="stack">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="moderate_post">
                    <input type="hidden" name="post_id" value="<?= (int) $post['id'] ?>">
                    <label>Décision
                        <select name="status">
                            <option value="published">Publier</option>
                            <option value="pending">Laisser en attente</option>
                            <option value="rejected">Refuser</option>
                            <option value="draft">Repasser en brouillon</option>
                        </select>
                    </label>
                    <label>Note de modération
                        <textarea name="moderation_note" rows="3"><?= e((string) ($post['moderation_note'] ?? '')) ?></textarea>
                    </label>
                    <p><button class="button">Enregistrer la décision</button></p>
                </form>
            </article>
        <?php endforeach; ?>
        <?php if ($queue === []): ?><p>Aucune actualité à modérer.</p><?php endif; ?>
    </section>

    <section class="card">
        <h2>Responsables de sections</h2>
        <form method="post">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="assign_section_manager">
            <label>Membre
                <select name="member_id">
                    <?php foreach ($members as $member): ?>
                        <option value="<?= (int) $member['id'] ?>"><?= e((string) $member['callsign']) ?> — <?= e((string) $member['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Section
                <select name="section_id">
                    <?php foreach ($sections as $section): ?>
                        <option value="<?= (int) $section['id'] ?>"><?= e((string) $section['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <p><button class="button">Attribuer</button></p>
        </form>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Section</th><th>Indicatif</th></tr></thead>
                <tbody>
                <?php foreach ($sectionManagers as $item): ?>
                    <tr><td><?= e((string) $item['section_name']) ?></td><td><?= e((string) $item['callsign']) ?></td></tr>
                <?php endforeach; ?>
                <?php if ($sectionManagers === []): ?><tr><td colspan="2">Aucune attribution.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php endif; ?>

<?php
echo render_layout((string) ob_get_clean(), 'Actualités section');
