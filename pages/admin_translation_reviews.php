<?php
declare(strict_types=1);

require_permission('admin.access');
$i18n = [
    'fr' => ['invalid_lang' => 'Langue invalide.', 'ok_news' => 'Traduction d’actualité relue.', 'ok_article' => 'Traduction d’article relue.', 'news_title' => 'Relecture des actualités', 'article_title' => 'Relecture des articles', 'label_title' => 'Titre', 'label_excerpt' => 'Extrait', 'label_content' => 'Contenu', 'submit' => 'Valider la traduction', 'no_news' => 'Aucune traduction d’actualité en attente.', 'no_article' => 'Aucune traduction d’article en attente.', 'layout' => 'Relecture linguistique', 'meta_desc' => 'Validation des traductions automatiques des contenus.'],
    'en' => ['invalid_lang' => 'Invalid language.', 'ok_news' => 'News translation reviewed.', 'ok_article' => 'Article translation reviewed.', 'news_title' => 'News review', 'article_title' => 'Article review', 'label_title' => 'Title', 'label_excerpt' => 'Excerpt', 'label_content' => 'Content', 'submit' => 'Approve translation', 'no_news' => 'No pending news translation.', 'no_article' => 'No pending article translation.', 'layout' => 'Translation review', 'meta_desc' => 'Review and validate automated content translations.'],
    'de' => ['invalid_lang' => 'Ungültige Sprache.', 'ok_news' => 'Nachrichtenübersetzung geprüft.', 'ok_article' => 'Artikelübersetzung geprüft.', 'news_title' => 'Nachrichtenprüfung', 'article_title' => 'Artikelprüfung', 'label_title' => 'Titel', 'label_excerpt' => 'Auszug', 'label_content' => 'Inhalt', 'submit' => 'Übersetzung bestätigen', 'no_news' => 'Keine ausstehende Nachrichtenübersetzung.', 'no_article' => 'Keine ausstehende Artikelübersetzung.', 'layout' => 'Sprachprüfung', 'meta_desc' => 'Automatische Inhaltsübersetzungen prüfen und validieren.'],
    'nl' => ['invalid_lang' => 'Ongeldige taal.', 'ok_news' => 'Nieuwsvertaling nagekeken.', 'ok_article' => 'Artikelvertaling nagekeken.', 'news_title' => 'Nieuwscontrole', 'article_title' => 'Artikelcontrole', 'label_title' => 'Titel', 'label_excerpt' => 'Uittreksel', 'label_content' => 'Inhoud', 'submit' => 'Vertaling goedkeuren', 'no_news' => 'Geen wachtende nieuwsvertaling.', 'no_article' => 'Geen wachtende artikelvertaling.', 'layout' => 'Taalcontrole', 'meta_desc' => 'Automatische inhoudsvertalingen nakijken en valideren.'],
];
$localeUi = strtolower((string) ($_SESSION['locale'] ?? 'fr'));
$t = $i18n[$localeUi] ?? $i18n['fr'];

set_page_meta([
    'title' => (string) $t['layout'],
    'description' => (string) $t['meta_desc'],
    'robots' => 'noindex,nofollow',
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');
        $id = (int) ($_POST['id'] ?? 0);
        $locale = (string) ($_POST['locale'] ?? 'en');
        if (!in_array($locale, ['en', 'de', 'nl'], true)) {
            throw new RuntimeException((string) $t['invalid_lang']);
        }
        if ($action === 'review_news_translation') {
            $stmt = db()->prepare('UPDATE news_translations SET title = ?, excerpt = ?, content = ?, status = "reviewed", reviewed_by = ?, reviewed_at = NOW() WHERE id = ? AND locale = ?');
            $stmt->execute([
                trim((string) ($_POST['title'] ?? '')),
                trim((string) ($_POST['excerpt'] ?? '')),
                sanitize_rich_html((string) ($_POST['content'] ?? '')),
                (int) current_user()['id'],
                $id,
                $locale,
            ]);
            set_flash('success', (string) $t['ok_news']);
        } elseif ($action === 'review_article_translation') {
            $stmt = db()->prepare('UPDATE article_translations SET title = ?, excerpt = ?, content = ?, status = "reviewed", reviewed_by = ?, reviewed_at = NOW() WHERE id = ? AND locale = ?');
            $stmt->execute([
                trim((string) ($_POST['title'] ?? '')),
                trim((string) ($_POST['excerpt'] ?? '')),
                sanitize_rich_html((string) ($_POST['content'] ?? '')),
                (int) current_user()['id'],
                $id,
                $locale,
            ]);
            set_flash('success', (string) $t['ok_article']);
        }
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
    }
    redirect('admin_translation_reviews');
}

