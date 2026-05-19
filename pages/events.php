<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['title' => 'Événements', 'agenda_unavailable' => "L'agenda n'est pas encore disponible.", 'export' => 'Exporter', 'calendar_load_error' => 'Impossible de charger le calendrier interactif.', 'event' => 'Événement', 'no_summary' => 'Aucun résumé disponible.', 'location_tbd' => 'À confirmer', 'today' => 'Aujourd’hui', 'month' => 'Mois', 'week' => 'Semaine', 'list' => 'Liste', 'detail' => 'Détail', 'start' => 'Début', 'end' => 'Fin', 'location' => 'Lieu', 'view_sheet' => 'Voir la fiche', 'external_link' => 'Lien externe', 'no_event' => 'Aucun événement publié pour le moment.', 'calendar_name' => 'Agenda ON4CRD', 'ics_filename' => 'on4crd-evenements.ics'],
    'en' => ['title' => 'Events', 'agenda_unavailable' => 'The calendar is not available yet.', 'export' => 'Export', 'calendar_load_error' => 'Unable to load the interactive calendar.', 'event' => 'Event', 'no_summary' => 'No summary available.', 'location_tbd' => 'To be confirmed', 'today' => 'Today', 'month' => 'Month', 'week' => 'Week', 'list' => 'List', 'detail' => 'Details', 'start' => 'Start', 'end' => 'End', 'location' => 'Location', 'view_sheet' => 'View details', 'external_link' => 'External link', 'no_event' => 'No published event at the moment.', 'calendar_name' => 'ON4CRD Calendar', 'ics_filename' => 'on4crd-events.ics'],
    'de' => ['title' => 'Veranstaltungen', 'agenda_unavailable' => 'Der Kalender ist noch nicht verfügbar.', 'export' => 'Exportieren', 'calendar_load_error' => 'Interaktiver Kalender konnte nicht geladen werden.', 'event' => 'Veranstaltung', 'no_summary' => 'Keine Zusammenfassung verfügbar.', 'location_tbd' => 'Noch offen', 'today' => 'Heute', 'month' => 'Monat', 'week' => 'Woche', 'list' => 'Liste', 'detail' => 'Details', 'start' => 'Beginn', 'end' => 'Ende', 'location' => 'Ort', 'view_sheet' => 'Details ansehen', 'external_link' => 'Externer Link', 'no_event' => 'Derzeit keine veröffentlichten Veranstaltungen.', 'calendar_name' => 'ON4CRD Kalender', 'ics_filename' => 'on4crd-veranstaltungen.ics'],
    'es' => ['title' => 'Eventos', 'agenda_unavailable' => 'La agenda aún no está disponible.', 'export' => 'Exportar', 'calendar_load_error' => 'No se puede cargar el calendario interactivo.', 'event' => 'Evento', 'no_summary' => 'No hay resumen disponible.', 'location_tbd' => 'Por confirmar', 'today' => 'Hoy', 'month' => 'Mes', 'week' => 'Semana', 'list' => 'Lista', 'detail' => 'Detalle', 'start' => 'Inicio', 'end' => 'Fin', 'location' => 'Lugar', 'view_sheet' => 'Ver ficha', 'external_link' => 'Enlace externo', 'no_event' => 'No hay eventos publicados por el momento.', 'calendar_name' => 'Agenda ON4CRD', 'ics_filename' => 'on4crd-eventos.ics'],
    'it' => ['title' => 'Eventi', 'agenda_unavailable' => 'Il calendario non è ancora disponibile.', 'export' => 'Esporta', 'calendar_load_error' => 'Impossibile caricare il calendario interattivo.', 'event' => 'Evento', 'no_summary' => 'Nessun riepilogo disponibile.', 'location_tbd' => 'Da confermare', 'today' => 'Oggi', 'month' => 'Mese', 'week' => 'Settimana', 'list' => 'Elenco', 'detail' => 'Dettaglio', 'start' => 'Inizio', 'end' => 'Fine', 'location' => 'Luogo', 'view_sheet' => 'Vedi scheda', 'external_link' => 'Link esterno', 'no_event' => 'Nessun evento pubblicato al momento.', 'calendar_name' => 'Agenda ON4CRD', 'ics_filename' => 'on4crd-eventi.ics'],
    'pt' => ['title' => 'Eventos', 'agenda_unavailable' => 'A agenda ainda não está disponível.', 'export' => 'Exportar', 'calendar_load_error' => 'Não foi possível carregar o calendário interativo.', 'event' => 'Evento', 'no_summary' => 'Sem resumo disponível.', 'location_tbd' => 'A confirmar', 'today' => 'Hoje', 'month' => 'Mês', 'week' => 'Semana', 'list' => 'Lista', 'detail' => 'Detalhe', 'start' => 'Início', 'end' => 'Fim', 'location' => 'Local', 'view_sheet' => 'Ver ficha', 'external_link' => 'Ligação externa', 'no_event' => 'Sem eventos publicados neste momento.', 'calendar_name' => 'Agenda ON4CRD', 'ics_filename' => 'on4crd-eventos.ics'],
    'nl' => ['title' => 'Evenementen', 'agenda_unavailable' => 'De agenda is nog niet beschikbaar.', 'export' => 'Exporteren', 'calendar_load_error' => 'Interactieve kalender kon niet geladen worden.', 'event' => 'Evenement', 'no_summary' => 'Geen samenvatting beschikbaar.', 'location_tbd' => 'Nog te bevestigen', 'today' => 'Vandaag', 'month' => 'Maand', 'week' => 'Week', 'list' => 'Lijst', 'detail' => 'Details', 'start' => 'Start', 'end' => 'Einde', 'location' => 'Locatie', 'view_sheet' => 'Bekijk detail', 'external_link' => 'Externe link', 'no_event' => 'Momenteel geen gepubliceerde evenementen.', 'calendar_name' => 'ON4CRD Agenda', 'ics_filename' => 'on4crd-evenementen.ics'],

    'ar' => ['title' => 'الفعاليات', 'agenda_unavailable' => 'التقويم غير متاح بعد.', 'export' => 'تصدير', 'calendar_load_error' => 'تعذّر تحميل التقويم التفاعلي.', 'event' => 'فعالية', 'no_summary' => 'لا يوجد ملخص متاح.', 'location_tbd' => 'سيُحدَّد لاحقًا', 'today' => 'اليوم', 'month' => 'شهر', 'week' => 'أسبوع', 'list' => 'قائمة', 'detail' => 'تفاصيل', 'start' => 'البداية', 'end' => 'النهاية', 'location' => 'الموقع', 'view_sheet' => 'عرض التفاصيل', 'external_link' => 'رابط خارجي', 'no_event' => 'لا توجد فعاليات منشورة حاليًا.', 'calendar_name' => 'تقويم ON4CRD', 'ics_filename' => 'on4crd-events-ar.ics'],
    'bn' => ['title' => 'ইভেন্টসমূহ', 'agenda_unavailable' => 'ক্যালেন্ডার এখনও উপলব্ধ নয়।', 'export' => 'রপ্তানি', 'calendar_load_error' => 'ইন্টারঅ্যাকটিভ ক্যালেন্ডার লোড করা যায়নি।', 'event' => 'ইভেন্ট', 'no_summary' => 'কোনও সারাংশ উপলব্ধ নয়।', 'location_tbd' => 'পরে নিশ্চিত করা হবে', 'today' => 'আজ', 'month' => 'মাস', 'week' => 'সপ্তাহ', 'list' => 'তালিকা', 'detail' => 'বিস্তারিত', 'start' => 'শুরু', 'end' => 'শেষ', 'location' => 'স্থান', 'view_sheet' => 'বিস্তারিত দেখুন', 'external_link' => 'বাহ্যিক লিংক', 'no_event' => 'এই মুহূর্তে কোনো ইভেন্ট প্রকাশিত নেই।', 'calendar_name' => 'ON4CRD ক্যালেন্ডার', 'ics_filename' => 'on4crd-events-bn.ics'],
    'hi' => ['title' => 'कार्यक्रम', 'agenda_unavailable' => 'कैलेंडर अभी उपलब्ध नहीं है।', 'export' => 'निर्यात', 'calendar_load_error' => 'इंटरैक्टिव कैलेंडर लोड नहीं हो सका।', 'event' => 'कार्यक्रम', 'no_summary' => 'कोई सारांश उपलब्ध नहीं है।', 'location_tbd' => 'बाद में पुष्टि होगी', 'today' => 'आज', 'month' => 'महीना', 'week' => 'सप्ताह', 'list' => 'सूची', 'detail' => 'विवरण', 'start' => 'आरंभ', 'end' => 'समाप्ति', 'location' => 'स्थान', 'view_sheet' => 'विवरण देखें', 'external_link' => 'बाहरी लिंक', 'no_event' => 'फिलहाल कोई प्रकाशित कार्यक्रम नहीं है।', 'calendar_name' => 'ON4CRD कैलेंडर', 'ics_filename' => 'on4crd-events-hi.ics'],
    'id' => ['title' => 'Acara', 'agenda_unavailable' => 'Kalender belum tersedia.', 'export' => 'Ekspor', 'calendar_load_error' => 'Gagal memuat kalender interaktif.', 'event' => 'Acara', 'no_summary' => 'Tidak ada ringkasan tersedia.', 'location_tbd' => 'Akan dikonfirmasi', 'today' => 'Hari ini', 'month' => 'Bulan', 'week' => 'Minggu', 'list' => 'Daftar', 'detail' => 'Detail', 'start' => 'Mulai', 'end' => 'Selesai', 'location' => 'Lokasi', 'view_sheet' => 'Lihat detail', 'external_link' => 'Tautan eksternal', 'no_event' => 'Belum ada acara yang dipublikasikan.', 'calendar_name' => 'Kalender ON4CRD', 'ics_filename' => 'on4crd-events-id.ics'],
    'ja' => ['title' => 'イベント', 'agenda_unavailable' => 'カレンダーはまだ利用できません。', 'export' => 'エクスポート', 'calendar_load_error' => 'インタラクティブカレンダーを読み込めませんでした。', 'event' => 'イベント', 'no_summary' => '概要はありません。', 'location_tbd' => '未定', 'today' => '今日', 'month' => '月', 'week' => '週', 'list' => '一覧', 'detail' => '詳細', 'start' => '開始', 'end' => '終了', 'location' => '場所', 'view_sheet' => '詳細を見る', 'external_link' => '外部リンク', 'no_event' => '現在、公開中のイベントはありません。', 'calendar_name' => 'ON4CRD カレンダー', 'ics_filename' => 'on4crd-events-ja.ics'],
    'ru' => ['title' => 'События', 'agenda_unavailable' => 'Календарь пока недоступен.', 'export' => 'Экспорт', 'calendar_load_error' => 'Не удалось загрузить интерактивный календарь.', 'event' => 'Событие', 'no_summary' => 'Краткое описание отсутствует.', 'location_tbd' => 'Будет уточнено', 'today' => 'Сегодня', 'month' => 'Месяц', 'week' => 'Неделя', 'list' => 'Список', 'detail' => 'Детали', 'start' => 'Начало', 'end' => 'Окончание', 'location' => 'Место', 'view_sheet' => 'Открыть детали', 'external_link' => 'Внешняя ссылка', 'no_event' => 'Опубликованных событий пока нет.', 'calendar_name' => 'Календарь ON4CRD', 'ics_filename' => 'on4crd-events-ru.ics'],
    'zh' => ['title' => '活动', 'agenda_unavailable' => '日历暂不可用。', 'export' => '导出', 'calendar_load_error' => '无法加载交互式日历。', 'event' => '活动', 'no_summary' => '暂无摘要。', 'location_tbd' => '待确认', 'today' => '今天', 'month' => '月', 'week' => '周', 'list' => '列表', 'detail' => '详情', 'start' => '开始', 'end' => '结束', 'location' => '地点', 'view_sheet' => '查看详情', 'external_link' => '外部链接', 'no_event' => '目前没有已发布的活动。', 'calendar_name' => 'ON4CRD 日历', 'ics_filename' => 'on4crd-events-zh.ics'],
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

