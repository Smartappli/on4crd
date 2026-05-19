<?php
declare(strict_types=1);

$user = require_login();
$locale = current_locale();
$i18n = [
    'fr' => [
        'title' => 'Petites annonces', 'lead' => 'Déposez et consultez les annonces radioamateur du club.', 'new_ad' => 'Déposer une annonce', 'my_ads' => 'Mes annonces', 'all_ads' => 'Annonces récentes', 'save' => 'Enregistrer', 'created_ok' => 'Annonce créée.', 'updated_ok' => 'Annonce mise à jour.', 'status_ok' => 'Statut mis à jour.', 'invalid' => 'Données invalides.', 'missing' => 'Annonce introuvable.', 'title_label' => 'Titre', 'description_label' => 'Description', 'price_label' => 'Prix (€)', 'location_label' => 'Localisation', 'contact_label' => 'Contact', 'category_label' => 'Catégorie', 'status_label' => 'Statut', 'actions' => 'Actions', 'edit' => 'Éditer', 'mark_sold' => 'Marquer vendue', 'reactivate' => 'Réactiver', 'none' => 'Aucune annonce pour le moment.',
    ],
    'en' => [
        'title' => 'Classifieds', 'lead' => 'Post and browse club ham radio classifieds.', 'new_ad' => 'Post an ad', 'my_ads' => 'My ads', 'all_ads' => 'Recent ads', 'save' => 'Save', 'created_ok' => 'Ad created.', 'updated_ok' => 'Ad updated.', 'status_ok' => 'Status updated.', 'invalid' => 'Invalid data.', 'missing' => 'Ad not found.', 'title_label' => 'Title', 'description_label' => 'Description', 'price_label' => 'Price (€)', 'location_label' => 'Location', 'contact_label' => 'Contact', 'category_label' => 'Category', 'status_label' => 'Status', 'actions' => 'Actions', 'edit' => 'Edit', 'mark_sold' => 'Mark sold', 'reactivate' => 'Reactivate', 'none' => 'No classifieds yet.',
    ],
    'de' => ['title' => 'Kleinanzeigen', 'lead' => 'Veröffentlichen und durchsuchen Sie die Funk-Kleinanzeigen des Clubs.', 'new_ad' => 'Anzeige aufgeben', 'my_ads' => 'Meine Anzeigen', 'all_ads' => 'Neueste Anzeigen', 'save' => 'Speichern', 'created_ok' => 'Anzeige erstellt.', 'updated_ok' => 'Anzeige aktualisiert.', 'status_ok' => 'Status aktualisiert.', 'invalid' => 'Ungültige Daten.', 'missing' => 'Anzeige nicht gefunden.', 'title_label' => 'Titel', 'description_label' => 'Beschreibung', 'price_label' => 'Preis (€)', 'location_label' => 'Ort', 'contact_label' => 'Kontakt', 'category_label' => 'Kategorie', 'status_label' => 'Status', 'actions' => 'Aktionen', 'edit' => 'Bearbeiten', 'mark_sold' => 'Als verkauft markieren', 'reactivate' => 'Reaktivieren', 'none' => 'Derzeit keine Anzeigen.'],
    'nl' => ['title' => 'Kleine advertenties', 'lead' => 'Plaats en bekijk de radioamateur-advertenties van de club.', 'new_ad' => 'Advertentie plaatsen', 'my_ads' => 'Mijn advertenties', 'all_ads' => 'Recente advertenties', 'save' => 'Opslaan', 'created_ok' => 'Advertentie aangemaakt.', 'updated_ok' => 'Advertentie bijgewerkt.', 'status_ok' => 'Status bijgewerkt.', 'invalid' => 'Ongeldige gegevens.', 'missing' => 'Advertentie niet gevonden.', 'title_label' => 'Titel', 'description_label' => 'Beschrijving', 'price_label' => 'Prijs (€)', 'location_label' => 'Locatie', 'contact_label' => 'Contact', 'category_label' => 'Categorie', 'status_label' => 'Status', 'actions' => 'Acties', 'edit' => 'Bewerken', 'mark_sold' => 'Markeer als verkocht', 'reactivate' => 'Heractiveren', 'none' => 'Nog geen advertenties.'],
    'es' => ['title' => 'Anuncios', 'lead' => 'Publica y consulta los anuncios de radioaficionados del club.', 'new_ad' => 'Publicar anuncio', 'my_ads' => 'Mis anuncios', 'all_ads' => 'Anuncios recientes', 'save' => 'Guardar', 'created_ok' => 'Anuncio creado.', 'updated_ok' => 'Anuncio actualizado.', 'status_ok' => 'Estado actualizado.', 'invalid' => 'Datos no válidos.', 'missing' => 'Anuncio no encontrado.', 'title_label' => 'Título', 'description_label' => 'Descripción', 'price_label' => 'Precio (€)', 'location_label' => 'Ubicación', 'contact_label' => 'Contacto', 'category_label' => 'Categoría', 'status_label' => 'Estado', 'actions' => 'Acciones', 'edit' => 'Editar', 'mark_sold' => 'Marcar como vendido', 'reactivate' => 'Reactivar', 'none' => 'Aún no hay anuncios.'],
    'it' => ['title' => 'Annunci', 'lead' => 'Pubblica e consulta gli annunci radioamatoriali del club.', 'new_ad' => 'Pubblica annuncio', 'my_ads' => 'I miei annunci', 'all_ads' => 'Annunci recenti', 'save' => 'Salva', 'created_ok' => 'Annuncio creato.', 'updated_ok' => 'Annuncio aggiornato.', 'status_ok' => 'Stato aggiornato.', 'invalid' => 'Dati non validi.', 'missing' => 'Annuncio non trovato.', 'title_label' => 'Titolo', 'description_label' => 'Descrizione', 'price_label' => 'Prezzo (€)', 'location_label' => 'Località', 'contact_label' => 'Contatto', 'category_label' => 'Categoria', 'status_label' => 'Stato', 'actions' => 'Azioni', 'edit' => 'Modifica', 'mark_sold' => 'Segna come venduto', 'reactivate' => 'Riattiva', 'none' => 'Nessun annuncio al momento.'],
    'pt' => ['title' => 'Classificados', 'lead' => 'Publique e consulte os classificados de radioamador do clube.', 'new_ad' => 'Publicar anúncio', 'my_ads' => 'Meus anúncios', 'all_ads' => 'Anúncios recentes', 'save' => 'Guardar', 'created_ok' => 'Anúncio criado.', 'updated_ok' => 'Anúncio atualizado.', 'status_ok' => 'Estado atualizado.', 'invalid' => 'Dados inválidos.', 'missing' => 'Anúncio não encontrado.', 'title_label' => 'Título', 'description_label' => 'Descrição', 'price_label' => 'Preço (€)', 'location_label' => 'Localização', 'contact_label' => 'Contacto', 'category_label' => 'Categoria', 'status_label' => 'Estado', 'actions' => 'Ações', 'edit' => 'Editar', 'mark_sold' => 'Marcar como vendido', 'reactivate' => 'Reativar', 'none' => 'Ainda não há anúncios.'],
    'ar' => ['title' => 'إعلانات مبوبة', 'lead' => 'انشر وتصفح إعلانات هواة الراديو الخاصة بالنادي.', 'new_ad' => 'نشر إعلان', 'my_ads' => 'إعلاناتي', 'all_ads' => 'أحدث الإعلانات', 'save' => 'حفظ', 'created_ok' => 'تم إنشاء الإعلان.', 'updated_ok' => 'تم تحديث الإعلان.', 'status_ok' => 'تم تحديث الحالة.', 'invalid' => 'بيانات غير صالحة.', 'missing' => 'الإعلان غير موجود.', 'title_label' => 'العنوان', 'description_label' => 'الوصف', 'price_label' => 'السعر (€)', 'location_label' => 'الموقع', 'contact_label' => 'جهة الاتصال', 'category_label' => 'الفئة', 'status_label' => 'الحالة', 'actions' => 'إجراءات', 'edit' => 'تعديل', 'mark_sold' => 'تحديد كمباع', 'reactivate' => 'إعادة التفعيل', 'none' => 'لا توجد إعلانات حالياً.'],
    'hi' => ['title' => 'क्लासीफाइड्स', 'lead' => 'क्लब के हैम रेडियो विज्ञापन पोस्ट करें और देखें।', 'new_ad' => 'विज्ञापन पोस्ट करें', 'my_ads' => 'मेरे विज्ञापन', 'all_ads' => 'हाल के विज्ञापन', 'save' => 'सहेजें', 'created_ok' => 'विज्ञापन बनाया गया।', 'updated_ok' => 'विज्ञापन अपडेट किया गया।', 'status_ok' => 'स्थिति अपडेट की गई।', 'invalid' => 'अमान्य डेटा।', 'missing' => 'विज्ञापन नहीं मिला।', 'title_label' => 'शीर्षक', 'description_label' => 'विवरण', 'price_label' => 'कीमत (€)', 'location_label' => 'स्थान', 'contact_label' => 'संपर्क', 'category_label' => 'श्रेणी', 'status_label' => 'स्थिति', 'actions' => 'क्रियाएँ', 'edit' => 'संपादित करें', 'mark_sold' => 'बिक गया चिह्नित करें', 'reactivate' => 'पुनः सक्रिय करें', 'none' => 'अभी कोई विज्ञापन नहीं।'],
    'ja' => ['title' => 'クラシファイド', 'lead' => 'クラブのアマチュア無線広告を投稿・閲覧できます。', 'new_ad' => '広告を投稿', 'my_ads' => '自分の広告', 'all_ads' => '最近の広告', 'save' => '保存', 'created_ok' => '広告を作成しました。', 'updated_ok' => '広告を更新しました。', 'status_ok' => 'ステータスを更新しました。', 'invalid' => '無効なデータです。', 'missing' => '広告が見つかりません。', 'title_label' => 'タイトル', 'description_label' => '説明', 'price_label' => '価格 (€)', 'location_label' => '場所', 'contact_label' => '連絡先', 'category_label' => 'カテゴリ', 'status_label' => 'ステータス', 'actions' => '操作', 'edit' => '編集', 'mark_sold' => '売却済みにする', 'reactivate' => '再有効化', 'none' => '現在広告はありません。'],
    'zh' => ['title' => '分类信息', 'lead' => '发布并浏览俱乐部业余无线电分类广告。', 'new_ad' => '发布广告', 'my_ads' => '我的广告', 'all_ads' => '最新广告', 'save' => '保存', 'created_ok' => '广告已创建。', 'updated_ok' => '广告已更新。', 'status_ok' => '状态已更新。', 'invalid' => '数据无效。', 'missing' => '未找到广告。', 'title_label' => '标题', 'description_label' => '描述', 'price_label' => '价格 (€)', 'location_label' => '地点', 'contact_label' => '联系方式', 'category_label' => '分类', 'status_label' => '状态', 'actions' => '操作', 'edit' => '编辑', 'mark_sold' => '标记为已售', 'reactivate' => '重新启用', 'none' => '暂无广告。'],
    'bn' => ['title' => 'ক্লাসিফায়েড', 'lead' => 'ক্লাবের হ্যাম রেডিও বিজ্ঞাপন পোস্ট ও দেখুন।', 'new_ad' => 'বিজ্ঞাপন দিন', 'my_ads' => 'আমার বিজ্ঞাপন', 'all_ads' => 'সাম্প্রতিক বিজ্ঞাপন', 'save' => 'সংরক্ষণ', 'created_ok' => 'বিজ্ঞাপন তৈরি হয়েছে।', 'updated_ok' => 'বিজ্ঞাপন আপডেট হয়েছে।', 'status_ok' => 'স্ট্যাটাস আপডেট হয়েছে।', 'invalid' => 'অবৈধ তথ্য।', 'missing' => 'বিজ্ঞাপন পাওয়া যায়নি।', 'title_label' => 'শিরোনাম', 'description_label' => 'বর্ণনা', 'price_label' => 'মূল্য (€)', 'location_label' => 'অবস্থান', 'contact_label' => 'যোগাযোগ', 'category_label' => 'বিভাগ', 'status_label' => 'স্ট্যাটাস', 'actions' => 'অ্যাকশন', 'edit' => 'সম্পাদনা', 'mark_sold' => 'বিক্রি হয়েছে চিহ্ন দিন', 'reactivate' => 'পুনরায় সক্রিয়', 'none' => 'এখনও কোনো বিজ্ঞাপন নেই।'],
    'ru' => ['title' => 'Объявления', 'lead' => 'Публикуйте и просматривайте радиолюбительские объявления клуба.', 'new_ad' => 'Разместить объявление', 'my_ads' => 'Мои объявления', 'all_ads' => 'Последние объявления', 'save' => 'Сохранить', 'created_ok' => 'Объявление создано.', 'updated_ok' => 'Объявление обновлено.', 'status_ok' => 'Статус обновлён.', 'invalid' => 'Некорректные данные.', 'missing' => 'Объявление не найдено.', 'title_label' => 'Заголовок', 'description_label' => 'Описание', 'price_label' => 'Цена (€)', 'location_label' => 'Местоположение', 'contact_label' => 'Контакт', 'category_label' => 'Категория', 'status_label' => 'Статус', 'actions' => 'Действия', 'edit' => 'Редактировать', 'mark_sold' => 'Отметить как продано', 'reactivate' => 'Повторно активировать', 'none' => 'Пока нет объявлений。'],
    'id' => ['title' => 'Iklan Baris', 'lead' => 'Posting dan telusuri iklan radio amatir klub.', 'new_ad' => 'Posting iklan', 'my_ads' => 'Iklan saya', 'all_ads' => 'Iklan terbaru', 'save' => 'Simpan', 'created_ok' => 'Iklan dibuat.', 'updated_ok' => 'Iklan diperbarui.', 'status_ok' => 'Status diperbarui.', 'invalid' => 'Data tidak valid.', 'missing' => 'Iklan tidak ditemukan.', 'title_label' => 'Judul', 'description_label' => 'Deskripsi', 'price_label' => 'Harga (€)', 'location_label' => 'Lokasi', 'contact_label' => 'Kontak', 'category_label' => 'Kategori', 'status_label' => 'Status', 'actions' => 'Aksi', 'edit' => 'Edit', 'mark_sold' => 'Tandai terjual', 'reactivate' => 'Aktifkan lagi', 'none' => 'Belum ada iklan.'],
];
$i18n = i18n_expand_supported_locales($i18n);
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $i18n['fr'][$key] ?? $key);
};