$newsTranslations = table_exists('news_translations')
    ? db()->query('SELECT nt.*, np.title AS source_title FROM news_translations nt INNER JOIN news_posts np ON np.id = nt.news_post_id WHERE nt.status IN ("auto", "needs_review") ORDER BY nt.updated_at DESC')->fetchAll()
    : [];
$articleTranslations = table_exists('article_translations')
    ? db()->query('SELECT at.*, a.title AS source_title FROM article_translations at INNER JOIN articles a ON a.id = at.article_id WHERE at.status IN ("auto", "needs_review") ORDER BY at.updated_at DESC')->fetchAll()
    : [];

ob_start();
?>
<div class="grid-2">
  <section class="card">
    <h1><?= e((string) $t['news_title']) ?></h1>
    <?php foreach ($newsTranslations as $translation): ?>
      <article class="card inner-card">
        <h3><?= e((string) $translation['source_title']) ?> — <?= strtoupper(e((string) $translation['locale'])) ?></h3>
        <form method="post" class="stack">
          <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="review_news_translation">
          <input type="hidden" name="id" value="<?= (int) $translation['id'] ?>">
          <input type="hidden" name="locale" value="<?= e((string) $translation['locale']) ?>">
          <label><?= e((string) $t['label_title']) ?><input type="text" name="title" value="<?= e((string) $translation['title']) ?>"></label>
          <label><?= e((string) $t['label_excerpt']) ?><textarea name="excerpt" rows="3"><?= e((string) $translation['excerpt']) ?></textarea></label>
          <label><?= e((string) $t['label_content']) ?><textarea name="content" rows="8"><?= e((string) $translation['content']) ?></textarea></label>
          <button class="button"><?= e((string) $t['submit']) ?></button>
        </form>
      </article>
    <?php endforeach; ?>
    <?php if ($newsTranslations === []): ?><p><?= e((string) $t['no_news']) ?></p><?php endif; ?>
  </section>

  <section class="card">
    <h1><?= e((string) $t['article_title']) ?></h1>
    <?php foreach ($articleTranslations as $translation): ?>
      <article class="card inner-card">
        <h3><?= e((string) $translation['source_title']) ?> — <?= strtoupper(e((string) $translation['locale'])) ?></h3>
        <form method="post" class="stack">
          <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="review_article_translation">
          <input type="hidden" name="id" value="<?= (int) $translation['id'] ?>">
          <input type="hidden" name="locale" value="<?= e((string) $translation['locale']) ?>">
          <label><?= e((string) $t['label_title']) ?><input type="text" name="title" value="<?= e((string) $translation['title']) ?>"></label>
          <label><?= e((string) $t['label_excerpt']) ?><textarea name="excerpt" rows="3"><?= e((string) $translation['excerpt']) ?></textarea></label>
          <label><?= e((string) $t['label_content']) ?><textarea name="content" rows="8"><?= e((string) $translation['content']) ?></textarea></label>
          <button class="button"><?= e((string) $t['submit']) ?></button>
        </form>
      </article>
    <?php endforeach; ?>
    <?php if ($articleTranslations === []): ?><p><?= e((string) $t['no_article']) ?></p><?php endif; ?>
  </section>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['layout']);
