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
$tr = static function (string $key) use ($t): string {
    $value = trim((string) ($t[$key] ?? ''));
    return $value;
};
$reviewableLocales = array_values(array_filter(supported_locales(), static fn(string $supportedLocale): bool => $supportedLocale !== 'fr'));
$reviewLocale = strtolower(trim((string) ($_GET['review_locale'] ?? '')));
if ($reviewLocale !== '' && !in_array($reviewLocale, $reviewableLocales, true)) {
    $reviewLocale = '';
}
$reviewType = strtolower(trim((string) ($_GET['review_type'] ?? 'all')));
if (!in_array($reviewType, ['all', 'news', 'articles'], true)) {
    $reviewType = 'all';
}
$filterLabelsByLocale = [
    'fr' => ['language' => 'Langue', 'all_languages' => 'Toutes les langues', 'type' => 'Type de contenu', 'all_content' => 'Tous les contenus', 'apply' => 'Filtrer', 'source' => 'Source'],
    'en' => ['language' => 'Language', 'all_languages' => 'All languages', 'type' => 'Content type', 'all_content' => 'All content', 'apply' => 'Filter', 'source' => 'Source'],
    'de' => ['language' => 'Sprache', 'all_languages' => 'Alle Sprachen', 'type' => 'Inhaltstyp', 'all_content' => 'Alle Inhalte', 'apply' => 'Filtern', 'source' => 'Quelle'],
    'nl' => ['language' => 'Taal', 'all_languages' => 'Alle talen', 'type' => 'Inhoudstype', 'all_content' => 'Alle inhoud', 'apply' => 'Filteren', 'source' => 'Bron'],
];
$filterLabels = $filterLabelsByLocale[$localeUi] ?? $filterLabelsByLocale['en'];

set_page_meta([
    'title' => (string) $tr('layout'),
    'description' => (string) $tr('meta_desc'),
    'robots' => 'noindex,nofollow',
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');
        $id = (int) ($_POST['id'] ?? 0);
        $locale = (string) ($_POST['locale'] ?? 'en');
        if (!in_array($locale, $reviewableLocales, true)) {
            throw new RuntimeException((string) $tr('invalid_lang'));
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
            set_flash('success', (string) $tr('ok_news'));
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
            set_flash('success', (string) $tr('ok_article'));
        }
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
    }
    $returnLocale = strtolower(trim((string) ($_POST['return_locale'] ?? '')));
    $returnType = strtolower(trim((string) ($_POST['return_type'] ?? 'all')));
    redirect_url(route_url_clean('admin_translation_reviews', [
        'review_locale' => in_array($returnLocale, $reviewableLocales, true) ? $returnLocale : '',
        'review_type' => in_array($returnType, ['all', 'news', 'articles'], true) ? $returnType : 'all',
    ]));
}

