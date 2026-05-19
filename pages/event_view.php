<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['not_found' => 'Événement introuvable', 'not_found_msg' => "L'événement demandé est indisponible.", 'back' => '← Retour au calendrier', 'summary_fallback' => 'Retrouvez toutes les informations utiles sur cet événement.', 'start' => 'Début', 'end' => 'Fin', 'location' => 'Lieu', 'tbd' => 'À confirmer', 'site' => "Site de l'événement", 'title' => 'Événement'],
    'en' => ['not_found' => 'Event not found', 'not_found_msg' => 'The requested event is unavailable.', 'back' => '← Back to calendar', 'summary_fallback' => 'Find all useful information about this event.', 'start' => 'Start', 'end' => 'End', 'location' => 'Location', 'tbd' => 'To be confirmed', 'site' => 'Event website', 'title' => 'Event'],
    'de' => ['not_found' => 'Veranstaltung nicht gefunden', 'not_found_msg' => 'Die angeforderte Veranstaltung ist nicht verfügbar.', 'back' => '← Zurück zum Kalender', 'summary_fallback' => 'Hier finden Sie alle wichtigen Informationen zu dieser Veranstaltung.', 'start' => 'Beginn', 'end' => 'Ende', 'location' => 'Ort', 'tbd' => 'Noch offen', 'site' => 'Veranstaltungsseite', 'title' => 'Veranstaltung'],
    'es' => ['not_found' => 'Evento no encontrado', 'not_found_msg' => 'El evento solicitado no está disponible.', 'back' => '← Volver al calendario', 'summary_fallback' => 'Encuentre toda la información útil sobre este evento.', 'start' => 'Inicio', 'end' => 'Fin', 'location' => 'Lugar', 'tbd' => 'Por confirmar', 'site' => 'Sitio del evento', 'title' => 'Evento'],
    'it' => ['not_found' => 'Evento non trovato', 'not_found_msg' => 'L\'evento richiesto non è disponibile.', 'back' => '← Torna al calendario', 'summary_fallback' => 'Trova tutte le informazioni utili su questo evento.', 'start' => 'Inizio', 'end' => 'Fine', 'location' => 'Luogo', 'tbd' => 'Da confermare', 'site' => 'Sito evento', 'title' => 'Evento'],
    'pt' => ['not_found' => 'Evento não encontrado', 'not_found_msg' => 'O evento solicitado não está disponível.', 'back' => '← Voltar ao calendário', 'summary_fallback' => 'Encontre todas as informações úteis sobre este evento.', 'start' => 'Início', 'end' => 'Fim', 'location' => 'Local', 'tbd' => 'A confirmar', 'site' => 'Site do evento', 'title' => 'Evento'],
    'nl' => ['not_found' => 'Evenement niet gevonden', 'not_found_msg' => 'Het gevraagde evenement is niet beschikbaar.', 'back' => '← Terug naar kalender', 'summary_fallback' => 'Vind alle nuttige informatie over dit evenement.', 'start' => 'Start', 'end' => 'Einde', 'location' => 'Locatie', 'tbd' => 'Nog te bevestigen', 'site' => 'Evenementwebsite', 'title' => 'Evenement'],

    'ar' => ['not_found' => 'الفعالية غير موجودة', 'not_found_msg' => 'الفعالية المطلوبة غير متاحة.', 'back' => '← العودة إلى التقويم', 'summary_fallback' => 'اعثر على كل المعلومات المفيدة حول هذه الفعالية.', 'start' => 'البداية', 'end' => 'النهاية', 'location' => 'الموقع', 'tbd' => 'سيُحدَّد لاحقًا', 'site' => 'موقع الفعالية', 'title' => 'فعالية'],
    'bn' => ['not_found' => 'ইভেন্ট পাওয়া যায়নি', 'not_found_msg' => 'অনুরোধকৃত ইভেন্টটি উপলব্ধ নয়।', 'back' => '← ক্যালেন্ডারে ফিরে যান', 'summary_fallback' => 'এই ইভেন্ট সম্পর্কে সব প্রয়োজনীয় তথ্য দেখুন।', 'start' => 'শুরু', 'end' => 'শেষ', 'location' => 'স্থান', 'tbd' => 'পরে নিশ্চিত করা হবে', 'site' => 'ইভেন্ট ওয়েবসাইট', 'title' => 'ইভেন্ট'],
    'hi' => ['not_found' => 'कार्यक्रम नहीं मिला', 'not_found_msg' => 'मांगा गया कार्यक्रम उपलब्ध नहीं है।', 'back' => '← कैलेंडर पर वापस जाएँ', 'summary_fallback' => 'इस कार्यक्रम की सभी उपयोगी जानकारी देखें।', 'start' => 'आरंभ', 'end' => 'समाप्ति', 'location' => 'स्थान', 'tbd' => 'बाद में पुष्टि होगी', 'site' => 'कार्यक्रम वेबसाइट', 'title' => 'कार्यक्रम'],
    'id' => ['not_found' => 'Acara tidak ditemukan', 'not_found_msg' => 'Acara yang diminta tidak tersedia.', 'back' => '← Kembali ke kalender', 'summary_fallback' => 'Temukan semua informasi penting tentang acara ini.', 'start' => 'Mulai', 'end' => 'Selesai', 'location' => 'Lokasi', 'tbd' => 'Akan dikonfirmasi', 'site' => 'Situs acara', 'title' => 'Acara'],
    'ja' => ['not_found' => 'イベントが見つかりません', 'not_found_msg' => '指定されたイベントは利用できません。', 'back' => '← カレンダーに戻る', 'summary_fallback' => 'このイベントに関する有用な情報をご確認ください。', 'start' => '開始', 'end' => '終了', 'location' => '場所', 'tbd' => '未定', 'site' => 'イベントサイト', 'title' => 'イベント'],
    'ru' => ['not_found' => 'Событие не найдено', 'not_found_msg' => 'Запрошенное событие недоступно.', 'back' => '← Назад к календарю', 'summary_fallback' => 'Здесь вы найдете всю полезную информацию об этом событии.', 'start' => 'Начало', 'end' => 'Окончание', 'location' => 'Место', 'tbd' => 'Будет уточнено', 'site' => 'Сайт события', 'title' => 'Событие'],
    'zh' => ['not_found' => '未找到活动', 'not_found_msg' => '请求的活动不可用。', 'back' => '← 返回日历', 'summary_fallback' => '查看此活动的所有有用信息。', 'start' => '开始', 'end' => '结束', 'location' => '地点', 'tbd' => '待确认', 'site' => '活动网站', 'title' => '活动'],
];
$i18n = i18n_expand_supported_locales($i18n);
$t = [];
foreach (array_keys($i18n['fr']) as $key) {
    $t[$key] = i18n_localized_value($i18n, $locale, $key);
}

