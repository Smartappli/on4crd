<?php
declare(strict_types=1);

require_permission('admin.access');
$i18n = [
    'fr' => ['invalid_lang' => 'Langue invalide.', 'ok_news' => 'Traduction d’actualité relue.', 'ok_article' => 'Traduction d’article relue.', 'news_title' => 'Relecture des actualités', 'article_title' => 'Relecture des articles', 'label_title' => 'Titre', 'label_excerpt' => 'Extrait', 'label_content' => 'Contenu', 'submit' => 'Valider la traduction', 'no_news' => 'Aucune traduction d’actualité en attente.', 'no_article' => 'Aucune traduction d’article en attente.', 'layout' => 'Relecture linguistique', 'meta_desc' => 'Validation des traductions automatiques des contenus.'],
    'en' => ['invalid_lang' => 'Invalid language.', 'ok_news' => 'News translation reviewed.', 'ok_article' => 'Article translation reviewed.', 'news_title' => 'News review', 'article_title' => 'Article review', 'label_title' => 'Title', 'label_excerpt' => 'Excerpt', 'label_content' => 'Content', 'submit' => 'Approve translation', 'no_news' => 'No pending news translation.', 'no_article' => 'No pending article translation.', 'layout' => 'Translation review', 'meta_desc' => 'Review and validate automated content translations.'],
    'de' => ['invalid_lang' => 'Ungültige Sprache.', 'ok_news' => 'Nachrichtenübersetzung geprüft.', 'ok_article' => 'Artikelübersetzung geprüft.', 'news_title' => 'Nachrichtenprüfung', 'article_title' => 'Artikelprüfung', 'label_title' => 'Titel', 'label_excerpt' => 'Auszug', 'label_content' => 'Inhalt', 'submit' => 'Übersetzung bestätigen', 'no_news' => 'Keine ausstehende Nachrichtenübersetzung.', 'no_article' => 'Keine ausstehende Artikelübersetzung.', 'layout' => 'Sprachprüfung', 'meta_desc' => 'Automatische Inhaltsübersetzungen prüfen und validieren.'],
    'nl' => ['invalid_lang' => 'Ongeldige taal.', 'ok_news' => 'Nieuwsvertaling nagekeken.', 'ok_article' => 'Artikelvertaling nagekeken.', 'news_title' => 'Nieuwscontrole', 'article_title' => 'Artikelcontrole', 'label_title' => 'Titel', 'label_excerpt' => 'Uittreksel', 'label_content' => 'Inhoud', 'submit' => 'Vertaling goedkeuren', 'no_news' => 'Geen wachtende nieuwsvertaling.', 'no_article' => 'Geen wachtende artikelvertaling.', 'layout' => 'Taalcontrole', 'meta_desc' => 'Automatische inhoudsvertalingen nakijken en valideren.'],
    'es' => ['invalid_lang' => 'Idioma no válido.', 'ok_news' => 'Traducción de noticia revisada.', 'ok_article' => 'Traducción de artículo revisada.', 'news_title' => 'Revisión de noticias', 'article_title' => 'Revisión de artículos', 'label_title' => 'Título', 'label_excerpt' => 'Extracto', 'label_content' => 'Contenido', 'submit' => 'Validar traducción', 'no_news' => 'No hay traducciones de noticias pendientes.', 'no_article' => 'No hay traducciones de artículos pendientes.', 'layout' => 'Revisión lingüística', 'meta_desc' => 'Validación de traducciones automáticas de contenidos.'],
    'it' => ['invalid_lang' => 'Lingua non valida.', 'ok_news' => 'Traduzione notizia revisionata.', 'ok_article' => 'Traduzione articolo revisionata.', 'news_title' => 'Revisione notizie', 'article_title' => 'Revisione articoli', 'label_title' => 'Titolo', 'label_excerpt' => 'Estratto', 'label_content' => 'Contenuto', 'submit' => 'Convalida traduzione', 'no_news' => 'Nessuna traduzione notizia in attesa.', 'no_article' => 'Nessuna traduzione articolo in attesa.', 'layout' => 'Revisione linguistica', 'meta_desc' => 'Convalida delle traduzioni automatiche dei contenuti.'],
    'pt' => ['invalid_lang' => 'Idioma inválido.', 'ok_news' => 'Tradução de notícia revista.', 'ok_article' => 'Tradução de artigo revista.', 'news_title' => 'Revisão de notícias', 'article_title' => 'Revisão de artigos', 'label_title' => 'Título', 'label_excerpt' => 'Excerto', 'label_content' => 'Conteúdo', 'submit' => 'Validar tradução', 'no_news' => 'Sem traduções de notícias pendentes.', 'no_article' => 'Sem traduções de artigos pendentes.', 'layout' => 'Revisão linguística', 'meta_desc' => 'Validação de traduções automáticas de conteúdos.'],
    'ar' => ['invalid_lang' => 'لغة غير صالحة.', 'ok_news' => 'تمت مراجعة ترجمة الخبر.', 'ok_article' => 'تمت مراجعة ترجمة المقال.', 'news_title' => 'مراجعة الأخبار', 'article_title' => 'مراجعة المقالات', 'label_title' => 'العنوان', 'label_excerpt' => 'المقتطف', 'label_content' => 'المحتوى', 'submit' => 'اعتماد الترجمة', 'no_news' => 'لا توجد ترجمات أخبار قيد الانتظار.', 'no_article' => 'لا توجد ترجمات مقالات قيد الانتظار.', 'layout' => 'مراجعة الترجمة', 'meta_desc' => 'مراجعة واعتماد الترجمات الآلية للمحتوى.'],
    'hi' => ['invalid_lang' => 'अमान्य भाषा।', 'ok_news' => 'समाचार अनुवाद की समीक्षा हो गई।', 'ok_article' => 'लेख अनुवाद की समीक्षा हो गई।', 'news_title' => 'समाचार समीक्षा', 'article_title' => 'लेख समीक्षा', 'label_title' => 'शीर्षक', 'label_excerpt' => 'सारांश', 'label_content' => 'सामग्री', 'submit' => 'अनुवाद स्वीकृत करें', 'no_news' => 'कोई लंबित समाचार अनुवाद नहीं।', 'no_article' => 'कोई लंबित लेख अनुवाद नहीं।', 'layout' => 'अनुवाद समीक्षा', 'meta_desc' => 'सामग्री के स्वचालित अनुवादों की समीक्षा और सत्यापन।'],
    'ja' => ['invalid_lang' => '無効な言語です。', 'ok_news' => 'ニュース翻訳をレビューしました。', 'ok_article' => '記事翻訳をレビューしました。', 'news_title' => 'ニュースレビュー', 'article_title' => '記事レビュー', 'label_title' => 'タイトル', 'label_excerpt' => '抜粋', 'label_content' => '内容', 'submit' => '翻訳を承認', 'no_news' => '保留中のニュース翻訳はありません。', 'no_article' => '保留中の記事翻訳はありません。', 'layout' => '翻訳レビュー', 'meta_desc' => '自動翻訳されたコンテンツをレビューして承認します。'],
    'zh' => ['invalid_lang' => '无效语言。', 'ok_news' => '新闻翻译已审阅。', 'ok_article' => '文章翻译已审阅。', 'news_title' => '新闻审阅', 'article_title' => '文章审阅', 'label_title' => '标题', 'label_excerpt' => '摘要', 'label_content' => '内容', 'submit' => '确认翻译', 'no_news' => '没有待审阅的新闻翻译。', 'no_article' => '没有待审阅的文章翻译。', 'layout' => '翻译审阅', 'meta_desc' => '审阅并验证自动生成的内容翻译。'],
    'bn' => ['invalid_lang' => 'অবৈধ ভাষা।', 'ok_news' => 'সংবাদের অনুবাদ পর্যালোচনা করা হয়েছে।', 'ok_article' => 'প্রবন্ধের অনুবাদ পর্যালোচনা করা হয়েছে।', 'news_title' => 'সংবাদ পর্যালোচনা', 'article_title' => 'প্রবন্ধ পর্যালোচনা', 'label_title' => 'শিরোনাম', 'label_excerpt' => 'সারাংশ', 'label_content' => 'বিষয়বস্তু', 'submit' => 'অনুবাদ অনুমোদন করুন', 'no_news' => 'কোনো অপেক্ষমাণ সংবাদ অনুবাদ নেই।', 'no_article' => 'কোনো অপেক্ষমাণ প্রবন্ধ অনুবাদ নেই।', 'layout' => 'অনুবাদ পর্যালোচনা', 'meta_desc' => 'স্বয়ংক্রিয় কনটেন্ট অনুবাদ পর্যালোচনা ও যাচাই।'],
    'ru' => ['invalid_lang' => 'Недопустимый язык.', 'ok_news' => 'Перевод новости проверен.', 'ok_article' => 'Перевод статьи проверен.', 'news_title' => 'Проверка новостей', 'article_title' => 'Проверка статей', 'label_title' => 'Заголовок', 'label_excerpt' => 'Краткое описание', 'label_content' => 'Содержание', 'submit' => 'Подтвердить перевод', 'no_news' => 'Нет ожидающих переводов новостей.', 'no_article' => 'Нет ожидающих переводов статей.', 'layout' => 'Проверка переводов', 'meta_desc' => 'Проверка и подтверждение автоматических переводов контента.'],
    'id' => ['invalid_lang' => 'Bahasa tidak valid.', 'ok_news' => 'Terjemahan berita ditinjau.', 'ok_article' => 'Terjemahan artikel ditinjau.', 'news_title' => 'Tinjauan berita', 'article_title' => 'Tinjauan artikel', 'label_title' => 'Judul', 'label_excerpt' => 'Ringkasan', 'label_content' => 'Konten', 'submit' => 'Setujui terjemahan', 'no_news' => 'Tidak ada terjemahan berita yang menunggu.', 'no_article' => 'Tidak ada terjemahan artikel yang menunggu.', 'layout' => 'Tinjauan terjemahan', 'meta_desc' => 'Tinjau dan validasi terjemahan konten otomatis.'],
];
$localeUi = current_locale();
$t = [];
foreach (array_keys($i18n['fr']) as $key) {
    $t[$key] = i18n_localized_value($i18n, $localeUi, (string) $key);
}

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
        if (!in_array($locale, ['en', 'de', 'nl', 'es', 'it', 'pt', 'ar', 'hi', 'ja', 'zh', 'bn', 'ru', 'id'], true)) {
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