if (!module_enabled('classifieds')) {
    echo render_layout('<div class="card"><p>Module disabled.</p></div>', $t('title'));
    return;
}

if (!table_exists('classified_ads')) {
    $message = '<section class="card"><h1>' . e($t('title')) . '</h1><p class="help">Module temporairement indisponible : table <code>classified_ads</code> manquante.</p></section>';
    echo render_layout($message, $t('title'));
    return;
}

$categories = ['gear' => 'Matériel', 'wanted' => 'Recherche', 'service' => 'Service'];
$editing = null;
if (!empty($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM classified_ads WHERE id = ? AND owner_member_id = ? LIMIT 1');
    $stmt->execute([(int) $_GET['edit'], (int) $user['id']]);
    $editing = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? 'save');

        if ($action === 'save') {
            $id = (int) ($_POST['id'] ?? 0);
            $payload = [
                'category_code' => (string) ($_POST['category_code'] ?? 'gear'),
                'title' => trim((string) ($_POST['title'] ?? '')),
                'description' => trim((string) ($_POST['description'] ?? '')),
                'location' => trim((string) ($_POST['location'] ?? '')),
                'contact' => trim((string) ($_POST['contact'] ?? '')),
                'price_cents' => (int) round(((float) str_replace(',', '.', (string) ($_POST['price'] ?? '0'))) * 100),
            ];
            if ($payload['title'] === '' || !isset($categories[$payload['category_code']])) {
                throw new RuntimeException($t('invalid'));
            }

            if ($id > 0) {
                $stmt = db()->prepare('UPDATE classified_ads SET category_code = ?, title = ?, description = ?, location = ?, contact = ?, price_cents = ?, updated_at = NOW() WHERE id = ? AND owner_member_id = ?');
                $stmt->execute([$payload['category_code'], $payload['title'], $payload['description'], $payload['location'], $payload['contact'], max(0, $payload['price_cents']), $id, (int) $user['id']]);
                set_flash('success', $t('updated_ok'));
            } else {
                $stmt = db()->prepare('INSERT INTO classified_ads (owner_member_id, category_code, title, description, location, contact, price_cents) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([(int) $user['id'], $payload['category_code'], $payload['title'], $payload['description'], $payload['location'], $payload['contact'], max(0, $payload['price_cents'])]);
                set_flash('success', $t('created_ok'));
            }
        }

        if ($action === 'set_status') {
            $id = (int) ($_POST['id'] ?? 0);
            $status = (string) ($_POST['status'] ?? 'active');
            if (!in_array($status, ['active', 'sold'], true)) {
                throw new RuntimeException($t('invalid'));
            }
            $stmt = db()->prepare('UPDATE classified_ads SET status = ?, updated_at = NOW() WHERE id = ? AND owner_member_id = ?');
            $stmt->execute([$status, $id, (int) $user['id']]);
            set_flash('success', $t('status_ok'));
        }

        redirect('classifieds');
    } catch (Throwable $exception) {
        set_flash('error', $exception->getMessage());
        redirect('classifieds');
    }
}