$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '' || !table_exists('events')) {
    http_response_code(404);
    echo render_layout('<div class="card"><h1>' . e($t['not_found']) . '</h1><p>' . e($t['not_found_msg']) . '</p></div>', $t['title']);
    return;
}

$stmt = db()->prepare('SELECT * FROM events WHERE slug = ? AND status = "published" LIMIT 1');
$stmt->execute([$slug]);
$event = $stmt->fetch();

if (!is_array($event)) {
    http_response_code(404);
    echo render_layout('<div class="card"><h1>' . e($t['not_found']) . '</h1><p>' . e($t['not_found_msg']) . '</p></div>', $t['title']);
    return;
}

$startAt = new DateTimeImmutable((string) $event['start_at']);
$endAt = new DateTimeImmutable((string) $event['end_at']);
$summary = trim((string) ($event['summary'] ?? ''));
$description = trim((string) ($event['description'] ?? ''));
$location = trim((string) ($event['location'] ?? ''));
$externalUrl = trim((string) ($event['external_url'] ?? ''));

if ($summary === '') {
    $summary = $t['summary_fallback'];
}

ob_start();
?>
<article class="card events-single-card">
    <p><a href="<?= e(route_url('events')) ?>"><?= e($t['back']) ?></a></p>
    <h1><?= e((string) $event['title']) ?></h1>
    <p class="help"><?= e($summary) ?></p>

    <dl class="events-single-meta">
        <dt><?= e($t['start']) ?></dt><dd><?= e($startAt->format('d/m/Y H:i')) ?></dd>
        <dt><?= e($t['end']) ?></dt><dd><?= e($endAt->format('d/m/Y H:i')) ?></dd>
        <dt><?= e($t['location']) ?></dt><dd><?= e($location !== '' ? $location : $t['tbd']) ?></dd>
    </dl>

    <?php if ($description !== ''): ?>
        <section class="events-single-description">
            <?= $description ?>
        </section>
    <?php endif; ?>

    <?php if ($externalUrl !== ''): ?>
        <p><a class="button" href="<?= e($externalUrl) ?>" target="_blank" rel="noopener noreferrer"><?= e($t['site']) ?></a></p>
    <?php endif; ?>
</article>
<?php
echo render_layout((string) ob_get_clean(), (string) $event['title']);
