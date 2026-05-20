<?php
declare(strict_types=1);

require_module_enabled('dashboard');
$user = require_login();
$locale = current_locale();
$i18n = [
    'fr' => ['meta_title' => 'Tableau de bord membre', 'meta_desc' => 'Personnalisez votre tableau de bord ON4CRD avec vos widgets favoris.', 'title' => 'Tableau de bord', 'notifications' => 'Notifications', 'chatbot' => 'Assistant', 'fullscreen' => 'Plein écran', 'save_layout' => 'Enregistrer', 'widget_unavailable' => 'Widget temporairement indisponible.', 'table_missing' => 'La table dashboard_widgets est absente : la disposition des widgets ne peut pas être enregistrée.', 'available_widgets' => 'Widgets', 'widgets_help' => 'Installez vos widgets, puis glissez-déposez pour réordonner la grille.', 'no_widgets' => 'Aucun widget activé pour le moment.', 'add' => 'Ajouter', 'close' => 'Fermer'],
    'en' => ['meta_title' => 'Member dashboard', 'meta_desc' => 'Customize your ON4CRD dashboard with your favorite widgets.', 'title' => 'Member dashboard', 'notifications' => 'Notifications', 'chatbot' => 'Assistant', 'fullscreen' => 'Fullscreen', 'save_layout' => 'Save', 'widget_unavailable' => 'Widget temporarily unavailable.', 'table_missing' => 'The dashboard_widgets table is missing: widget layout cannot be saved.', 'available_widgets' => 'Widgets', 'widgets_help' => 'Install your widgets, then drag and drop to reorder the grid.', 'no_widgets' => 'No widgets are currently enabled.', 'add' => 'Add', 'close' => 'Close'],
    'de' => ['meta_title' => 'Mitglieder-Dashboard', 'meta_desc' => 'Passen Sie Ihr ON4CRD-Dashboard mit Ihren bevorzugten Widgets an.', 'title' => 'Mitglieder-Dashboard', 'notifications' => 'Benachrichtigungen', 'chatbot' => 'Assistent', 'fullscreen' => 'Vollbild', 'save_layout' => 'Speichern', 'widget_unavailable' => 'Widget vorübergehend nicht verfügbar.', 'table_missing' => 'Die Tabelle dashboard_widgets fehlt: Das Widget-Layout kann nicht gespeichert werden.', 'available_widgets' => 'Widgets', 'widgets_help' => 'Installieren Sie Ihre Widgets und ordnen Sie das Raster per Drag-and-drop neu.', 'no_widgets' => 'Derzeit sind keine Widgets aktiviert.', 'add' => 'Hinzufügen', 'close' => 'Schließen'],
    'es' => ['meta_title' => 'Panel de miembro', 'meta_desc' => 'Personalice su panel ON4CRD.', 'title' => 'Panel de miembro', 'notifications' => 'Notificaciones', 'chatbot' => 'Asistente', 'fullscreen' => 'Pantalla completa', 'save_layout' => 'Guardar', 'widget_unavailable' => 'Widget temporalmente no disponible.', 'table_missing' => 'Falta la tabla dashboard_widgets.', 'available_widgets' => 'Widgets', 'widgets_help' => 'Instale widgets y arrástrelos para ordenar la cuadrícula.', 'no_widgets' => 'No hay widgets activados.', 'add' => 'Añadir', 'close' => 'Cerrar'],
    'it' => ['meta_title' => 'Dashboard membri', 'meta_desc' => 'Personalizza il tuo dashboard ON4CRD.', 'title' => 'Dashboard membri', 'notifications' => 'Notifiche', 'chatbot' => 'Assistente', 'fullscreen' => 'Schermo intero', 'save_layout' => 'Salva', 'widget_unavailable' => 'Widget temporaneamente non disponibile.', 'table_missing' => 'Manca la tabella dashboard_widgets.', 'available_widgets' => 'Widget', 'widgets_help' => 'Installa i widget e trascinali per riordinare la griglia.', 'no_widgets' => 'Nessun widget attivo.', 'add' => 'Aggiungi', 'close' => 'Chiudi'],
    'pt' => ['meta_title' => 'Painel de membro', 'meta_desc' => 'Personalize o seu painel ON4CRD.', 'title' => 'Painel de membro', 'notifications' => 'Notificações', 'chatbot' => 'Assistente', 'fullscreen' => 'Ecrã inteiro', 'save_layout' => 'Guardar', 'widget_unavailable' => 'Widget temporariamente indisponível.', 'table_missing' => 'A tabela dashboard_widgets está em falta.', 'available_widgets' => 'Widgets', 'widgets_help' => 'Instale os widgets e arraste para reordenar a grelha.', 'no_widgets' => 'Nenhum widget ativo.', 'add' => 'Adicionar', 'close' => 'Fechar'],
    'nl' => ['meta_title' => 'Leden-dashboard', 'meta_desc' => 'Pas je ON4CRD-dashboard aan met je favoriete widgets.', 'title' => 'Leden-dashboard', 'notifications' => 'Meldingen', 'chatbot' => 'Assistent', 'fullscreen' => 'Volledig scherm', 'save_layout' => 'Opslaan', 'widget_unavailable' => 'Widget tijdelijk niet beschikbaar.', 'table_missing' => 'De tabel dashboard_widgets ontbreekt: de widgetindeling kan niet worden opgeslagen.', 'available_widgets' => 'Widgets', 'widgets_help' => 'Installeer je widgets en herschik het raster met slepen en neerzetten.', 'no_widgets' => 'Er zijn momenteel geen widgets geactiveerd.', 'add' => 'Toevoegen', 'close' => 'Sluiten'],
    'ar' => ['meta_title' => 'لوحة تحكم العضو', 'meta_desc' => 'خصّص لوحة تحكم ON4CRD باستخدام الودجات المفضلة لديك.', 'title' => 'لوحة تحكم العضو', 'notifications' => 'الإشعارات', 'chatbot' => 'المساعد', 'fullscreen' => 'ملء الشاشة', 'save_layout' => 'حفظ', 'widget_unavailable' => 'الودجت غير متاح مؤقتاً.', 'table_missing' => 'جدول dashboard_widgets غير موجود: لا يمكن حفظ تخطيط الودجات.', 'available_widgets' => 'الودجات', 'widgets_help' => 'ثبّت ودجاتك ثم اسحبها وأفلتها لإعادة ترتيب الشبكة.', 'no_widgets' => 'لا توجد ودجات مفعلة حالياً.', 'add' => 'إضافة', 'close' => 'إغلاق'],
    'bn' => ['meta_title' => 'সদস্য ড্যাশবোর্ড', 'meta_desc' => 'আপনার পছন্দের উইজেট দিয়ে ON4CRD ড্যাশবোর্ড সাজান।', 'title' => 'সদস্য ড্যাশবোর্ড', 'notifications' => 'বিজ্ঞপ্তি', 'chatbot' => 'সহকারী', 'fullscreen' => 'পূর্ণ পর্দা', 'save_layout' => 'সংরক্ষণ', 'widget_unavailable' => 'উইজেট সাময়িকভাবে উপলব্ধ নয়।', 'table_missing' => 'dashboard_widgets টেবিল নেই: উইজেট বিন্যাস সংরক্ষণ করা যাবে না।', 'available_widgets' => 'উইজেট', 'widgets_help' => 'উইজেট ইনস্টল করুন, তারপর গ্রিড পুনর্বিন্যাস করতে টেনে আনুন।', 'no_widgets' => 'বর্তমানে কোনো উইজেট সক্রিয় নেই।', 'add' => 'যোগ করুন', 'close' => 'বন্ধ'],
    'hi' => ['meta_title' => 'सदस्य डैशबोर्ड', 'meta_desc' => 'अपने पसंदीदा विजेट से ON4CRD डैशबोर्ड को अनुकूलित करें।', 'title' => 'सदस्य डैशबोर्ड', 'notifications' => 'सूचनाएँ', 'chatbot' => 'सहायक', 'fullscreen' => 'पूर्ण स्क्रीन', 'save_layout' => 'सहेजें', 'widget_unavailable' => 'विजेट अस्थायी रूप से उपलब्ध नहीं है।', 'table_missing' => 'dashboard_widgets तालिका मौजूद नहीं है: विजेट लेआउट सहेजा नहीं जा सकता।', 'available_widgets' => 'विजेट', 'widgets_help' => 'अपने विजेट इंस्टॉल करें, फिर ग्रिड को फिर से क्रमित करने के लिए खींचें और छोड़ें।', 'no_widgets' => 'फ़िलहाल कोई विजेट सक्रिय नहीं है।', 'add' => 'जोड़ें', 'close' => 'बंद करें'],
    'id' => ['meta_title' => 'Dasbor anggota', 'meta_desc' => 'Sesuaikan dasbor ON4CRD dengan widget favorit Anda.', 'title' => 'Dasbor anggota', 'notifications' => 'Notifikasi', 'chatbot' => 'Asisten', 'fullscreen' => 'Layar penuh', 'save_layout' => 'Simpan', 'widget_unavailable' => 'Widget sementara tidak tersedia.', 'table_missing' => 'Tabel dashboard_widgets tidak ada: tata letak widget tidak dapat disimpan.', 'available_widgets' => 'Widget', 'widgets_help' => 'Pasang widget Anda, lalu seret dan lepas untuk mengurutkan ulang grid.', 'no_widgets' => 'Saat ini tidak ada widget yang aktif.', 'add' => 'Tambah', 'close' => 'Tutup'],
    'ja' => ['meta_title' => 'メンバーダッシュボード', 'meta_desc' => 'お気に入りのウィジェットでON4CRDダッシュボードをカスタマイズします。', 'title' => 'メンバーダッシュボード', 'notifications' => '通知', 'chatbot' => 'アシスタント', 'fullscreen' => '全画面', 'save_layout' => '保存', 'widget_unavailable' => 'ウィジェットは一時的に利用できません。', 'table_missing' => 'dashboard_widgets テーブルがないため、ウィジェット配置を保存できません。', 'available_widgets' => 'ウィジェット', 'widgets_help' => 'ウィジェットを追加し、ドラッグ＆ドロップでグリッドを並べ替えます。', 'no_widgets' => '現在有効なウィジェットはありません。', 'add' => '追加', 'close' => '閉じる'],
    'ru' => ['meta_title' => 'Панель участника', 'meta_desc' => 'Настройте панель ON4CRD с любимыми виджетами.', 'title' => 'Панель участника', 'notifications' => 'Уведомления', 'chatbot' => 'Ассистент', 'fullscreen' => 'На весь экран', 'save_layout' => 'Сохранить', 'widget_unavailable' => 'Виджет временно недоступен.', 'table_missing' => 'Таблица dashboard_widgets отсутствует: макет виджетов нельзя сохранить.', 'available_widgets' => 'Виджеты', 'widgets_help' => 'Установите виджеты, затем перетаскивайте их, чтобы изменить порядок сетки.', 'no_widgets' => 'Сейчас нет активных виджетов.', 'add' => 'Добавить', 'close' => 'Закрыть'],
    'zh' => ['meta_title' => '会员仪表板', 'meta_desc' => '使用你喜欢的小组件自定义 ON4CRD 仪表板。', 'title' => '会员仪表板', 'notifications' => '通知', 'chatbot' => '助手', 'fullscreen' => '全屏', 'save_layout' => '保存', 'widget_unavailable' => '小组件暂时不可用。', 'table_missing' => '缺少 dashboard_widgets 表：无法保存小组件布局。', 'available_widgets' => '小组件', 'widgets_help' => '安装你的小组件，然后拖放以重新排列网格。', 'no_widgets' => '当前没有启用的小组件。', 'add' => '添加', 'close' => '关闭'],
];
$i18n = i18n_expand_supported_locales($i18n);
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};
set_page_meta(['title' => $t('meta_title'), 'description' => $t('meta_desc'), 'schema_type' => 'WebPage']);
$availableWidgets = enabled_widget_catalog();
$dashboardPersistenceEnabled = table_exists('dashboard_widgets');
$selected = [];
if ($dashboardPersistenceEnabled) {
    $userWidgets = db()->prepare('SELECT widget_key, config_json, position FROM dashboard_widgets WHERE member_id = ? ORDER BY position ASC');
    $userWidgets->execute([(int) $user['id']]);
    $selected = $userWidgets->fetchAll();
}
$selectedWidgets = [];
$seenSelected = [];
foreach ($selected as $row) {
    $widgetKey = (string) ($row['widget_key'] ?? '');
    if ($widgetKey === '' || !array_key_exists($widgetKey, $availableWidgets) || isset($seenSelected[$widgetKey])) {
        continue;
    }
    $decodedConfig = json_decode((string) ($row['config_json'] ?? ''), true);
    $seenSelected[$widgetKey] = true;
    $selectedWidgets[] = [
        'key' => $widgetKey,
        'config' => is_array($decodedConfig) ? $decodedConfig : [],
    ];
}
if ($selectedWidgets === []) {
    $defaultWidgetKeys = array_values(array_intersect(['welcome', 'propagation', 'club_status', 'chatbot'], array_keys($availableWidgets)));
    if ($defaultWidgetKeys === []) {
        $defaultWidgetKeys = array_slice(array_keys($availableWidgets), 0, 4);
    }
    $selectedWidgets = array_map(static fn(string $key): array => ['key' => $key, 'config' => []], $defaultWidgetKeys);
}
$selectedKeys = array_map(static fn(array $widget): string => (string) $widget['key'], $selectedWidgets);
$availableToAdd = array_filter($availableWidgets, static fn(string $key): bool => !in_array($key, $selectedKeys, true), ARRAY_FILTER_USE_KEY);