$myStmt = db()->prepare('SELECT * FROM classified_ads WHERE owner_member_id = ? ORDER BY created_at DESC');
$myStmt->execute([(int) $user['id']]);
$myAds = $myStmt->fetchAll();

$allAds = db()->query("SELECT ca.*, m.callsign FROM classified_ads ca LEFT JOIN members m ON m.id = ca.owner_member_id WHERE ca.status = 'active' ORDER BY ca.created_at DESC LIMIT 60")->fetchAll();

set_page_meta(['title' => $t('title'), 'description' => $t('lead')]);
ob_start();
?>
<div class="grid-2">
    <section class="card">
        <h1><?= e($editing ? $t('edit') : $t('new_ad')) ?></h1>
        <p class="help"><?= e($t('lead')) ?></p>
        <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
            <label><?= e($t('category_label')) ?>
                <select name="category_code">
                    <?php foreach ($categories as $code => $label): ?>
                    <option value="<?= e($code) ?>" <?= (($editing['category_code'] ?? 'gear') === $code) ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><?= e($t('title_label')) ?><input type="text" name="title" required value="<?= e((string) ($editing['title'] ?? '')) ?>"></label>
            <label><?= e($t('description_label')) ?><textarea name="description" rows="5"><?= e((string) ($editing['description'] ?? '')) ?></textarea></label>
            <label><?= e($t('price_label')) ?><input type="text" name="price" value="<?= e(number_format(((int) ($editing['price_cents'] ?? 0)) / 100, 2, '.', '')) ?>"></label>
            <label><?= e($t('location_label')) ?><input type="text" name="location" value="<?= e((string) ($editing['location'] ?? '')) ?>"></label>
            <label><?= e($t('contact_label')) ?><input type="text" name="contact" value="<?= e((string) ($editing['contact'] ?? ((string) ($user['callsign'] ?? '')))) ?>"></label>
            <p><button class="button"><?= e($t('save')) ?></button></p>
        </form>
    </section>

    <section class="card">
        <h2><?= e($t('my_ads')) ?></h2>
        <?php if ($myAds === []): ?><p class="help"><?= e($t('none')) ?></p><?php else: ?>
            <div class="table-wrap"><table><thead><tr><th><?= e($t('title_label')) ?></th><th><?= e($t('status_label')) ?></th><th><?= e($t('actions')) ?></th></tr></thead><tbody>
            <?php foreach ($myAds as $ad): ?>
                <tr>
                    <td><strong><?= e((string) $ad['title']) ?></strong><div class="help"><?= e((string) $ad['location']) ?> · <?= e(number_format(((int) $ad['price_cents']) / 100, 2, ',', ' ')) ?> €</div></td>
                    <td><span class="badge muted"><?= e((string) $ad['status']) ?></span></td>
                    <td>
                        <a href="<?= e(route_url('classifieds', ['edit' => (int) $ad['id']])) ?>"><?= e($t('edit')) ?></a>
                        <form method="post" class="inline-form" style="display:inline-flex;gap:.4rem;margin-left:.5rem;">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="set_status"><input type="hidden" name="id" value="<?= (int) $ad['id'] ?>">
                            <button class="button ghost" name="status" value="sold"><?= e($t('mark_sold')) ?></button>
                            <button class="button ghost" name="status" value="active"><?= e($t('reactivate')) ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </section>
</div>

<section class="card" style="margin-top:1rem;">
    <h2><?= e($t('all_ads')) ?></h2>
    <?php if ($allAds === []): ?><p class="help"><?= e($t('none')) ?></p><?php else: ?>
        <div class="stack">
            <?php foreach ($allAds as $ad): ?>
            <article class="card" style="margin:0;">
                <h3 style="margin:0;"><?= e((string) $ad['title']) ?></h3>
                <p class="help"><?= e((string) ($categories[$ad['category_code']] ?? $ad['category_code'])) ?> · <?= e((string) ($ad['callsign'] ?? 'N/A')) ?> · <?= e((string) $ad['location']) ?></p>
                <p><?= nl2br(e((string) $ad['description'])) ?></p>
                <p><strong><?= e(number_format(((int) $ad['price_cents']) / 100, 2, ',', ' ')) ?> €</strong> — <?= e((string) $ad['contact']) ?></p>
            </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php

echo render_layout((string) ob_get_clean(), $t('title'));
