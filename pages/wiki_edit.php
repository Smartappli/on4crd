<?php
declare(strict_types=1);

require_permission('wiki.edit');

$locale = current_locale();
$i18n = [
    'fr' => [
        'saved' => 'Page wiki enregistrée.',
        'edit' => 'Modifier',
        'create' => 'Créer',
        'heading_suffix' => 'une page wiki',
        'title_label' => 'Titre',
        'slug_label' => 'Slug',
        'content_label' => 'Contenu (HTML simple)',
        'save' => 'Enregistrer',
        'layout' => 'Éditer wiki','meta_desc' => 'Créer ou modifier une page wiki.',
    ],
    'en' => [
        'saved' => 'Wiki page saved.',
        'edit' => 'Edit',
        'create' => 'Create',
        'heading_suffix' => 'a wiki page',
        'title_label' => 'Title',
        'slug_label' => 'Slug',
        'content_label' => 'Content (simple HTML)',
        'save' => 'Save',
        'layout' => 'Edit wiki','meta_desc' => 'Create or edit a wiki page.',
    ],
    'de' => [
        'saved' => 'Wiki-Seite gespeichert.',
        'edit' => 'Bearbeiten',
        'create' => 'Erstellen',
        'heading_suffix' => 'eine Wiki-Seite',
        'title_label' => 'Titel',
        'slug_label' => 'Slug',
        'content_label' => 'Inhalt (einfaches HTML)',
        'save' => 'Speichern',
        'layout' => 'Wiki bearbeiten','meta_desc' => 'Eine Wiki-Seite erstellen oder bearbeiten.',
    ],
    'nl' => [
        'saved' => 'Wiki-pagina opgeslagen.',
        'edit' => 'Bewerken',
        'create' => 'Aanmaken',
        'heading_suffix' => 'een wiki-pagina',
        'title_label' => 'Titel',
        'slug_label' => 'Slug',
        'content_label' => 'Inhoud (eenvoudige HTML)',
        'save' => 'Opslaan',
        'layout' => 'Wiki bewerken','meta_desc' => 'Een wiki-pagina maken of bewerken.',
    ],
    'es' => [
        'saved' => 'Página wiki guardada.',
        'edit' => 'Editar',
        'create' => 'Crear',
        'heading_suffix' => 'una página wiki',
        'title_label' => 'Título',
        'slug_label' => 'Slug',
        'content_label' => 'Contenido (HTML simple)',
        'save' => 'Guardar',
        'layout' => 'Editar wiki','meta_desc' => 'Crear o editar una página wiki.',
    ],
    'it' => [
        'saved' => 'Pagina wiki salvata.',
        'edit' => 'Modifica',
        'create' => 'Crea',
        'heading_suffix' => 'una pagina wiki',
        'title_label' => 'Titolo',
        'slug_label' => 'Slug',
        'content_label' => 'Contenuto (HTML semplice)',
        'save' => 'Salva',
        'layout' => 'Modifica wiki','meta_desc' => 'Crea o modifica una pagina wiki.',
    ],
    'pt' => [
        'saved' => 'Página wiki guardada.',
        'edit' => 'Editar',
        'create' => 'Criar',
        'heading_suffix' => 'uma página wiki',
        'title_label' => 'Título',
        'slug_label' => 'Slug',
        'content_label' => 'Conteúdo (HTML simples)',
        'save' => 'Guardar',
        'layout' => 'Editar wiki','meta_desc' => 'Criar ou editar uma página wiki.',
    ],
    'ar' => [
        'saved' => 'تم حفظ صفحة الويكي.',
        'edit' => 'تعديل',
        'create' => 'إنشاء',
        'heading_suffix' => 'صفحة ويكي',
        'title_label' => 'العنوان',
        'slug_label' => 'Slug',
        'content_label' => 'المحتوى (HTML بسيط)',
        'save' => 'حفظ',
        'layout' => 'تحرير الويكي', 'meta_desc' => 'إنشاء أو تعديل صفحة ويكي.',
    ],
    'hi' => [
        'saved' => 'विकी पृष्ठ सहेजा गया।',
        'edit' => 'संपादित करें',
        'create' => 'बनाएँ',
        'heading_suffix' => 'एक विकी पृष्ठ',
        'title_label' => 'शीर्षक',
        'slug_label' => 'Slug',
        'content_label' => 'सामग्री (सरल HTML)',
        'save' => 'सहेजें',
        'layout' => 'विकी संपादित करें', 'meta_desc' => 'विकी पृष्ठ बनाएँ या संपादित करें।',
    ],
    'ja' => [
        'saved' => 'Wikiページを保存しました。',
        'edit' => '編集',
        'create' => '作成',
        'heading_suffix' => 'Wikiページ',
        'title_label' => 'タイトル',
        'slug_label' => 'Slug',
        'content_label' => '内容（シンプルHTML）',
        'save' => '保存',
        'layout' => 'Wikiを編集', 'meta_desc' => 'Wikiページを作成または編集します。',
    ],
    'zh' => [
        'saved' => 'Wiki 页面已保存。',
        'edit' => '编辑',
        'create' => '创建',
        'heading_suffix' => '一个 Wiki 页面',
        'title_label' => '标题',
        'slug_label' => 'Slug',
        'content_label' => '内容（简单 HTML）',
        'save' => '保存',
        'layout' => '编辑 Wiki', 'meta_desc' => '创建或编辑 Wiki 页面。',
    ],
    'bn' => [
        'saved' => 'উইকি পৃষ্ঠা সংরক্ষণ করা হয়েছে।',
        'edit' => 'সম্পাদনা',
        'create' => 'তৈরি করুন',
        'heading_suffix' => 'একটি উইকি পৃষ্ঠা',
        'title_label' => 'শিরোনাম',
        'slug_label' => 'Slug',
        'content_label' => 'বিষয়বস্তু (সহজ HTML)',
        'save' => 'সংরক্ষণ করুন',
        'layout' => 'উইকি সম্পাদনা', 'meta_desc' => 'উইকি পৃষ্ঠা তৈরি বা সম্পাদনা করুন।',
    ],
    'ru' => [
        'saved' => 'Страница wiki сохранена.',
        'edit' => 'Редактировать',
        'create' => 'Создать',
        'heading_suffix' => 'страницу wiki',
        'title_label' => 'Заголовок',
        'slug_label' => 'Slug',
        'content_label' => 'Содержимое (простой HTML)',
        'save' => 'Сохранить',
        'layout' => 'Редактирование wiki', 'meta_desc' => 'Создать или редактировать страницу wiki.',
    ],
    'id' => [
        'saved' => 'Halaman wiki disimpan.',
        'edit' => 'Ubah',
        'create' => 'Buat',
        'heading_suffix' => 'sebuah halaman wiki',
        'title_label' => 'Judul',
        'slug_label' => 'Slug',
        'content_label' => 'Konten (HTML sederhana)',
        'save' => 'Simpan',
        'layout' => 'Edit wiki', 'meta_desc' => 'Buat atau edit halaman wiki.',
    ],
];
$i18n = i18n_expand_supported_locales($i18n);
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};


