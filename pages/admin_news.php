<?php
declare(strict_types=1);

require_permission('admin.access');
$user = require_login();
$locale = current_locale();
$i18n = [
    'fr' => ['cant_publish' => 'Vous ne pouvez pas publier dans cette section.', 'title_required' => 'Titre obligatoire.', 'saved_pending' => 'Actualité enregistrée et envoyée en validation.', 'saved' => 'Actualité enregistrée.', 'invalid_status' => 'Statut invalide.', 'moderation_saved' => 'Décision de modération enregistrée.', 'manager_added' => 'Responsable de section ajouté.', 'edit' => 'Modifier', 'write' => 'Rédiger', 'news_item' => 'une actualité', 'help_intro' => 'Les textes sont rédigés en français. Les versions EN/DE/NL sont générées automatiquement, puis relues si nécessaire. Les responsables de section peuvent soumettre des contenus, les modérateurs les valident avant publication.', 'section' => 'Section', 'title' => 'Titre', 'slug' => 'Slug', 'excerpt' => 'Extrait', 'simple_html' => 'Contenu HTML simple', 'editor_tip' => 'Astuce: utilisez le bouton Importer Word (format .docx) dans la barre de l’éditeur.', 'status' => 'Statut', 'save' => 'Enregistrer', 'my_news' => 'Mes actualités / sections gérées', 'action' => 'Action', 'edit_action' => 'Éditer', 'no_news' => 'Aucune actualité.', 'moderation_queue' => 'File de modération', 'unknown' => 'inconnu', 'decision' => 'Décision', 'publish' => 'Publier', 'keep_pending' => 'Laisser en attente', 'reject' => 'Refuser', 'back_to_draft' => 'Repasser en brouillon', 'moderation_note' => 'Note de modération', 'save_decision' => 'Enregistrer la décision', 'no_pending_news' => 'Aucune actualité à modérer.', 'section_managers' => 'Responsables de sections', 'member' => 'Membre', 'assign' => 'Attribuer', 'callsign' => 'Indicatif', 'no_assignment' => 'Aucune attribution.', 'layout' => 'Actualités section'],
    'en' => ['cant_publish' => 'You cannot publish in this section.', 'title_required' => 'Title is required.', 'saved_pending' => 'News item saved and sent for moderation.', 'saved' => 'News item saved.', 'invalid_status' => 'Invalid status.', 'moderation_saved' => 'Moderation decision saved.', 'manager_added' => 'Section manager added.', 'edit' => 'Edit', 'write' => 'Write', 'news_item' => 'a news item', 'help_intro' => 'Texts are written in French. EN/DE/NL versions are generated automatically and then reviewed if needed. Section managers can submit content; moderators validate it before publication.', 'section' => 'Section', 'title' => 'Title', 'slug' => 'Slug', 'excerpt' => 'Excerpt', 'simple_html' => 'Simple HTML content', 'editor_tip' => 'Tip: use the Import Word button (.docx format) in the editor toolbar.', 'status' => 'Status', 'save' => 'Save', 'my_news' => 'My news / managed sections', 'action' => 'Action', 'edit_action' => 'Edit', 'no_news' => 'No news item.', 'moderation_queue' => 'Moderation queue', 'unknown' => 'unknown', 'decision' => 'Decision', 'publish' => 'Publish', 'keep_pending' => 'Keep pending', 'reject' => 'Reject', 'back_to_draft' => 'Move back to draft', 'moderation_note' => 'Moderation note', 'save_decision' => 'Save decision', 'no_pending_news' => 'No news to moderate.', 'section_managers' => 'Section managers', 'member' => 'Member', 'assign' => 'Assign', 'callsign' => 'Callsign', 'no_assignment' => 'No assignment.', 'layout' => 'Section news'],
    'de' => ['cant_publish' => 'Sie dürfen in diesem Bereich nicht veröffentlichen.', 'title_required' => 'Titel ist erforderlich.', 'saved_pending' => 'Nachricht gespeichert und zur Moderation gesendet.', 'saved' => 'Nachricht gespeichert.', 'invalid_status' => 'Ungültiger Status.', 'moderation_saved' => 'Moderationsentscheidung gespeichert.', 'manager_added' => 'Bereichsverantwortlicher hinzugefügt.', 'edit' => 'Bearbeiten', 'write' => 'Verfassen', 'news_item' => 'eine Nachricht', 'help_intro' => 'Texte werden auf Französisch verfasst. EN/DE/NL-Versionen werden automatisch erzeugt und bei Bedarf überprüft. Bereichsverantwortliche können Inhalte einreichen, Moderatoren geben sie vor Veröffentlichung frei.', 'section' => 'Bereich', 'title' => 'Titel', 'slug' => 'Slug', 'excerpt' => 'Auszug', 'simple_html' => 'Einfacher HTML-Inhalt', 'editor_tip' => 'Tipp: Verwenden Sie die Schaltfläche Word importieren (.docx) in der Editorleiste.', 'status' => 'Status', 'save' => 'Speichern', 'my_news' => 'Meine Nachrichten / verwaltete Bereiche', 'action' => 'Aktion', 'edit_action' => 'Bearbeiten', 'no_news' => 'Keine Nachricht.', 'moderation_queue' => 'Moderationswarteschlange', 'unknown' => 'unbekannt', 'decision' => 'Entscheidung', 'publish' => 'Veröffentlichen', 'keep_pending' => 'Ausstehend lassen', 'reject' => 'Ablehnen', 'back_to_draft' => 'Zurück auf Entwurf', 'moderation_note' => 'Moderationsnotiz', 'save_decision' => 'Entscheidung speichern', 'no_pending_news' => 'Keine zu moderierenden Nachrichten.', 'section_managers' => 'Bereichsverantwortliche', 'member' => 'Mitglied', 'assign' => 'Zuweisen', 'callsign' => 'Rufzeichen', 'no_assignment' => 'Keine Zuordnung.', 'layout' => 'Bereichsnachrichten'],
    'nl' => ['cant_publish' => 'Je kunt niet in deze sectie publiceren.', 'title_required' => 'Titel is verplicht.', 'saved_pending' => 'Nieuwsbericht opgeslagen en ter validatie verzonden.', 'saved' => 'Nieuwsbericht opgeslagen.', 'invalid_status' => 'Ongeldige status.', 'moderation_saved' => 'Moderatiebeslissing opgeslagen.', 'manager_added' => 'Sectieverantwoordelijke toegevoegd.', 'edit' => 'Bewerken', 'write' => 'Schrijven', 'news_item' => 'een nieuwsbericht', 'help_intro' => 'Teksten worden in het Frans geschreven. EN/DE/NL-versies worden automatisch gegenereerd en indien nodig nagelezen. Sectieverantwoordelijken kunnen inhoud indienen; moderatoren valideren vóór publicatie.', 'section' => 'Sectie', 'title' => 'Titel', 'slug' => 'Slug', 'excerpt' => 'Uittreksel', 'simple_html' => 'Eenvoudige HTML-inhoud', 'editor_tip' => 'Tip: gebruik de knop Word importeren (.docx) in de editorwerkbalk.', 'status' => 'Status', 'save' => 'Opslaan', 'my_news' => 'Mijn nieuws / beheerde secties', 'action' => 'Actie', 'edit_action' => 'Bewerken', 'no_news' => 'Geen nieuwsbericht.', 'moderation_queue' => 'Moderatiequeue', 'unknown' => 'onbekend', 'decision' => 'Beslissing', 'publish' => 'Publiceren', 'keep_pending' => 'In wachtrij laten', 'reject' => 'Weigeren', 'back_to_draft' => 'Terug naar concept', 'moderation_note' => 'Moderatienota', 'save_decision' => 'Beslissing opslaan', 'no_pending_news' => 'Geen nieuws ter moderatie.', 'section_managers' => 'Sectieverantwoordelijken', 'member' => 'Lid', 'assign' => 'Toewijzen', 'callsign' => 'Roepletters', 'no_assignment' => 'Geen toewijzing.', 'layout' => 'Sectienieuws'],
];
$t = $i18n[$locale] ?? $i18n['fr'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'save_post') {
            $postId = (int) ($_POST['post_id'] ?? 0);
            $sectionId = (int) ($_POST['section_id'] ?? 0);
            if (!has_permission('news.moderate') && !can_submit_news_in_section((int) $user['id'], $sectionId)) {
                throw new RuntimeException((string) $t['cant_publish']);
            }
            $status = has_permission('news.moderate') ? (string) ($_POST['status'] ?? 'published') : 'pending';
            $title = trim((string) ($_POST['title'] ?? ''));
            if ($title === '') {
                throw new RuntimeException((string) $t['title_required']);
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
            set_flash('success', $status === 'pending' ? (string) $t['saved_pending'] : (string) $t['saved']);
            redirect('admin_news');
        }

        if ($action === 'moderate_post') {
            require_permission('news.moderate');
            $postId = (int) ($_POST['post_id'] ?? 0);
            $status = (string) ($_POST['status'] ?? 'pending');
            if (!in_array($status, ['draft', 'pending', 'published', 'rejected'], true)) {
                throw new RuntimeException((string) $t['invalid_status']);
            }
            $note = trim((string) ($_POST['moderation_note'] ?? ''));
            db()->prepare('UPDATE news_posts SET status = ?, moderation_note = ?, moderator_id = ?, published_at = CASE WHEN ? = "published" THEN NOW() ELSE published_at END WHERE id = ?')->execute([$status, $note, (int) $user['id'], $status, $postId]);
            set_flash('success', (string) $t['moderation_saved']);
            redirect('admin_news');
        }

        if ($action === 'assign_section_manager') {
            require_permission('news.moderate');
            db()->prepare('INSERT IGNORE INTO news_section_managers (member_id, section_id) VALUES (?, ?)')->execute([(int) ($_POST['member_id'] ?? 0), (int) ($_POST['section_id'] ?? 0)]);
            set_flash('success', (string) $t['manager_added']);
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
$queue = has_permission('news.moderate') ? db()->query('SELECT p.*, s.name AS section_name, m.callsign AS author_callsign FROM news_posts p INNER JOIN news_sections s ON s.id = p.section_id LEFT JOIN members m ON m.id = p.author_id WHERE p.status IN ("pending", "rejected") ORDER BY p.updated_at ASC')->fetchAll() : [];
$members = has_permission('news.moderate') ? db()->query('SELECT id, callsign, full_name FROM members WHERE is_active = 1 ORDER BY callsign')->fetchAll() : [];
$sectionManagers = has_permission('news.moderate') ? db()->query('SELECT nsm.section_id, nsm.member_id, ns.name AS section_name, m.callsign FROM news_section_managers nsm INNER JOIN news_sections ns ON ns.id = nsm.section_id INNER JOIN members m ON m.id = nsm.member_id ORDER BY ns.sort_order, m.callsign')->fetchAll() : [];

ob_start();
?>
<div class="grid-2">
    <section class="card">
        <h1><?= $editing ? e((string) $t['edit']) : e((string) $t['write']) ?> <?= e((string) $t['news_item']) ?></h1>
        <p class="help"><?= e((string) $t['help_intro']) ?></p>
        <form method="post">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save_post">
            <input type="hidden" name="post_id" value="<?= (int) ($editing['id'] ?? 0) ?>">
            <label><?= e((string) $t['section']) ?>
                <select name="section_id">
                    <?php foreach ($sections as $section): ?>
                        <?php $sid = (int) $section['id']; if (!has_permission('news.moderate') && !can_submit_news_in_section((int) $user['id'], $sid)) { continue; } ?>
                        <option value="<?= $sid ?>" <?= (int) ($editing['section_id'] ?? 0) === $sid ? 'selected' : '' ?>><?= e((string) $section['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><?= e((string) $t['title']) ?><input type="text" name="title" value="<?= e((string) ($editing['title'] ?? '')) ?>" required></label>
            <label><?= e((string) $t['slug']) ?><input type="text" name="slug" value="<?= e((string) ($editing['slug'] ?? '')) ?>"></label>
            <label><?= e((string) $t['excerpt']) ?><textarea name="excerpt" rows="3"><?= e((string) ($editing['excerpt'] ?? '')) ?></textarea></label>
            <label><?= e((string) $t['simple_html']) ?><textarea name="content" rows="12"><?= e((string) ($editing['content'] ?? '<p></p>')) ?></textarea></label>
            <p class="help"><?= e((string) $t['editor_tip']) ?></p>
            <?php if (has_permission('news.moderate')): ?>
                <label><?= e((string) $t['status']) ?>
                    <select name="status"><?php foreach (['draft', 'pending', 'published', 'rejected'] as $status): ?><option value="<?= e($status) ?>" <?= (($editing['status'] ?? 'published') === $status) ? 'selected' : '' ?>><?= e(news_status_label($status)) ?></option><?php endforeach; ?></select>
                </label>
            <?php endif; ?>
            <p><button class="button"><?= e((string) $t['save']) ?></button></p>
        </form>
    </section>

    <section class="card">
        <h2><?= e((string) $t['my_news']) ?></h2>
        <div class="table-wrap"><table><thead><tr><th><?= e((string) $t['title']) ?></th><th><?= e((string) $t['section']) ?></th><th><?= e((string) $t['status']) ?></th><th><?= e((string) $t['action']) ?></th></tr></thead><tbody>
        <?php foreach ($posts as $post): ?><tr><td><?= e((string) $post['title']) ?></td><td><?= e((string) $post['section_name']) ?></td><td><span class="badge muted"><?= e(news_status_label((string) $post['status'])) ?></span></td><td><a href="<?= e(route_url('admin_news', ['edit' => (int) $post['id']])) ?>"><?= e((string) $t['edit_action']) ?></a></td></tr><?php endforeach; ?>
        <?php if ($posts === []): ?><tr><td colspan="4"><?= e((string) $t['no_news']) ?></td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
</div>

<?php if (has_permission('news.moderate')): ?>
<div class="grid-2">
    <section class="card">
        <h2><?= e((string) $t['moderation_queue']) ?></h2>
        <?php foreach ($queue as $post): ?>
            <article class="card inner-card">
                <div class="row-between"><div><h3><?= e((string) $post['title']) ?></h3><p class="help"><?= e((string) $post['section_name']) ?> — <?= e((string) ($post['author_callsign'] ?: $t['unknown'])) ?></p></div><span class="badge muted"><?= e(news_status_label((string) $post['status'])) ?></span></div>
                <p><?= e((string) $post['excerpt']) ?></p>
                <form method="post" class="stack"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="moderate_post"><input type="hidden" name="post_id" value="<?= (int) $post['id'] ?>">
                    <label><?= e((string) $t['decision']) ?><select name="status"><option value="published"><?= e((string) $t['publish']) ?></option><option value="pending"><?= e((string) $t['keep_pending']) ?></option><option value="rejected"><?= e((string) $t['reject']) ?></option><option value="draft"><?= e((string) $t['back_to_draft']) ?></option></select></label>
                    <label><?= e((string) $t['moderation_note']) ?><textarea name="moderation_note" rows="3"><?= e((string) ($post['moderation_note'] ?? '')) ?></textarea></label>
                    <p><button class="button"><?= e((string) $t['save_decision']) ?></button></p>
                </form>
            </article>
        <?php endforeach; ?>
        <?php if ($queue === []): ?><p><?= e((string) $t['no_pending_news']) ?></p><?php endif; ?>
    </section>

    <section class="card">
        <h2><?= e((string) $t['section_managers']) ?></h2>
        <form method="post"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="assign_section_manager">
            <label><?= e((string) $t['member']) ?><select name="member_id"><?php foreach ($members as $member): ?><option value="<?= (int) $member['id'] ?>"><?= e((string) $member['callsign']) ?> — <?= e((string) $member['full_name']) ?></option><?php endforeach; ?></select></label>
            <label><?= e((string) $t['section']) ?><select name="section_id"><?php foreach ($sections as $section): ?><option value="<?= (int) $section['id'] ?>"><?= e((string) $section['name']) ?></option><?php endforeach; ?></select></label>
            <p><button class="button"><?= e((string) $t['assign']) ?></button></p>
        </form>
        <div class="table-wrap"><table><thead><tr><th><?= e((string) $t['section']) ?></th><th><?= e((string) $t['callsign']) ?></th></tr></thead><tbody>
            <?php foreach ($sectionManagers as $item): ?><tr><td><?= e((string) $item['section_name']) ?></td><td><?= e((string) $item['callsign']) ?></td></tr><?php endforeach; ?>
            <?php if ($sectionManagers === []): ?><tr><td colspan="2"><?= e((string) $t['no_assignment']) ?></td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
</div>
<?php endif; ?>

<?php echo render_layout((string) ob_get_clean(), (string) $t['layout']);
