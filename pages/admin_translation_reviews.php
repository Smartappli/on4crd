<?php
declare(strict_types=1);

require_permission('admin.access');
$i18n = i18n_load_array_file_once(__DIR__ . '/../app/i18n/admin_translation_reviews.php');
$i18n = i18n_expand_supported_locales($i18n);
$localeUi = current_locale();
$t = [];
foreach (array_keys($i18n['fr']) as $key) {
    $t[$key] = i18n_localized_value($i18n, $localeUi, (string) $key);
}
$tr = static function (string $key, string $fallback) use ($t): string {
    $value = trim((string) ($t[$key] ?? ''));
    return $value !== '' ? $value : $fallback;
};

set_page_meta([
    'title' => (string) $tr('layout', 'Translation review'),
    'description' => (string) $tr('meta_desc', 'Review and validate automated content translations.'),
    'robots' => 'noindex,nofollow',
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');
        $id = (int) ($_POST['id'] ?? 0);
        $locale = (string) ($_POST['locale'] ?? 'en');
        $reviewableLocales = array_values(array_filter(supported_locales(), static fn(string $supportedLocale): bool => $supportedLocale !== 'fr'));
        if (!in_array($locale, $reviewableLocales, true)) {
            throw new RuntimeException((string) $tr('invalid_lang', 'Invalid language.'));
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
            set_flash('success', (string) $tr('ok_news', 'News translation reviewed.'));
        } elseif ($action === 'review_article_translation') {
            $stmt = db()->prepare('UPDATE article_translations SET title = ?, excerpt = ?, content = ?, status = "reviewed", reviewed_by = ?, reviewed_at = NOW() WHERE id = ? AND locale = ?');
            $stmt->execute([
                trim((string) ($_POST['title'] ?? '')),
                trim((string) ($_POST['excerpt'] ?? '')),
                article_sanitize_content((string) ($_POST['content'] ?? '')),
                (int) current_user()['id'],
                $id,
                $locale,
            ]);
            set_flash('success', (string) $tr('ok_article', 'Article translation reviewed.'));
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

$chatbotI18nQa = ['ok' => true, 'issues' => []];
try {
    $chatbotDir = dirname(__DIR__) . '/app/i18n/chatbot';
    $requiredLocales = ['en', 'fr', 'de', 'nl', 'es', 'it', 'pt', 'ar', 'hi', 'ja', 'zh', 'bn', 'ru', 'id'];
    $requiredKeys = array_keys(i18n_load_array_file_once($chatbotDir . '/en.php'));
    foreach ($requiredLocales as $locale) {
        $path = $chatbotDir . '/' . $locale . '.php';
        if (!is_file($path)) {
            $chatbotI18nQa['ok'] = false;
            $chatbotI18nQa['issues'][] = $locale . ': missing file';
            continue;
        }
        $dict = i18n_load_array_file_once($path);
        if (!is_array($dict)) {
            $chatbotI18nQa['ok'] = false;
            $chatbotI18nQa['issues'][] = $locale . ': invalid dictionary';
            continue;
        }
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $dict)) {
                $chatbotI18nQa['ok'] = false;
                $chatbotI18nQa['issues'][] = $locale . ': missing key "' . $key . '"';
                continue;
            }
            $value = trim((string) $dict[$key]);
            if ($value === '') {
                $chatbotI18nQa['ok'] = false;
                $chatbotI18nQa['issues'][] = $locale . ': empty key "' . $key . '"';
                continue;
            }
            if (preg_match('/(?:\?{2,}|Ã|Â|ðŸ|�)/u', $value) === 1) {
                $chatbotI18nQa['ok'] = false;
                $chatbotI18nQa['issues'][] = $locale . ': suspicious encoding in key "' . $key . '"';
            }
        }
    }
} catch (Throwable $throwable) {
    $chatbotI18nQa['ok'] = false;
    $chatbotI18nQa['issues'][] = $throwable->getMessage();
}