set_page_meta([
    'title' => $t('layout'),
    'description' => $t('meta_desc'),
    'robots' => 'noindex,nofollow',
]);

$user = require_login();
$id = (int) ($_GET['id'] ?? 0);
$page = ['id' => 0, 'title' => '', 'slug' => '', 'content' => '<p></p>'];

if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM wiki_pages WHERE id = ?');
    $stmt->execute([$id]);
    $page = $stmt->fetch() ?: $page;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $title = trim((string) ($_POST['title'] ?? ''));
        $content = sanitize_rich_html((string) ($_POST['content'] ?? ''));
        $slug = slugify((string) ($_POST['slug'] ?? $title));

        if ($id > 0) {
            db()->prepare('INSERT INTO wiki_revisions (wiki_page_id, member_id, content) VALUES (?, ?, ?)')->execute([$id, (int) $user['id'], (string) $page['content']]);
            db()->prepare('UPDATE wiki_pages SET title = ?, slug = ?, content = ?, author_id = ? WHERE id = ?')->execute([$title, $slug, $content, (int) $user['id'], $id]);
        } else {
            db()->prepare('INSERT INTO wiki_pages (title, slug, content, author_id) VALUES (?, ?, ?, ?)')->execute([$title, $slug, $content, (int) $user['id']]);
        }

        set_flash('success', $t('saved'));
        redirect('wiki');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect($id > 0 ? 'wiki_edit&id=' . $id : 'wiki_edit');
    }
}

ob_start();
?>
<div class="card">
    <h1><?= e(($id > 0 ? $t('edit') : $t('create')) . ' ' . $t('heading_suffix')) ?></h1>
    <form method="post">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <label><?= e($t('title_label')) ?><input type="text" name="title" value="<?= e((string) $page['title']) ?>" required></label>
        <label><?= e($t('slug_label')) ?><input type="text" name="slug" value="<?= e((string) $page['slug']) ?>"></label>
        <label><?= e($t('content_label')) ?>
            <textarea name="content" rows="18"><?= e((string) $page['content']) ?></textarea>
        </label>
        <p><button class="button"><?= e($t('save')) ?></button></p>
    </form>
</div>
<?php
echo render_layout((string) ob_get_clean(), $t('layout'));