if (!table_exists('events')) {
    echo render_layout('<div class="card"><h1>' . e($t['title']) . '</h1><p>' . e($t['agenda_unavailable']) . '</p></div>', $t['title']);
    return;
}

$rows = [];
try {
    $stmt = db()->query('SELECT id, slug, title, summary, description, start_at, end_at, location, external_url FROM events WHERE status = "published" ORDER BY start_at ASC, id ASC');
    $rows = $stmt->fetchAll();
} catch (Throwable) {
    $rows = [];
}

$icalEscape = static function (string $value): string {
    $value = str_replace("\r", '', $value);
    $value = str_replace("\n", '\\n', $value);
    $value = str_replace([',', ';'], ['\\,', '\\;'], $value);
    return $value;
};

if (strtolower((string) ($_GET['format'] ?? '')) === 'ics') {
    $host = parse_url(base_url('/'), PHP_URL_HOST) ?: 'on4crd.local';
    $lines = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//ON4CRD//Agenda//FR',
        'CALSCALE:GREGORIAN',
        'METHOD:PUBLISH',
        'X-WR-CALNAME:' . $icalEscape((string) $t['calendar_name']),
    ];

    foreach ($rows as $event) {
        $startTs = strtotime((string) $event['start_at']);
        $endTs = strtotime((string) $event['end_at']);
        if ($startTs === false || $endTs === false) {
            continue;
        }

        $description = trim((string) ($event['summary'] ?? ''));
        if ($description === '') {
            $description = trim(strip_tags((string) ($event['description'] ?? '')));
        }
        $eventUrl = trim((string) ($event['external_url'] ?? ''));
        if ($eventUrl === '') {
            $eventUrl = route_url('event_view', ['slug' => (string) $event['slug']]);
        }

        $lines[] = 'BEGIN:VEVENT';
        $lines[] = 'UID:event-' . (int) $event['id'] . '@' . $host;
        $lines[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z');
        $lines[] = 'DTSTART:' . gmdate('Ymd\THis\Z', $startTs);
        $lines[] = 'DTEND:' . gmdate('Ymd\THis\Z', $endTs);
        $lines[] = 'SUMMARY:' . $icalEscape((string) $event['title']);
        if ($description !== '') {
            $lines[] = 'DESCRIPTION:' . $icalEscape($description);
        }
        $location = trim((string) ($event['location'] ?? ''));
        if ($location !== '') {
            $lines[] = 'LOCATION:' . $icalEscape($location);
        }
        if ($eventUrl !== '') {
            $lines[] = 'URL:' . $icalEscape($eventUrl);
        }
        $lines[] = 'END:VEVENT';
    }

    $lines[] = 'END:VCALENDAR';

    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', (string) $t['ics_filename']) . '"');
    echo implode("\r\n", $lines) . "\r\n";
    exit;
}

