<?php
declare(strict_types=1);

$base = dirname(__DIR__) . '/app/i18n/dashboard';
$map = [
    'ar' => ['signal_article' => 'المقالات', 'signal_wiki' => 'الويكي', 'signal_classified' => 'الإعلانات', 'signal_album' => 'الألبومات', 'signal_library' => 'المكتبة'],
    'bn' => ['signal_article' => 'প্রবন্ধ', 'signal_wiki' => 'উইকি', 'signal_classified' => 'বিজ্ঞাপন', 'signal_album' => 'অ্যালবাম', 'signal_library' => 'লাইব্রেরি'],
    'de' => ['signal_article' => 'Artikel', 'signal_wiki' => 'Wiki', 'signal_classified' => 'Kleinanzeigen', 'signal_album' => 'Alben', 'signal_library' => 'Bibliothek'],
    'en' => ['signal_article' => 'Articles', 'signal_wiki' => 'Wiki', 'signal_classified' => 'Classifieds', 'signal_album' => 'Albums', 'signal_library' => 'Library'],
    'es' => ['signal_article' => 'Articulos', 'signal_wiki' => 'Wiki', 'signal_classified' => 'Anuncios', 'signal_album' => 'Albumes', 'signal_library' => 'Biblioteca'],
    'fr' => ['signal_article' => 'Articles', 'signal_wiki' => 'Wiki', 'signal_classified' => 'Annonces', 'signal_album' => 'Albums', 'signal_library' => 'Bibliotheque'],
    'hi' => ['signal_article' => 'लेख', 'signal_wiki' => 'विकी', 'signal_classified' => 'विज्ञापन', 'signal_album' => 'एल्बम', 'signal_library' => 'पुस्तकालय'],
    'id' => ['signal_article' => 'Artikel', 'signal_wiki' => 'Wiki', 'signal_classified' => 'Iklan', 'signal_album' => 'Album', 'signal_library' => 'Pustaka'],
    'it' => ['signal_article' => 'Articoli', 'signal_wiki' => 'Wiki', 'signal_classified' => 'Annunci', 'signal_album' => 'Album', 'signal_library' => 'Biblioteca'],
    'ja' => ['signal_article' => '記事', 'signal_wiki' => 'Wiki', 'signal_classified' => '広告', 'signal_album' => 'アルバム', 'signal_library' => 'ライブラリ'],
    'nl' => ['signal_article' => 'Artikelen', 'signal_wiki' => 'Wiki', 'signal_classified' => 'Advertenties', 'signal_album' => 'Albums', 'signal_library' => 'Bibliotheek'],
    'pt' => ['signal_article' => 'Artigos', 'signal_wiki' => 'Wiki', 'signal_classified' => 'Anuncios', 'signal_album' => 'Albuns', 'signal_library' => 'Biblioteca'],
    'ru' => ['signal_article' => 'Статьи', 'signal_wiki' => 'Вики', 'signal_classified' => 'Объявления', 'signal_album' => 'Альбомы', 'signal_library' => 'Библиотека'],
    'zh' => ['signal_article' => '文章', 'signal_wiki' => 'Wiki', 'signal_classified' => '分类信息', 'signal_album' => '相册', 'signal_library' => '资料库'],
];

