<?php
declare(strict_types=1);

$lots = cache_remember('auction_public_lots_60_v1', 60, static fn(): array => auction_public_lots(60));
$locale = current_locale();
$i18n = [
    'fr' => [
        'meta_title' => 'Enchères',
        'meta_desc' => 'Enchères en cours, à venir et terminées.',
        'active_title' => 'Enchères en cours',
        'scheduled_title' => 'Enchères à venir',
        'closed_title' => 'Enchères terminées',
        'active_empty' => 'Aucune enchère en cours.',
        'scheduled_empty' => 'Aucune enchère à venir.',
        'closed_empty' => 'Aucune enchère terminée.',
        'lot' => 'lot',
        'lots' => 'lots',
        'default_summary' => 'Lot d’enchère du club.',
        'start' => 'Début',
        'end' => 'Fin',
        'step' => 'Pas',
        'view_lot' => 'Voir le lot',
        'page' => 'Page',
        'previous' => 'Précédent',
        'next' => 'Suivant',
    ],
    'en' => [
        'meta_title' => 'Auctions',
        'meta_desc' => 'Current, upcoming and closed auctions.',
        'active_title' => 'Active auctions',
        'scheduled_title' => 'Upcoming auctions',
        'closed_title' => 'Closed auctions',
        'active_empty' => 'No active auctions.',
        'scheduled_empty' => 'No upcoming auctions.',
        'closed_empty' => 'No closed auctions.',
        'lot' => 'lot',
        'lots' => 'lots',
        'default_summary' => 'Club auction lot.',
        'start' => 'Start',
        'end' => 'End',
        'step' => 'Step',
        'view_lot' => 'View lot',
        'page' => 'Page',
        'previous' => 'Previous',
        'next' => 'Next',
    ],
    'de' => [
        'meta_title' => 'Auktionen',
        'meta_desc' => 'Laufende, kommende und beendete Auktionen.',
        'active_title' => 'Laufende Auktionen',
        'scheduled_title' => 'Kommende Auktionen',
        'closed_title' => 'Beendete Auktionen',
        'active_empty' => 'Keine laufenden Auktionen.',
        'scheduled_empty' => 'Keine kommenden Auktionen.',
        'closed_empty' => 'Keine beendeten Auktionen.',
        'lot' => 'Los',
        'lots' => 'Lose',
        'default_summary' => 'Club-Auktionslos.',
        'start' => 'Start',
        'end' => 'Ende',
        'step' => 'Schritt',
        'view_lot' => 'Los anzeigen',
        'page' => 'Seite',
        'previous' => 'Zurück',
        'next' => 'Weiter',
    ],
    'nl' => [
        'meta_title' => 'Veilingen',
        'meta_desc' => 'Lopende, komende en afgelopen veilingen.',
        'active_title' => 'Lopende veilingen',
        'scheduled_title' => 'Komende veilingen',
        'closed_title' => 'Afgelopen veilingen',
        'active_empty' => 'Geen lopende veilingen.',
        'scheduled_empty' => 'Geen komende veilingen.',
        'closed_empty' => 'Geen afgelopen veilingen.',
        'lot' => 'lot',
        'lots' => 'loten',
        'default_summary' => 'Clubveiling-lot.',
        'start' => 'Start',
        'end' => 'Einde',
        'step' => 'Stap',
        'view_lot' => 'Bekijk lot',
        'page' => 'Pagina',
        'previous' => 'Vorige',
        'next' => 'Volgende',
    ],

    'es' => [
        'meta_title' => 'Subastas',
        'meta_desc' => 'Subastas en curso, próximas y cerradas.',
        'active_title' => 'Subastas en curso',
        'scheduled_title' => 'Próximas subastas',
        'closed_title' => 'Subastas cerradas',
        'active_empty' => 'No hay subastas en curso.',
        'scheduled_empty' => 'No hay subastas próximas.',
        'closed_empty' => 'No hay subastas cerradas.',
        'lot' => 'lote',
        'lots' => 'lotes',
        'default_summary' => 'Lote de subasta del club.',
        'start' => 'Inicio',
        'end' => 'Fin',
        'step' => 'Incremento',
        'view_lot' => 'Ver lote',
        'page' => 'Página',
        'previous' => 'Anterior',
        'next' => 'Siguiente',
    ],
    'it' => [
        'meta_title' => 'Aste',
        'meta_desc' => 'Aste attive, imminenti e chiuse.',
        'active_title' => 'Aste attive',
        'scheduled_title' => 'Aste imminenti',
        'closed_title' => 'Aste chiuse',
        'active_empty' => 'Nessuna asta attiva.',
        'scheduled_empty' => 'Nessuna asta imminente.',
        'closed_empty' => 'Nessuna asta chiusa.',
        'lot' => 'lotto',
        'lots' => 'lotti',
        'default_summary' => 'Lotto d’asta del club.',
        'start' => 'Inizio',
        'end' => 'Fine',
        'step' => 'Rialzo',
        'view_lot' => 'Vedi lotto',
        'page' => 'Pagina',
        'previous' => 'Precedente',
        'next' => 'Successiva',
    ],
    'pt' => [
        'meta_title' => 'Leilões',
        'meta_desc' => 'Leilões em curso, próximos e encerrados.',
        'active_title' => 'Leilões em curso',
        'scheduled_title' => 'Próximos leilões',
        'closed_title' => 'Leilões encerrados',
        'active_empty' => 'Não existem leilões em curso.',
        'scheduled_empty' => 'Não existem leilões próximos.',
        'closed_empty' => 'Não existem leilões encerrados.',
        'lot' => 'lote',
        'lots' => 'lotes',
        'default_summary' => 'Lote de leilão do clube.',
        'start' => 'Início',
        'end' => 'Fim',
        'step' => 'Incremento',
        'view_lot' => 'Ver lote',
        'page' => 'Página',
        'previous' => 'Anterior',
        'next' => 'Seguinte',
    ],
    'ar' => [
        'meta_title' => 'المزادات',
        'meta_desc' => 'مزادات جارية وقادمة ومنتهية.',
        'active_title' => 'المزادات الجارية',
        'scheduled_title' => 'المزادات القادمة',
        'closed_title' => 'المزادات المنتهية',
        'active_empty' => 'لا توجد مزادات جارية.',
        'scheduled_empty' => 'لا توجد مزادات قادمة.',
        'closed_empty' => 'لا توجد مزادات منتهية.',
        'lot' => 'قطعة',
        'lots' => 'قطع',
        'default_summary' => 'قطعة مزاد للنادي.',
        'start' => 'البداية',
        'end' => 'النهاية',
        'step' => 'الزيادة',
        'view_lot' => 'عرض القطعة',
        'page' => 'صفحة',
        'previous' => 'السابق',
        'next' => 'التالي',
    ],
    'hi' => [
        'meta_title' => 'नीलामियाँ',
        'meta_desc' => 'सक्रिय, आगामी और समाप्त नीलामियाँ।',
        'active_title' => 'सक्रिय नीलामियाँ',
        'scheduled_title' => 'आगामी नीलामियाँ',
        'closed_title' => 'समाप्त नीलामियाँ',
        'active_empty' => 'कोई सक्रिय नीलामी नहीं।',
        'scheduled_empty' => 'कोई आगामी नीलामी नहीं।',
        'closed_empty' => 'कोई समाप्त नीलामी नहीं।',
        'lot' => 'लॉट',
        'lots' => 'लॉट',
        'default_summary' => 'क्लब नीलामी लॉट।',
        'start' => 'शुरुआत',
        'end' => 'समाप्ति',
        'step' => 'वृद्धि',
        'view_lot' => 'लॉट देखें',
        'page' => 'पृष्ठ',
        'previous' => 'पिछला',
        'next' => 'अगला',
    ],
    'ja' => [
        'meta_title' => 'オークション',
        'meta_desc' => '開催中・予定・終了したオークション。',
        'active_title' => '開催中のオークション',
        'scheduled_title' => '今後のオークション',
        'closed_title' => '終了したオークション',
        'active_empty' => '開催中のオークションはありません。',
        'scheduled_empty' => '予定されているオークションはありません。',
        'closed_empty' => '終了したオークションはありません。',
        'lot' => 'ロット',
        'lots' => 'ロット',
        'default_summary' => 'クラブのオークションロット。',
        'start' => '開始',
        'end' => '終了',
        'step' => '増分',
        'view_lot' => 'ロットを見る',
        'page' => 'ページ',
        'previous' => '前へ',
        'next' => '次へ',
    ],
    'zh' => [
        'meta_title' => '拍卖',
        'meta_desc' => '进行中、即将开始和已结束的拍卖。',
        'active_title' => '进行中的拍卖',
        'scheduled_title' => '即将开始的拍卖',
        'closed_title' => '已结束的拍卖',
        'active_empty' => '没有进行中的拍卖。',
        'scheduled_empty' => '没有即将开始的拍卖。',
        'closed_empty' => '没有已结束的拍卖。',
        'lot' => '拍品',
        'lots' => '拍品',
        'default_summary' => '俱乐部拍卖拍品。',
        'start' => '开始',
        'end' => '结束',
        'step' => '加价幅度',
        'view_lot' => '查看拍品',
        'page' => '页',
        'previous' => '上一页',
        'next' => '下一页',
    ],
    'bn' => [
        'meta_title' => 'নিলাম',
        'meta_desc' => 'চলমান, আসন্ন এবং সমাপ্ত নিলাম।',
        'active_title' => 'চলমান নিলাম',
        'scheduled_title' => 'আসন্ন নিলাম',
        'closed_title' => 'সমাপ্ত নিলাম',
        'active_empty' => 'কোনো চলমান নিলাম নেই।',
        'scheduled_empty' => 'কোনো আসন্ন নিলাম নেই।',
        'closed_empty' => 'কোনো সমাপ্ত নিলাম নেই।',
        'lot' => 'লট',
        'lots' => 'লটসমূহ',
        'default_summary' => 'ক্লাব নিলাম লট।',
        'start' => 'শুরু',
        'end' => 'শেষ',
        'step' => 'ধাপ',
        'view_lot' => 'লট দেখুন',
        'page' => 'পৃষ্ঠা',
        'previous' => 'পূর্ববর্তী',
        'next' => 'পরবর্তী',
    ],
    'ru' => [
        'meta_title' => 'Аукционы',
        'meta_desc' => 'Текущие, предстоящие и завершённые аукционы.',
        'active_title' => 'Текущие аукционы',
        'scheduled_title' => 'Предстоящие аукционы',
        'closed_title' => 'Завершённые аукционы',
        'active_empty' => 'Нет текущих аукционов.',
        'scheduled_empty' => 'Нет предстоящих аукционов.',
        'closed_empty' => 'Нет завершённых аукционов.',
        'lot' => 'лот',
        'lots' => 'лоты',
        'default_summary' => 'Аукционный лот клуба.',
        'start' => 'Начало',
        'end' => 'Окончание',
        'step' => 'Шаг',
        'view_lot' => 'Открыть лот',
        'page' => 'Страница',
        'previous' => 'Назад',
        'next' => 'Далее',
    ],
    'id' => [
        'meta_title' => 'Lelang',
        'meta_desc' => 'Lelang aktif, mendatang, dan selesai.',
        'active_title' => 'Lelang aktif',
        'scheduled_title' => 'Lelang mendatang',
        'closed_title' => 'Lelang selesai',
        'active_empty' => 'Tidak ada lelang aktif.',
        'scheduled_empty' => 'Tidak ada lelang mendatang.',
        'closed_empty' => 'Tidak ada lelang yang selesai.',
        'lot' => 'lot',
        'lots' => 'lot',
        'default_summary' => 'Lot lelang klub.',
        'start' => 'Mulai',
        'end' => 'Berakhir',
        'step' => 'Kenaikan',
        'view_lot' => 'Lihat lot',
        'page' => 'Halaman',
        'previous' => 'Sebelumnya',
        'next' => 'Berikutnya',
    ],
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
set_page_meta([
    'title' => (string) $t['meta_title'],
    'description' => (string) $t['meta_desc'],
]);

$groupedLots = [
    'active' => [],
    'scheduled' => [],
    'closed' => [],
];
$perPage = 9;

foreach ($lots as $lot) {
    $runtime = auction_runtime_status($lot);
    if (isset($groupedLots[$runtime])) {
        $groupedLots[$runtime][] = $lot;
    }
}

$sectionPages = [
    'active' => max(1, (int) ($_GET['active_page'] ?? 1)),
    'scheduled' => max(1, (int) ($_GET['scheduled_page'] ?? 1)),
    'closed' => max(1, (int) ($_GET['closed_page'] ?? 1)),
];
$sectionMaxPages = [];
$pagedGroupedLots = [];
foreach ($groupedLots as $status => $items) {
    $max = max(1, (int) ceil(count($items) / $perPage));
    if ($sectionPages[$status] > $max) {
        $sectionPages[$status] = $max;
    }
    $sectionMaxPages[$status] = $max;
    $pagedGroupedLots[$status] = array_slice($items, ($sectionPages[$status] - 1) * $perPage, $perPage);
}

$sections = [
    'active' => ['title' => (string) $t['active_title'], 'empty' => (string) $t['active_empty']],
    'scheduled' => ['title' => (string) $t['scheduled_title'], 'empty' => (string) $t['scheduled_empty']],
    'closed' => ['title' => (string) $t['closed_title'], 'empty' => (string) $t['closed_empty']],
];

ob_start();
?>
<section class="stack auctions-page">
    <?php foreach ($sections as $status => $meta): ?>
        <section class="inner-card auctions-section auctions-section-<?= e($status) ?>">
            <div class="section-header">
                <h1><?= e($meta['title']) ?></h1>
                <span class="badge"><?= count($groupedLots[$status]) ?> <?= count($groupedLots[$status]) > 1 ? e((string) $t['lots']) : e((string) $t['lot']) ?></span>
            </div>
            <?php if ($groupedLots[$status] === []): ?>
                <div class="card empty-state"><p><?= e($meta['empty']) ?></p></div>
            <?php else: ?>
                <div class="grid-3">
                    <?php foreach ($pagedGroupedLots[$status] as $lot): ?>
                        <article class="card feature-card auction-lot-card">
                            <div class="section-header">
                                <h2><?= e((string) $lot['title']) ?></h2>
                                <strong class="price-tag"><?= e(format_price_eur(max((int) $lot['current_price_cents'], (int) $lot['starting_price_cents']))) ?></strong>
                            </div>
                            <p><?= e((string) ($lot['summary'] ?: (string) $t['default_summary'])) ?></p>
                            <ul class="list-clean list-spaced">
                                <li><span class="help"><?= e((string) $t['start']) ?> : <?= e(date('d/m/Y H:i', strtotime((string) $lot['starts_at']))) ?></span></li>
                                <li><span class="help"><?= e((string) $t['end']) ?> : <?= e(date('d/m/Y H:i', strtotime((string) $lot['ends_at']))) ?></span></li>
                                <li><span class="help"><?= e((string) $t['step']) ?> : <?= e(format_price_eur((int) $lot['min_increment_cents'])) ?></span></li>
                            </ul>
                            <div class="actions">
                                <a class="button" href="<?= e(route_url('auction_view', ['slug' => (string) $lot['slug']])) ?>"><?= e((string) $t['view_lot']) ?></a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <?php if ($sectionMaxPages[$status] > 1): ?>
                    <div class="actions mt-3">
                        <?php if ($sectionPages[$status] > 1): ?>
                            <a class="button secondary" href="<?= e(route_url('auctions', $sectionPages + [$status . '_page' => $sectionPages[$status] - 1])) ?>"><?= e((string) $t['previous']) ?></a>
                        <?php endif; ?>
                        <span class="pill"><?= e((string) $t['page']) ?> <?= $sectionPages[$status] ?> / <?= $sectionMaxPages[$status] ?></span>
                        <?php if ($sectionPages[$status] < $sectionMaxPages[$status]): ?>
                            <a class="button secondary" href="<?= e(route_url('auctions', $sectionPages + [$status . '_page' => $sectionPages[$status] + 1])) ?>"><?= e((string) $t['next']) ?></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</section>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['meta_title']);
