<?php
declare(strict_types=1);

require_permission('admin.access');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');
        $id = (int) ($_POST['id'] ?? 0);
        $locale = (string) ($_POST['locale'] ?? 'en');
        if (!in_array($locale, ['en', 'de', 'nl'], true)) {
            throw new RuntimeException('Langue invalide.');
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
            set_flash('success', 'Traduction d’actualité relue.');
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
            set_flash('success', 'Traduction d’article relue.');
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
    <h1>Relecture des actualités</h1>
    <?php foreach ($newsTranslations as $translation): ?>
      <article class="card inner-card">
        <h3><?= e((string) $translation['source_title']) ?> — <?= strtoupper(e((string) $translation['locale'])) ?></h3>
        <form method="post" class="stack">
          <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="review_news_translation">
          <input type="hidden" name="id" value="<?= (int) $translation['id'] ?>">
          <input type="hidden" name="locale" value="<?= e((string) $translation['locale']) ?>">
          <label>Titre<input type="text" name="title" value="<?= e((string) $translation['title']) ?>"></label>
          <label>Extrait<textarea name="excerpt" rows="3"><?= e((string) $translation['excerpt']) ?></textarea></label>
          <label>Contenu<textarea name="content" rows="8"><?= e((string) $translation['content']) ?></textarea></label>
          <button class="button">Valider la traduction</button>
        </form>
      </article>
    <?php endforeach; ?>
    <?php if ($newsTranslations === []): ?><p>Aucune traduction d’actualité en attente.</p><?php endif; ?>
  </section>

  <section class="card">
    <h1>Relecture des articles</h1>
    <?php foreach ($articleTranslations as $translation): ?>
      <article class="card inner-card">
        <h3><?= e((string) $translation['source_title']) ?> — <?= strtoupper(e((string) $translation['locale'])) ?></h3>
        <form method="post" class="stack">
          <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="review_article_translation">
          <input type="hidden" name="id" value="<?= (int) $translation['id'] ?>">
          <input type="hidden" name="locale" value="<?= e((string) $translation['locale']) ?>">
          <label>Titre<input type="text" name="title" value="<?= e((string) $translation['title']) ?>"></label>
          <label>Extrait<textarea name="excerpt" rows="3"><?= e((string) $translation['excerpt']) ?></textarea></label>
          <label>Contenu<textarea name="content" rows="8"><?= e((string) $translation['content']) ?></textarea></label>
          <button class="button">Valider la traduction</button>
        </form>
      </article>
    <?php endforeach; ?>
    <?php if ($articleTranslations === []): ?><p>Aucune traduction d’article en attente.</p><?php endif; ?>
  </section>
</div>
<?php
echo render_layout((string) ob_get_clean(), 'Relecture linguistique');