$extra = [
    'ar' => ['activity_timeline_title' => 'الجدول الزمني للنشاط', 'activity_timeline_empty' => 'لا توجد عناصر نشاط حالياً.', 'onboarding_nudges_title' => 'تلميحات البدء', 'onboarding_nudges_empty' => 'لا توجد تلميحات حالياً.', 'nudge_add_favorites' => 'أضف عناصر إلى المفضلة لتحسين النتائج.', 'nudge_review_notifications' => 'راجع الإشعارات غير المقروءة.', 'nudge_enable_recommendations' => 'فعّل التوصيات المخصصة.'],
    'bn' => ['activity_timeline_title' => 'কার্যকলাপ টাইমলাইন', 'activity_timeline_empty' => 'এখনো কোনো কার্যকলাপ নেই।', 'onboarding_nudges_title' => 'শুরুর পরামর্শ', 'onboarding_nudges_empty' => 'এখন কোনো পরামর্শ নেই।', 'nudge_add_favorites' => 'সুপারিশ উন্নত করতে ফেভারিট যোগ করুন।', 'nudge_review_notifications' => 'অপঠিত নোটিফিকেশন দেখুন।', 'nudge_enable_recommendations' => 'ব্যক্তিগতকৃত সুপারিশ চালু করুন।'],
    'de' => ['activity_timeline_title' => 'Aktivitätsverlauf', 'activity_timeline_empty' => 'Noch keine Aktivität.', 'onboarding_nudges_title' => 'Einstiegshinweise', 'onboarding_nudges_empty' => 'Derzeit keine Hinweise.', 'nudge_add_favorites' => 'Fügen Sie Favoriten hinzu, um Empfehlungen zu verbessern.', 'nudge_review_notifications' => 'Prüfen Sie ungelesene Benachrichtigungen.', 'nudge_enable_recommendations' => 'Aktivieren Sie personalisierte Empfehlungen.'],
    'en' => ['activity_timeline_title' => 'Activity timeline', 'activity_timeline_empty' => 'No activity yet.', 'onboarding_nudges_title' => 'Onboarding nudges', 'onboarding_nudges_empty' => 'No nudges right now.', 'nudge_add_favorites' => 'Add favorites to improve recommendations.', 'nudge_review_notifications' => 'Review unread notifications.', 'nudge_enable_recommendations' => 'Enable personalized recommendations.'],
    'es' => ['activity_timeline_title' => 'Cronologia de actividad', 'activity_timeline_empty' => 'Aun no hay actividad.', 'onboarding_nudges_title' => 'Sugerencias de inicio', 'onboarding_nudges_empty' => 'No hay sugerencias por ahora.', 'nudge_add_favorites' => 'Agregue favoritos para mejorar las recomendaciones.', 'nudge_review_notifications' => 'Revise las notificaciones no leidas.', 'nudge_enable_recommendations' => 'Active las recomendaciones personalizadas.'],
    'fr' => ['activity_timeline_title' => 'Chronologie d activite', 'activity_timeline_empty' => 'Aucune activite pour le moment.', 'onboarding_nudges_title' => 'Suggestions de demarrage', 'onboarding_nudges_empty' => 'Aucune suggestion pour le moment.', 'nudge_add_favorites' => 'Ajoutez des favoris pour ameliorer les recommandations.', 'nudge_review_notifications' => 'Consultez les notifications non lues.', 'nudge_enable_recommendations' => 'Activez les recommandations personnalisees.'],
    'hi' => ['activity_timeline_title' => 'गतिविधि टाइमलाइन', 'activity_timeline_empty' => 'अभी कोई गतिविधि नहीं है।', 'onboarding_nudges_title' => 'ऑनबोर्डिंग संकेत', 'onboarding_nudges_empty' => 'फिलहाल कोई संकेत नहीं।', 'nudge_add_favorites' => 'सिफारिशें बेहतर करने के लिए फेवरेट जोड़ें।', 'nudge_review_notifications' => 'अपठित सूचनाएँ देखें।', 'nudge_enable_recommendations' => 'व्यक्तिगत सिफारिशें सक्षम करें।'],
    'id' => ['activity_timeline_title' => 'Linimasa aktivitas', 'activity_timeline_empty' => 'Belum ada aktivitas.', 'onboarding_nudges_title' => 'Saran awal', 'onboarding_nudges_empty' => 'Belum ada saran.', 'nudge_add_favorites' => 'Tambahkan favorit untuk meningkatkan rekomendasi.', 'nudge_review_notifications' => 'Tinjau notifikasi yang belum dibaca.', 'nudge_enable_recommendations' => 'Aktifkan rekomendasi yang dipersonalisasi.'],
    'it' => ['activity_timeline_title' => 'Timeline attività', 'activity_timeline_empty' => 'Nessuna attività al momento.', 'onboarding_nudges_title' => 'Suggerimenti iniziali', 'onboarding_nudges_empty' => 'Nessun suggerimento al momento.', 'nudge_add_favorites' => 'Aggiungi preferiti per migliorare i suggerimenti.', 'nudge_review_notifications' => 'Controlla le notifiche non lette.', 'nudge_enable_recommendations' => 'Attiva le raccomandazioni personalizzate.'],
    'ja' => ['activity_timeline_title' => 'アクティビティタイムライン', 'activity_timeline_empty' => '現在アクティビティはありません。', 'onboarding_nudges_title' => 'オンボーディング提案', 'onboarding_nudges_empty' => '現在提案はありません。', 'nudge_add_favorites' => 'おすすめ改善のためお気に入りを追加してください。', 'nudge_review_notifications' => '未読通知を確認してください。', 'nudge_enable_recommendations' => 'パーソナライズ推薦を有効化してください。'],
    'nl' => ['activity_timeline_title' => 'Activiteitstijdlijn', 'activity_timeline_empty' => 'Nog geen activiteit.', 'onboarding_nudges_title' => 'Startsuggesties', 'onboarding_nudges_empty' => 'Momenteel geen suggesties.', 'nudge_add_favorites' => 'Voeg favorieten toe om aanbevelingen te verbeteren.', 'nudge_review_notifications' => 'Bekijk ongelezen meldingen.', 'nudge_enable_recommendations' => 'Schakel gepersonaliseerde aanbevelingen in.'],
    'pt' => ['activity_timeline_title' => 'Cronologia de atividades', 'activity_timeline_empty' => 'Ainda sem atividade.', 'onboarding_nudges_title' => 'Sugestoes iniciais', 'onboarding_nudges_empty' => 'Sem sugestoes no momento.', 'nudge_add_favorites' => 'Adicione favoritos para melhorar as recomendacoes.', 'nudge_review_notifications' => 'Revise notificacoes nao lidas.', 'nudge_enable_recommendations' => 'Ative recomendacoes personalizadas.'],
    'ru' => ['activity_timeline_title' => 'Лента активности', 'activity_timeline_empty' => 'Пока нет активности.', 'onboarding_nudges_title' => 'Подсказки старта', 'onboarding_nudges_empty' => 'Сейчас нет подсказок.', 'nudge_add_favorites' => 'Добавьте избранное для улучшения рекомендаций.', 'nudge_review_notifications' => 'Проверьте непрочитанные уведомления.', 'nudge_enable_recommendations' => 'Включите персонализированные рекомендации.'],
    'zh' => ['activity_timeline_title' => '活动时间线', 'activity_timeline_empty' => '暂无活动。', 'onboarding_nudges_title' => '引导提示', 'onboarding_nudges_empty' => '当前没有提示。', 'nudge_add_favorites' => '添加收藏以改进推荐。', 'nudge_review_notifications' => '查看未读通知。', 'nudge_enable_recommendations' => '启用个性化推荐。'],
];

foreach ($map as $locale => $pairs) {
    $path = $base . '/' . $locale . '.php';
    if (!is_file($path)) {
        continue;
    }
    $data = require $path;
    if (!is_array($data)) {
        continue;
    }
    foreach ($pairs as $key => $value) {
        $data[$key] = $value;
    }
    foreach (($extra[$locale] ?? []) as $key => $value) {
        $data[$key] = $value;
    }
    $out = "<?php\ndeclare(strict_types=1);\n\nreturn " . var_export($data, true) . ";\n";
    file_put_contents($path, $out);
}

echo "dashboard signal translations updated\n";
