<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['title' => 'Wiki', 'unavailable' => 'Le wiki sera disponible après initialisation des pages.', 'new_pages' => 'Nouvelles pages', 'updated_pages' => 'Pages modifiées', 'most_read' => 'Les plus lues', 'new_page' => 'Nouvelle page', 'search_placeholder' => 'Rechercher une page (titre ou contenu)', 'search' => 'Rechercher', 'reset' => 'Réinitialiser', 'wiki_pages' => 'Pages du wiki', 'no_page' => 'Aucune page trouvée', 'for_search' => ' pour cette recherche', 'summary_fallback' => 'Consulter cette page pour accéder au contenu complet.', 'updated_at' => 'Mise à jour :', 'open_page' => 'Ouvrir la page'],
    'en' => ['title' => 'Wiki', 'unavailable' => 'The wiki will be available after page initialization.', 'new_pages' => 'New pages', 'updated_pages' => 'Updated pages', 'most_read' => 'Most read', 'new_page' => 'New page', 'search_placeholder' => 'Search for a page (title or content)', 'search' => 'Search', 'reset' => 'Reset', 'wiki_pages' => 'Wiki pages', 'no_page' => 'No page found', 'for_search' => ' for this search', 'summary_fallback' => 'Open this page to access the full content.', 'updated_at' => 'Updated:', 'open_page' => 'Open page'],
    'de' => ['title' => 'Wiki', 'unavailable' => 'Das Wiki ist nach der Initialisierung der Seiten verfügbar.', 'new_pages' => 'Neue Seiten', 'updated_pages' => 'Aktualisierte Seiten', 'most_read' => 'Am meisten gelesen', 'new_page' => 'Neue Seite', 'search_placeholder' => 'Seite suchen (Titel oder Inhalt)', 'search' => 'Suchen', 'reset' => 'Zurücksetzen', 'wiki_pages' => 'Wiki-Seiten', 'no_page' => 'Keine Seite gefunden', 'for_search' => ' für diese Suche', 'summary_fallback' => 'Öffnen Sie diese Seite, um den vollständigen Inhalt zu sehen.', 'updated_at' => 'Aktualisiert:', 'open_page' => 'Seite öffnen'],
    'es' => ['title' => 'Wiki', 'unavailable' => 'El wiki estará disponible tras la inicialización de páginas.', 'new_pages' => 'Nuevas páginas', 'updated_pages' => 'Páginas actualizadas', 'most_read' => 'Más leídas', 'new_page' => 'Nueva página', 'search_placeholder' => 'Buscar una página (título o contenido)', 'search' => 'Buscar', 'reset' => 'Restablecer', 'wiki_pages' => 'Páginas wiki', 'no_page' => 'No se encontró ninguna página', 'for_search' => ' para esta búsqueda', 'summary_fallback' => 'Abra esta página para acceder al contenido completo.', 'updated_at' => 'Actualizado:', 'open_page' => 'Abrir página'],
    'it' => ['title' => 'Wiki', 'unavailable' => 'Il wiki sarà disponibile dopo l\'inizializzazione delle pagine.', 'new_pages' => 'Nuove pagine', 'updated_pages' => 'Pagine aggiornate', 'most_read' => 'Più lette', 'new_page' => 'Nuova pagina', 'search_placeholder' => 'Cerca una pagina (titolo o contenuto)', 'search' => 'Cerca', 'reset' => 'Reimposta', 'wiki_pages' => 'Pagine wiki', 'no_page' => 'Nessuna pagina trovata', 'for_search' => ' per questa ricerca', 'summary_fallback' => 'Apri questa pagina per vedere il contenuto completo.', 'updated_at' => 'Aggiornato:', 'open_page' => 'Apri pagina'],
    'pt' => ['title' => 'Wiki', 'unavailable' => 'A wiki estará disponível após a inicialização das páginas.', 'new_pages' => 'Novas páginas', 'updated_pages' => 'Páginas atualizadas', 'most_read' => 'Mais lidas', 'new_page' => 'Nova página', 'search_placeholder' => 'Pesquisar uma página (título ou conteúdo)', 'search' => 'Pesquisar', 'reset' => 'Repor', 'wiki_pages' => 'Páginas wiki', 'no_page' => 'Nenhuma página encontrada', 'for_search' => ' para esta pesquisa', 'summary_fallback' => 'Abra esta página para aceder ao conteúdo completo.', 'updated_at' => 'Atualizado:', 'open_page' => 'Abrir página'],
    'nl' => ['title' => 'Wiki', 'unavailable' => "De wiki is beschikbaar na initialisatie van de pagina's.", 'new_pages' => "Nieuwe pagina's", 'updated_pages' => "Bijgewerkte pagina's", 'most_read' => 'Meest gelezen', 'new_page' => 'Nieuwe pagina', 'search_placeholder' => 'Zoek een pagina (titel of inhoud)', 'search' => 'Zoeken', 'reset' => 'Reset', 'wiki_pages' => "Wiki-pagina's", 'no_page' => 'Geen pagina gevonden', 'for_search' => ' voor deze zoekopdracht', 'summary_fallback' => 'Open deze pagina om de volledige inhoud te bekijken.', 'updated_at' => 'Bijgewerkt:', 'open_page' => 'Pagina openen'],

    'ar' => ['title' => 'ويكي', 'unavailable' => 'سيكون الويكي متاحًا بعد تهيئة الصفحات.', 'new_pages' => 'صفحات جديدة', 'updated_pages' => 'صفحات محدَّثة', 'most_read' => 'الأكثر قراءة', 'new_page' => 'صفحة جديدة', 'search_placeholder' => 'ابحث عن صفحة (العنوان أو المحتوى)', 'search' => 'بحث', 'reset' => 'إعادة تعيين', 'wiki_pages' => 'صفحات الويكي', 'no_page' => 'لم يتم العثور على صفحة', 'for_search' => ' لهذا البحث', 'summary_fallback' => 'افتح هذه الصفحة للوصول إلى المحتوى الكامل.', 'updated_at' => 'آخر تحديث:', 'open_page' => 'فتح الصفحة'],
    'bn' => ['title' => 'উইকি', 'unavailable' => 'পৃষ্ঠা ইনিশিয়ালাইজেশন শেষ হলে উইকি উপলব্ধ হবে।', 'new_pages' => 'নতুন পৃষ্ঠা', 'updated_pages' => 'হালনাগাদ পৃষ্ঠা', 'most_read' => 'সবচেয়ে বেশি পড়া', 'new_page' => 'নতুন পৃষ্ঠা', 'search_placeholder' => 'একটি পৃষ্ঠা খুঁজুন (শিরোনাম বা বিষয়বস্তু)', 'search' => 'খুঁজুন', 'reset' => 'রিসেট', 'wiki_pages' => 'উইকি পৃষ্ঠা', 'no_page' => 'কোনো পৃষ্ঠা পাওয়া যায়নি', 'for_search' => ' এই অনুসন্ধানের জন্য', 'summary_fallback' => 'পূর্ণ বিষয়বস্তু দেখতে এই পৃষ্ঠাটি খুলুন।', 'updated_at' => 'হালনাগাদ:', 'open_page' => 'পৃষ্ঠা খুলুন'],
    'hi' => ['title' => 'विकी', 'unavailable' => 'पृष्ठ प्रारंभ होने के बाद विकी उपलब्ध होगा।', 'new_pages' => 'नई पृष्ठ', 'updated_pages' => 'अपडेट किए गए पृष्ठ', 'most_read' => 'सबसे अधिक पढ़े गए', 'new_page' => 'नया पृष्ठ', 'search_placeholder' => 'एक पृष्ठ खोजें (शीर्षक या सामग्री)', 'search' => 'खोजें', 'reset' => 'रीसेट', 'wiki_pages' => 'विकी पृष्ठ', 'no_page' => 'कोई पृष्ठ नहीं मिला', 'for_search' => ' इस खोज के लिए', 'summary_fallback' => 'पूर्ण सामग्री देखने के लिए यह पृष्ठ खोलें।', 'updated_at' => 'अपडेट:', 'open_page' => 'पृष्ठ खोलें'],
    'id' => ['title' => 'Wiki', 'unavailable' => 'Wiki akan tersedia setelah inisialisasi halaman.', 'new_pages' => 'Halaman baru', 'updated_pages' => 'Halaman diperbarui', 'most_read' => 'Paling banyak dibaca', 'new_page' => 'Halaman baru', 'search_placeholder' => 'Cari halaman (judul atau konten)', 'search' => 'Cari', 'reset' => 'Setel ulang', 'wiki_pages' => 'Halaman wiki', 'no_page' => 'Tidak ada halaman ditemukan', 'for_search' => ' untuk pencarian ini', 'summary_fallback' => 'Buka halaman ini untuk melihat konten lengkap.', 'updated_at' => 'Diperbarui:', 'open_page' => 'Buka halaman'],
    'ja' => ['title' => 'Wiki', 'unavailable' => 'ページ初期化後にWikiが利用可能になります。', 'new_pages' => '新しいページ', 'updated_pages' => '更新されたページ', 'most_read' => 'よく読まれているページ', 'new_page' => '新規ページ', 'search_placeholder' => 'ページを検索（タイトルまたは内容）', 'search' => '検索', 'reset' => 'リセット', 'wiki_pages' => 'Wikiページ', 'no_page' => 'ページが見つかりません', 'for_search' => ' この検索に対して', 'summary_fallback' => '完全な内容を見るにはこのページを開いてください。', 'updated_at' => '更新:', 'open_page' => 'ページを開く'],
    'ru' => ['title' => 'Вики', 'unavailable' => 'Вики будет доступна после инициализации страниц.', 'new_pages' => 'Новые страницы', 'updated_pages' => 'Обновлённые страницы', 'most_read' => 'Самые читаемые', 'new_page' => 'Новая страница', 'search_placeholder' => 'Поиск страницы (заголовок или содержание)', 'search' => 'Поиск', 'reset' => 'Сбросить', 'wiki_pages' => 'Страницы вики', 'no_page' => 'Страница не найдена', 'for_search' => ' для этого поиска', 'summary_fallback' => 'Откройте эту страницу, чтобы просмотреть полный контент.', 'updated_at' => 'Обновлено:', 'open_page' => 'Открыть страницу'],
    'zh' => ['title' => '维基', 'unavailable' => '页面初始化后维基将可用。', 'new_pages' => '新页面', 'updated_pages' => '已更新页面', 'most_read' => '阅读最多', 'new_page' => '新建页面', 'search_placeholder' => '搜索页面（标题或内容）', 'search' => '搜索', 'reset' => '重置', 'wiki_pages' => '维基页面', 'no_page' => '未找到页面', 'for_search' => ' 与此搜索相关', 'summary_fallback' => '打开此页面以查看完整内容。', 'updated_at' => '更新于：', 'open_page' => '打开页面'],
];
$i18n = i18n_expand_supported_locales($i18n);
$t = $i18n[$locale] ?? $i18n['fr'];