$normalizeTaxonomy = static function (string $value): string {
    $value = mb_strtolower(trim($value));
    $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
    $value = str_replace(['_', '-', '.', ',', ';', ':'], ' ', $value);
    $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
    return trim($value);
};

$taxonomySuggestions = [];
if (table_exists('articles')) {
    $rows = db()->query('SELECT category, COUNT(*) AS total FROM articles WHERE category IS NOT NULL AND category <> "" GROUP BY category ORDER BY total DESC')->fetchAll() ?: [];
    $byNorm = [];
    foreach ($rows as $row) {
        $raw = trim((string) ($row['category'] ?? ''));
        if ($raw === '') {
            continue;
        }
        $norm = $normalizeTaxonomy($raw);
        if ($norm === '') {
            continue;
        }
        $byNorm[$norm][] = ['value' => $raw, 'count' => (int) ($row['total'] ?? 0)];
    }
    foreach ($byNorm as $norm => $variants) {
        if (count($variants) > 1) {
            $taxonomySuggestions[] = ['scope' => 'articles.category', 'normalized' => $norm, 'variants' => $variants];
        }
    }
}
if (table_exists('member_library_documents')) {
    $rows = db()->query('SELECT category, COUNT(*) AS total FROM member_library_documents WHERE category IS NOT NULL AND category <> "" GROUP BY category ORDER BY total DESC')->fetchAll() ?: [];
    $byNorm = [];
    foreach ($rows as $row) {
        $raw = trim((string) ($row['category'] ?? ''));
        if ($raw === '') {
            continue;
        }
        $norm = $normalizeTaxonomy($raw);
        if ($norm === '') {
            continue;
        }
        $byNorm[$norm][] = ['value' => $raw, 'count' => (int) ($row['total'] ?? 0)];
    }
    foreach ($byNorm as $norm => $variants) {
        if (count($variants) > 1) {
            $taxonomySuggestions[] = ['scope' => 'member_library_documents.category', 'normalized' => $norm, 'variants' => $variants];
        }
    }

    $tagRows = db()->query('SELECT tags FROM member_library_documents WHERE tags IS NOT NULL AND tags <> ""')->fetchAll() ?: [];
    $tagCounts = [];
    foreach ($tagRows as $row) {
        $chunks = explode(',', (string) ($row['tags'] ?? ''));
        foreach ($chunks as $chunk) {
            $tag = trim($chunk);
            if ($tag === '') {
                continue;
            }
            $norm = $normalizeTaxonomy($tag);
            if ($norm === '') {
                continue;
            }
            $tagCounts[$norm][$tag] = ($tagCounts[$norm][$tag] ?? 0) + 1;
        }
    }
    foreach ($tagCounts as $norm => $variantsMap) {
        if (count($variantsMap) <= 1) {
            continue;
        }
        $variants = [];
        foreach ($variantsMap as $variant => $count) {
            $variants[] = ['value' => $variant, 'count' => (int) $count];
        }
        usort($variants, static fn(array $a, array $b): int => $b['count'] <=> $a['count']);
        $taxonomySuggestions[] = ['scope' => 'member_library_documents.tags', 'normalized' => $norm, 'variants' => $variants];
    }
}