$monthRaw = (string) ($_GET['ym'] ?? date('Y-m'));
if (!preg_match('/^\d{4}-\d{2}$/', $monthRaw)) {
    $monthRaw = date('Y-m');
}
$requestedView = (string) ($_GET['view'] ?? 'month');
/** @var 'month'|'week'|'list' $view */
$view = in_array($requestedView, ['month', 'week', 'list'], true)
    ? $requestedView
    : 'month';

$monthDate = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $monthRaw . '-01 00:00:00');
if (!$monthDate instanceof DateTimeImmutable) {
    $monthDate = new DateTimeImmutable('first day of this month midnight');
}

$weekRaw = (string) ($_GET['week'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $weekRaw)) {
    $weekRaw = date('Y-m-d');
}
$weekDate = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $weekRaw . ' 00:00:00');
if (!$weekDate instanceof DateTimeImmutable) {
    $weekDate = new DateTimeImmutable('today');
}

$eventCards = [];
foreach ($rows as $event) {
    $startAt = new DateTimeImmutable((string) $event['start_at']);
    $endAt = new DateTimeImmutable((string) $event['end_at']);

    $summary = trim((string) ($event['summary'] ?? ''));
    if ($summary === '') {
        $summary = trim(strip_tags((string) ($event['description'] ?? '')));
    }

    $eventCards[(int) $event['id']] = [
        'id' => (int) $event['id'],
        'title' => (string) $event['title'],
        'summary' => $summary,
        'startLabel' => $startAt->format('d/m/Y H:i'),
        'endLabel' => $endAt->format('d/m/Y H:i'),
        'location' => trim((string) ($event['location'] ?? '')),
        'detailUrl' => route_url('event_view', ['slug' => (string) $event['slug']]),
        'externalUrl' => trim((string) ($event['external_url'] ?? '')),
    ];

}

