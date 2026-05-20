<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['not_found' => 'Actualité introuvable', 'not_found_msg' => "Cette actualité n'existe pas ou n'est pas publiée.", 'back' => '← Retour aux actualités', 'published_on' => 'Publié le', 'date_unknown' => 'Date non définie', 'content_soon' => 'Le contenu détaillé sera ajouté prochainement.'],
    'en' => ['not_found' => 'News not found', 'not_found_msg' => 'This news item does not exist or is not published.', 'back' => '← Back to news', 'published_on' => 'Published on', 'date_unknown' => 'Date not set', 'content_soon' => 'Detailed content will be added soon.'],
    'de' => ['not_found' => 'Nachricht nicht gefunden', 'not_found_msg' => 'Diese Nachricht existiert nicht oder ist nicht veröffentlicht.', 'back' => '← Zurück zu den Nachrichten', 'published_on' => 'Veröffentlicht am', 'date_unknown' => 'Datum nicht festgelegt', 'content_soon' => 'Detaillierter Inhalt wird in Kürze hinzugefügt.'],
    'es' => ['not_found' => 'Noticia no encontrada', 'not_found_msg' => 'Esta noticia no existe o no está publicada.', 'back' => '← Volver a noticias', 'published_on' => 'Publicado el', 'date_unknown' => 'Fecha no definida', 'content_soon' => 'El contenido detallado se añadirá pronto.'],
    'it' => ['not_found' => 'Notizia non trovata', 'not_found_msg' => 'Questa notizia non esiste o non è pubblicata.', 'back' => '← Torna alle notizie', 'published_on' => 'Pubblicato il', 'date_unknown' => 'Data non definita', 'content_soon' => 'Il contenuto dettagliato sarà aggiunto presto.'],
    'pt' => ['not_found' => 'Notícia não encontrada', 'not_found_msg' => 'Esta notícia não existe ou não está publicada.', 'back' => '← Voltar às notícias', 'published_on' => 'Publicado em', 'date_unknown' => 'Data não definida', 'content_soon' => 'O conteúdo detalhado será adicionado em breve.'],
    'nl' => ['not_found' => 'Nieuws niet gevonden', 'not_found_msg' => 'Dit nieuwsbericht bestaat niet of is niet gepubliceerd.', 'back' => '← Terug naar nieuws', 'published_on' => 'Gepubliceerd op', 'date_unknown' => 'Datum niet ingesteld', 'content_soon' => 'Gedetailleerde inhoud wordt binnenkort toegevoegd.'],

    'ar' => ['not_found' => 'الخبر غير موجود', 'not_found_msg' => 'هذا الخبر غير موجود أو غير منشور.', 'back' => '← العودة إلى الأخبار', 'published_on' => 'نُشر في', 'date_unknown' => 'تاريخ غير محدد', 'content_soon' => 'سيتم إضافة المحتوى التفصيلي قريبًا.'],
    'bn' => ['not_found' => 'সংবাদ পাওয়া যায়নি', 'not_found_msg' => 'এই সংবাদটি নেই বা প্রকাশিত নয়।', 'back' => '← সংবাদে ফিরে যান', 'published_on' => 'প্রকাশিত', 'date_unknown' => 'তারিখ নির্ধারিত নয়', 'content_soon' => 'বিস্তারিত বিষয়বস্তু শিগগিরই যোগ করা হবে।'],
    'hi' => ['not_found' => 'समाचार नहीं मिला', 'not_found_msg' => 'यह समाचार मौजूद नहीं है या प्रकाशित नहीं है।', 'back' => '← समाचार पर वापस जाएँ', 'published_on' => 'प्रकाशित', 'date_unknown' => 'तारीख निर्धारित नहीं', 'content_soon' => 'विस्तृत सामग्री जल्द जोड़ी जाएगी।'],
    'id' => ['not_found' => 'Berita tidak ditemukan', 'not_found_msg' => 'Berita ini tidak ada atau belum dipublikasikan.', 'back' => '← Kembali ke berita', 'published_on' => 'Dipublikasikan pada', 'date_unknown' => 'Tanggal belum ditetapkan', 'content_soon' => 'Konten rinci akan segera ditambahkan.'],
    'ja' => ['not_found' => 'ニュースが見つかりません', 'not_found_msg' => 'このニュースは存在しないか、公開されていません。', 'back' => '← ニュース一覧へ戻る', 'published_on' => '公開日', 'date_unknown' => '日付未設定', 'content_soon' => '詳細な内容は近日中に追加されます。'],
    'ru' => ['not_found' => 'Новость не найдена', 'not_found_msg' => 'Эта новость не существует или не опубликована.', 'back' => '← Назад к новостям', 'published_on' => 'Опубликовано', 'date_unknown' => 'Дата не указана', 'content_soon' => 'Подробное содержание будет добавлено в ближайшее время.'],
    'zh' => ['not_found' => '未找到新闻', 'not_found_msg' => '该新闻不存在或未发布。', 'back' => '← 返回新闻', 'published_on' => '发布于', 'date_unknown' => '日期未设置', 'content_soon' => '详细内容将很快添加。'],
];
$i18n = i18n_expand_supported_locales($i18n);
$t = [];
foreach (array_keys($i18n['fr']) as $key) {
    $pool = [];
    foreach ($i18n as $lang => $translations) {
        if (isset($translations[$key]) && is_string($translations[$key])) {
            $pool[$lang] = $translations[$key];
        }
    }
    $t[$key] = i18n_localized_value($pool, $locale, 'fr');
}

$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '' || !table_exists('news_posts')) {
    http_response_code(404);
    echo render_layout('<div class="card"><h1>' . e((string) $t['not_found']) . '</h1><p>' . e((string) $t['not_found_msg']) . '</p></div>', (string) $t['not_found']);
    return;
}

$stmt = db()->prepare('SELECT title, excerpt, content, published_at, updated_at FROM news_posts WHERE slug = ? AND status = "published" LIMIT 1');
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!is_array($post)) {
    http_response_code(404);
    echo render_layout('<div class="card"><h1>' . e((string) $t['not_found']) . '</h1><p>' . e((string) $t['not_found_msg']) . '</p></div>', (string) $t['not_found']);
    return;
}

$publishedAtRaw = (string) ($post['published_at'] ?? $post['updated_at'] ?? '');
$publishedAt = $publishedAtRaw !== '' ? date('d/m/Y H:i', strtotime($publishedAtRaw)) : (string) $t['date_unknown'];
$excerpt = trim((string) ($post['excerpt'] ?? ''));
$content = trim((string) ($post['content'] ?? ''));

ob_start();
?>
<article class="card">
    <p><a href="<?= e(route_url('news')) ?>"><?= e((string) $t['back']) ?></a></p>
    <h1><?= e((string) $post['title']) ?></h1>
    <p class="help"><?= e((string) $t['published_on']) ?> <?= e($publishedAt) ?></p>

    <?php if ($excerpt !== ''): ?>
        <p><strong><?= e($excerpt) ?></strong></p>
    <?php endif; ?>

    <?php if ($content !== ''): ?>
        <section class="inner-card">
            <?= $content ?>
        </section>
    <?php else: ?>
        <p><?= e((string) $t['content_soon']) ?></p>
    <?php endif; ?>
</article>
<?php
echo render_layout((string) ob_get_clean(), (string) $post['title']);