if (!table_exists('wiki_pages')) {
    echo render_layout('<div class="card"><h1>' . e((string) $t['title']) . '</h1><p>' . e((string) $t['unavailable']) . '</p></div>', (string) $t['title']);
    return;
}

$search = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($search) > 120) {
    $search = mb_substr($search, 0, 120);
}

$rows = [];
try {
    if ($search === '') {
        $stmt = db()->query('SELECT slug, title, content, updated_at FROM wiki_pages ORDER BY updated_at DESC LIMIT 120');
        $rows = $stmt->fetchAll() ?: [];
    } else {
        $stmt = db()->prepare('SELECT slug, title, content, updated_at FROM wiki_pages WHERE title LIKE ? OR content LIKE ? ORDER BY updated_at DESC LIMIT 120');
        $like = '%' . $search . '%';
        $stmt->execute([$like, $like]);
        $rows = $stmt->fetchAll() ?: [];
    }
} catch (Throwable) {
    $rows = [];
}

ob_start();
?>
<div class="stack">
    <section class="card wiki-header">
        <div class="stats-grid">
            <article class="stat-card">
                <span class="stat-card-label"><?= e((string) $t['new_pages']) ?></span>
            </article>
            <article class="stat-card">
                <span class="stat-card-label"><?= e((string) $t['updated_pages']) ?></span>
            </article>
            <article class="stat-card">
                <span class="stat-card-label"><?= e((string) $t['most_read']) ?></span>
            </article>
        </div>
        <form method="get" class="inline-form">
            <input type="hidden" name="route" value="wiki">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $t['search_placeholder']) ?>">
            <button class="button" type="submit"><?= e((string) $t['search']) ?></button>
            <?php if ($search !== ''): ?>
                <a class="button secondary" href="<?= e(route_url('wiki')) ?>"><?= e((string) $t['reset']) ?></a>
            <?php endif; ?>
        </form>
    </section>

    <section class="card">
        <h2 class="wiki-section-title"><?= e((string) $t['wiki_pages']) ?></h2>
        <?php if ($rows === []): ?>
            <p><?= e((string) $t['no_page']) ?><?= $search !== '' ? e((string) $t['for_search']) : '' ?>.</p>
        <?php else: ?>
            <div class="wiki-grid">
                <?php foreach ($rows as $row):
                    $summary = trim(strip_tags((string) ($row['content'] ?? '')));
                    if ($summary === '') {
                        $summary = (string) $t['summary_fallback'];
                    }
                    if (mb_strlen($summary) > 190) {
                        $summary = mb_substr($summary, 0, 187) . '…';
                    }
                    ?>
                    <article class="wiki-card">
                        <h3><a href="<?= e(base_url('index.php?route=wiki_view&slug=' . urlencode((string) $row['slug']))) ?>"><?= e((string) $row['title']) ?></a></h3>
                        <p class="help"><?= e((string) $t['updated_at']) ?> <?= e(date('d/m/Y H:i', strtotime((string) $row['updated_at']))) ?></p>
                        <p><?= e($summary) ?></p>
                        <p><a class="button secondary" href="<?= e(base_url('index.php?route=wiki_view&slug=' . urlencode((string) $row['slug']))) ?>"><?= e((string) $t['open_page']) ?></a></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['title']);