$safeRenderWidget = static function (string $widgetKey, array $currentUser) use ($t): string {
    try {
        return render_widget($widgetKey, $currentUser);
    } catch (Throwable $throwable) {
        return '<p class="help">' . e($t('widget_unavailable')) . '</p>';
    }
};

$dashboardConfig = [
    'renderBase' => base_url('index.php?route=widget_render&widget='),
    'saveUrl' => base_url('index.php?route=save_dashboard'),
    'saveEnabled' => $dashboardPersistenceEnabled,
    'refreshMs' => 90000,
    'csrf' => csrf_token(),
];

ob_start();
?>
<div class="dashboard-fullwidth" id="dashboard-shell">
  <section class="card">
    <div class="row-between">
      <div>
        <h1 class="dashboard-heading"><?= e($t('title')) ?></h1>
      </div>
      <div class="actions">
        <button class="button secondary small" id="open-widgets-panel" type="button" aria-controls="dashboard-widgets-panel" aria-expanded="false">🧩 <?= e($t('available_widgets')) ?></button>
        <button class="button secondary small" id="dashboard-fullscreen-toggle" type="button">⛶ <?= e($t('fullscreen')) ?></button>
        <a class="button secondary small" href="<?= e(route_url('news')) ?>">🔔 <?= e($t('notifications')) ?></a>
        <a class="button secondary small" href="<?= e(route_url('chatbot')) ?>">🤖 <?= e($t('chatbot')) ?></a>
        <button class="button secondary small" id="save-dashboard" type="button" <?= $dashboardPersistenceEnabled ? '' : 'disabled' ?>>💾 <?= e($t('save_layout')) ?></button>
        <span class="help" id="dashboard-save-status" role="status" aria-live="polite"></span>
      </div>
    </div>
    <?php if (!$dashboardPersistenceEnabled): ?>
      <p class="flash flash-error"><?= e($t('table_missing')) ?></p>
    <?php endif; ?>
    <div id="dashboard-grid" class="widget-grid" data-config='<?= e(json_encode($dashboardConfig, JSON_UNESCAPED_SLASHES)) ?>'>
      <?php if ($selectedWidgets === []): ?>
        <p class="help"><?= e($t('no_widgets')) ?></p>
      <?php endif; ?>
      <?php foreach ($selectedWidgets as $selectedWidget): ?>
        <?php
          $widgetKey = (string) $selectedWidget['key'];
          $widgetTitle = (string) ($availableWidgets[$widgetKey]['title'] ?? $widgetKey);
          $widgetConfig = (array) ($selectedWidget['config'] ?? []);
          $widgetBodyHtml = $safeRenderWidget($widgetKey, $user);
          include __DIR__ . '/dashboard_widget_card.php';
        ?>
      <?php endforeach; ?>
    </div>
  </section>