$newsTranslations = [];
if ($reviewType !== 'articles' && table_exists('news_translations')) {
    $newsLocaleSql = $reviewLocale !== '' ? ' AND nt.locale = ?' : '';
    $newsStmt = db()->prepare('SELECT nt.*, np.title AS source_title, np.excerpt AS source_excerpt, np.content AS source_content FROM news_translations nt INNER JOIN news_posts np ON np.id = nt.news_post_id WHERE nt.status IN ("auto", "needs_review")' . $newsLocaleSql . ' ORDER BY nt.updated_at DESC');
    $newsStmt->execute($reviewLocale !== '' ? [$reviewLocale] : []);
    $newsTranslations = $newsStmt->fetchAll() ?: [];
}
$articleTranslations = [];
if ($reviewType !== 'news' && table_exists('article_translations')) {
    $articleLocaleSql = $reviewLocale !== '' ? ' AND at.locale = ?' : '';
    $articleStmt = db()->prepare('SELECT at.*, a.title AS source_title, a.excerpt AS source_excerpt, a.content AS source_content FROM article_translations at INNER JOIN articles a ON a.id = at.article_id WHERE at.status IN ("auto", "needs_review")' . $articleLocaleSql . ' ORDER BY at.updated_at DESC');
    $articleStmt->execute($reviewLocale !== '' ? [$reviewLocale] : []);
    $articleTranslations = $articleStmt->fetchAll() ?: [];
}

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
  <section class="card admin-translation-filter-card">
    <div class="admin-section-head">
      <div><h1><?= e((string) $tr('layout')) ?></h1><p class="help"><?= e((string) $tr('meta_desc')) ?></p></div>
      <span class="badge muted"><?= count($newsTranslations) + count($articleTranslations) ?></span>
    </div>
    <form method="get" class="inline-form admin-translation-filters">
      <label><?= e($filterLabels['language']) ?>
        <select name="review_locale">
          <option value=""><?= e($filterLabels['all_languages']) ?></option>
          <?php foreach ($reviewableLocales as $candidateLocale): ?>
            <option value="<?= e($candidateLocale) ?>" <?= $reviewLocale === $candidateLocale ? 'selected' : '' ?>><?= e(strtoupper($candidateLocale)) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label><?= e($filterLabels['type']) ?>
        <select name="review_type">
          <option value="all" <?= $reviewType === 'all' ? 'selected' : '' ?>><?= e($filterLabels['all_content']) ?></option>
          <option value="news" <?= $reviewType === 'news' ? 'selected' : '' ?>><?= e((string) $tr('news_title')) ?></option>
          <option value="articles" <?= $reviewType === 'articles' ? 'selected' : '' ?>><?= e((string) $tr('article_title')) ?></option>
        </select>
      </label>
      <button class="button secondary" type="submit"><?= e($filterLabels['apply']) ?></button>
    </form>
  </section>
  <section class="card">
    <h1><?= e((string) $tr('assistant_title')) ?></h1>
    <p class="help"><?= e((string) $tr('assistant_help')) ?></p>
    <div class="grid-2">
      <article class="card inner-card">
        <h3><?= e((string) $tr('chatbot_i18n_title')) ?></h3>
        <?php if ($chatbotI18nQa['ok']): ?>
          <p><?= e((string) $tr('chatbot_i18n_ok')) ?></p>
        <?php else: ?>
          <ul class="help">
            <?php foreach ($chatbotI18nQa['issues'] as $issue): ?>
              <li><?= e((string) $issue) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </article>
      <article class="card inner-card">
        <h3><?= e((string) $tr('taxonomy_title')) ?></h3>
        <?php if ($taxonomySuggestions === []): ?>
          <p><?= e((string) $tr('taxonomy_empty')) ?></p>
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

  <div class="stack admin-translation-review-queue">
    <?php if ($reviewType !== 'articles'): ?>
    <section class="card">
      <h2><?= e((string) $tr('news_title')) ?></h2>
      <?php foreach ($newsTranslations as $translation): ?>
        <article class="card inner-card admin-translation-review-card">
          <h3><?= e((string) $translation['source_title']) ?> — <?= strtoupper(e((string) $translation['locale'])) ?></h3>
          <div class="admin-translation-compare">
          <aside class="admin-translation-source" aria-label="FR">
            <h4><?= e($filterLabels['source']) ?> — FR</h4>
            <strong><?= e((string) $translation['source_title']) ?></strong>
            <p><?= e((string) ($translation['source_excerpt'] ?? '')) ?></p>
            <div class="admin-translation-source-content"><?= nl2br(e(trim(strip_tags((string) ($translation['source_content'] ?? ''))))) ?></div>
          </aside>
          <form method="post" class="stack" data-admin-dirty-track>
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="review_news_translation">
            <input type="hidden" name="id" value="<?= (int) $translation['id'] ?>">
            <input type="hidden" name="locale" value="<?= e((string) $translation['locale']) ?>">
            <input type="hidden" name="return_locale" value="<?= e($reviewLocale) ?>">
            <input type="hidden" name="return_type" value="<?= e($reviewType) ?>">
            <label><?= e((string) $tr('label_title')) ?><input type="text" name="title" value="<?= e((string) $translation['title']) ?>"></label>
            <label><?= e((string) $tr('label_excerpt')) ?><textarea name="excerpt" rows="3"><?= e((string) $translation['excerpt']) ?></textarea></label>
            <label><?= e((string) $tr('label_content')) ?><textarea name="content" rows="8"><?= e((string) $translation['content']) ?></textarea></label>
            <button class="button"><?= e((string) $tr('submit')) ?></button>
          </form>
          </div>
        </article>
      <?php endforeach; ?>
      <?php if ($newsTranslations === []): ?><p><?= e((string) $tr('no_news')) ?></p><?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($reviewType !== 'news'): ?>
    <section class="card">
      <h2><?= e((string) $tr('article_title')) ?></h2>
      <?php foreach ($articleTranslations as $translation): ?>
        <article class="card inner-card admin-translation-review-card">
          <h3><?= e((string) $translation['source_title']) ?> — <?= strtoupper(e((string) $translation['locale'])) ?></h3>
          <div class="admin-translation-compare">
          <aside class="admin-translation-source" aria-label="FR">
            <h4><?= e($filterLabels['source']) ?> — FR</h4>
            <strong><?= e((string) $translation['source_title']) ?></strong>
            <p><?= e((string) ($translation['source_excerpt'] ?? '')) ?></p>
            <div class="admin-translation-source-content"><?= nl2br(e(trim(strip_tags((string) ($translation['source_content'] ?? ''))))) ?></div>
          </aside>
          <form method="post" class="stack" data-admin-dirty-track>
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="review_article_translation">
            <input type="hidden" name="id" value="<?= (int) $translation['id'] ?>">
            <input type="hidden" name="locale" value="<?= e((string) $translation['locale']) ?>">
            <input type="hidden" name="return_locale" value="<?= e($reviewLocale) ?>">
            <input type="hidden" name="return_type" value="<?= e($reviewType) ?>">
            <label><?= e((string) $tr('label_title')) ?><input type="text" name="title" value="<?= e((string) $translation['title']) ?>"></label>
            <label><?= e((string) $tr('label_excerpt')) ?><textarea name="excerpt" rows="3"><?= e((string) $translation['excerpt']) ?></textarea></label>
            <label><?= e((string) $tr('label_content')) ?><textarea name="content" rows="8"><?= e((string) $translation['content']) ?></textarea></label>
            <button class="button"><?= e((string) $tr('submit')) ?></button>
          </form>
          </div>
        </article>
      <?php endforeach; ?>
      <?php if ($articleTranslations === []): ?><p><?= e((string) $tr('no_article')) ?></p><?php endif; ?>
    </section>
    <?php endif; ?>
  </div>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $tr('layout'));