$defaultEvent = $eventCards !== [] ? reset($eventCards) : null;
$calendarView = match ($view) {
    'week' => 'timeGridWeek',
    'list' => 'listMonth',
    default => 'dayGridMonth',
};
$initialDate = $view === 'week' ? $weekDate->format('Y-m-d') : $monthDate->format('Y-m-d');

ob_start();
?>
<section class="events-layout">
    <article class="card events-calendar-card">
        <header class="events-toolbar events-toolbar-right">
            <div class="events-toolbar-actions">
                <a class="button events-export-button" href="<?= e(route_url('events', ['format' => 'ics'])) ?>"><?= e($t['export']) ?></a>
            </div>
        </header>

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@7.0.0-rc.2/skeleton.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@7.0.0-rc.2/themes/classic/theme.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@7.0.0-rc.2/themes/classic/palette.css">
        <div id="events-calendar" class="fullcalendar-theme"></div>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@7.0.0-rc.2/all.global.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@7.0.0-rc.2/themes/classic/global.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@7.0.0-rc.2/locales/fr.global.js"></script>
        <script nonce="<?= e(csp_nonce()) ?>">
            (() => {
                const calendarEl = document.getElementById('events-calendar');
                if (!calendarEl || !window.FullCalendar) {
                    calendarEl?.insertAdjacentHTML('beforeend', '<p class="help"><?= e($t['calendar_load_error']) ?></p>');
                    return;
                }
                const detail = {
                    title: document.getElementById('event-detail-title'),
                    summary: document.getElementById('event-detail-summary'),
                    start: document.getElementById('event-detail-start'),
                    end: document.getElementById('event-detail-end'),
                    location: document.getElementById('event-detail-location'),
                    link: document.getElementById('event-detail-link'),
                    external: document.getElementById('event-detail-external')
                };

                const updateDetails = (event) => {
                    const props = event.extendedProps || {};
                    if (detail.title) detail.title.textContent = event.title || <?= json_encode($t['event']) ?>;
                    if (detail.summary) detail.summary.textContent = props.summary || <?= json_encode($t['no_summary']) ?>;
                    if (detail.start) detail.start.textContent = props.startLabel || '';
                    if (detail.end) detail.end.textContent = props.endLabel || '';
                    if (detail.location) detail.location.textContent = props.location || <?= json_encode($t['location_tbd']) ?>;
                    if (detail.link) detail.link.setAttribute('href', event.url || '#');
                    if (detail.external) {
                        const externalUrl = props.externalUrl || '';
                        detail.external.setAttribute('href', externalUrl || '#');
                        detail.external.classList.toggle('is-hidden', !externalUrl);
                    }
                };
                const formatDate = (date) => {
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                };

                const calendar = new FullCalendar.Calendar(calendarEl, {
                    locale: <?= json_encode($locale) ?>,
                    firstDay: 1,
                    height: 'auto',
                    initialView: <?= json_encode($calendarView) ?>,
                    initialDate: <?= json_encode($initialDate) ?>,
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,listMonth'
                    },
                    buttonText: {
                        today: <?= json_encode($t['today']) ?>,
                        month: <?= json_encode($t['month']) ?>,
                        week: <?= json_encode($t['week']) ?>,
                        list: <?= json_encode($t['list']) ?>
                    },
                    events: <?= json_encode(route_url('events_feed')) ?>,
                    eventClick(info) {
                        info.jsEvent.preventDefault();
                        updateDetails(info.event);
                    },
                    datesSet(info) {
                        const params = new URLSearchParams(window.location.search);
                        const viewMap = {
                            dayGridMonth: 'month',
                            timeGridWeek: 'week',
                            listMonth: 'list'
                        };
                        const route = params.get('route') || 'events';
                        const currentView = viewMap[info.view.type] || 'month';
                        const monthAnchor = info.view.currentStart instanceof Date ? info.view.currentStart : info.start;
                        const weekAnchor = info.start instanceof Date ? info.start : monthAnchor;
                        params.set('route', route);
                        params.set('view', currentView);
                        params.set('ym', formatDate(monthAnchor).slice(0, 7));
                        params.set('week', formatDate(weekAnchor));
                        history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
                    }
                });
                calendar.render();
            })();
        </script>
    </article>

    <aside class="card events-detail-card" id="event-detail">
        <h2><?= e($t['detail']) ?></h2>
        <?php if (is_array($defaultEvent)): ?>
            <h3 id="event-detail-title"><?= e($defaultEvent['title']) ?></h3>
            <p id="event-detail-summary"><?= e($defaultEvent['summary'] !== '' ? $defaultEvent['summary'] : $t['no_summary']) ?></p>
            <dl>
                <dt><?= e($t['start']) ?></dt><dd id="event-detail-start"><?= e($defaultEvent['startLabel']) ?></dd>
                <dt><?= e($t['end']) ?></dt><dd id="event-detail-end"><?= e($defaultEvent['endLabel']) ?></dd>
                <dt><?= e($t['location']) ?></dt><dd id="event-detail-location"><?= e($defaultEvent['location'] !== '' ? $defaultEvent['location'] : $t['location_tbd']) ?></dd>
            </dl>
            <p class="events-detail-actions">
                <a id="event-detail-link" class="button" href="<?= e($defaultEvent['detailUrl']) ?>"><?= e($t['view_sheet']) ?></a>
                <a id="event-detail-external" class="button secondary <?= $defaultEvent['externalUrl'] === '' ? 'is-hidden' : '' ?>" href="<?= e($defaultEvent['externalUrl']) ?>" target="_blank" rel="noopener noreferrer"><?= e($t['external_link']) ?></a>
            </p>
        <?php else: ?>
            <p><?= e($t['no_event']) ?></p>
        <?php endif; ?>
    </aside>
</section>
<?php
echo render_layout((string) ob_get_clean(), $t['title']);