ob_start();
?>
<div class="stack">
  <section class="card">
    <h1><?= e((string) $tr('assistant_title', 'Admin assistant: i18n and taxonomy QA')) ?></h1>
    <p class="help"><?= e((string) $tr('assistant_help', 'Use this panel to validate chatbot translations and detect taxonomy/tag duplicates before moderation.')) ?></p>
    <div class="grid-2">
      <article class="card inner-card">
        <h3><?= e((string) $tr('chatbot_i18n_title', 'Chatbot i18n quality')) ?></h3>
        <?php if ($chatbotI18nQa['ok']): ?>
          <p><?= e((string) $tr('chatbot_i18n_ok', 'All chatbot locale files are complete and consistent.')) ?></p>
        <?php else: ?>
          <ul class="help">
            <?php foreach ($chatbotI18nQa['issues'] as $issue): ?>
              <li><?= e((string) $issue) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </article>
      <article class="card inner-card">
        <h3><?= e((string) $tr('taxonomy_title', 'Taxonomy and tag suggestions')) ?></h3>
        <?php if ($taxonomySuggestions === []): ?>
          <p><?= e((string) $tr('taxonomy_empty', 'No duplicate variants detected.')) ?></p>
        <?php else: ?>
          <?php foreach ($taxonomySuggestions as $suggestion): ?>
            <details style="margin-bottom:.6rem;">
              <summary><strong><?= e((string) $suggestion['scope']) ?></strong> — <?= e((string) $suggestion['normalized']) ?></summary>
              <ul class="help">
                <?php foreach ((array) ($suggestion['variants'] ?? []) as $variant): ?>
                  <li><?= e((string) ($variant['value'] ?? '')) ?> (<?= (int) ($variant['count'] ?? 0) ?>)</li>
                <?php endforeach; ?>
              </ul>
            </details>
          <?php endforeach; ?>
        <?php endif; ?>
      </article>
    </div>
  </section>

  <div class="grid-2">
    <section class="card">
      <h2><?= e((string) $tr('news_title', 'News review')) ?></h2>
      <?php foreach ($newsTranslations as $translation): ?>
        <article class="card inner-card">
          <h3><?= e((string) $translation['source_title']) ?> — <?= strtoupper(e((string) $translation['locale'])) ?></h3>
          <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="review_news_translation">
            <input type="hidden" name="id" value="<?= (int) $translation['id'] ?>">
            <input type="hidden" name="locale" value="<?= e((string) $translation['locale']) ?>">
            <label><?= e((string) $tr('label_title', 'Title')) ?><input type="text" name="title" value="<?= e((string) $translation['title']) ?>"></label>
            <label><?= e((string) $tr('label_excerpt', 'Excerpt')) ?><textarea name="excerpt" rows="3"><?= e((string) $translation['excerpt']) ?></textarea></label>
            <label><?= e((string) $tr('label_content', 'Content')) ?><textarea name="content" rows="8"><?= e((string) $translation['content']) ?></textarea></label>
            <button class="button"><?= e((string) $tr('submit', 'Approve translation')) ?></button>
          </form>
        </article>
      <?php endforeach; ?>
      <?php if ($newsTranslations === []): ?><p><?= e((string) $tr('no_news', 'No pending news translation.')) ?></p><?php endif; ?>
    </section>

    <section class="card">
      <h2><?= e((string) $tr('article_title', 'Article review')) ?></h2>
      <?php foreach ($articleTranslations as $translation): ?>
        <article class="card inner-card">
          <h3><?= e((string) $translation['source_title']) ?> — <?= strtoupper(e((string) $translation['locale'])) ?></h3>
          <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="review_article_translation">
            <input type="hidden" name="id" value="<?= (int) $translation['id'] ?>">
            <input type="hidden" name="locale" value="<?= e((string) $translation['locale']) ?>">
            <label><?= e((string) $tr('label_title', 'Title')) ?><input type="text" name="title" value="<?= e((string) $translation['title']) ?>"></label>
            <label><?= e((string) $tr('label_excerpt', 'Excerpt')) ?><textarea name="excerpt" rows="3"><?= e((string) $translation['excerpt']) ?></textarea></label>
            <label><?= e((string) $tr('label_content', 'Content')) ?><textarea name="content" rows="8"><?= e((string) $translation['content']) ?></textarea></label>
            <button class="button"><?= e((string) $tr('submit', 'Approve translation')) ?></button>
          </form>
        </article>
      <?php endforeach; ?>
      <?php if ($articleTranslations === []): ?><p><?= e((string) $tr('no_article', 'No pending article translation.')) ?></p><?php endif; ?>
    </section>
  </div>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $tr('layout', 'Translation review'));