</div>
<div class="dashboard-offcanvas-backdrop" id="dashboard-widgets-backdrop" hidden></div>
<aside class="dashboard-offcanvas" id="dashboard-widgets-panel" aria-hidden="true" data-widget-unavailable="<?= e($t('widget_unavailable')) ?>">
  <header class="dashboard-offcanvas-header">
    <h2><?= e($t('available_widgets')) ?></h2>
    <button class="ghost" type="button" id="close-widgets-panel" aria-label="<?= e($t('close')) ?>">✕</button>
  </header>
  <p class="help"><?= e($t('widgets_help')) ?></p>
  <div class="stack">
    <?php foreach ($availableToAdd as $widgetKey => $widget): ?>
      <article class="widget-card">
        <header>
          <strong><?= e((string) $widget['title']) ?></strong>
        </header>
        <p class="help"><?= e((string) ($widget['description'] ?? '')) ?></p>
        <div class="widget-body widget-preview" data-widget-preview="<?= e((string) $widgetKey) ?>"><p class="help">…</p></div>
        <button class="button small add-widget" type="button" data-widget="<?= e($widgetKey) ?>" data-title="<?= e((string) $widget['title']) ?>"><?= e($t('add')) ?></button>
      </article>
    <?php endforeach; ?>
  </div>
</aside>
<?php include __DIR__ . '/dashboard_script.js.php'; ?>
<?php
echo render_layout((string) ob_get_clean(), $t('title'));
