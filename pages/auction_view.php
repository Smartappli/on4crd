<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['lot_not_found' => 'Lot introuvable', 'lot_not_exists' => 'Ce lot n’existe pas.', 'lot_not_public' => 'Ce lot n’est pas public.', 'default_desc' => 'Lot aux enchères ON4CRD.', 'default_summary' => 'Lot aux enchères du club.', 'current_price' => 'Prix actuel :', 'min_step' => 'Pas minimal :', 'end' => 'Fin :', 'reserve' => 'Prix de réserve :', 'reserve_met' => 'réserve atteinte', 'reserve_not_met' => 'réserve non atteinte', 'provisional_winner' => 'Gagnant provisoire :', 'bid' => 'Enchérir', 'inactive' => 'Le lot n’est pas actuellement en phase d’enchère active.', 'login_needed' => 'Il faut être connecté pour enchérir.', 'member_login' => 'Connexion membre', 'your_bid' => 'Votre offre', 'min_offer' => 'Offre minimale actuelle :', 'place_bid' => 'Placer l’offre', 'history' => 'Historique des offres', 'no_bids' => 'Aucune offre pour le moment.'],
    'en' => ['lot_not_found' => 'Lot not found', 'lot_not_exists' => 'This lot does not exist.', 'lot_not_public' => 'This lot is not public.', 'default_desc' => 'ON4CRD auction lot.', 'default_summary' => 'Club auction lot.', 'current_price' => 'Current price:', 'min_step' => 'Minimum step:', 'end' => 'Ends:', 'reserve' => 'Reserve price:', 'reserve_met' => 'reserve met', 'reserve_not_met' => 'reserve not met', 'provisional_winner' => 'Provisional winner:', 'bid' => 'Bid', 'inactive' => 'This lot is not currently in an active bidding phase.', 'login_needed' => 'You must be logged in to bid.', 'member_login' => 'Member login', 'your_bid' => 'Your bid', 'min_offer' => 'Current minimum offer:', 'place_bid' => 'Place bid', 'history' => 'Bid history', 'no_bids' => 'No bids yet.'],
    'de' => ['lot_not_found' => 'Los nicht gefunden', 'lot_not_exists' => 'Dieses Los existiert nicht.', 'lot_not_public' => 'Dieses Los ist nicht öffentlich.', 'default_desc' => 'ON4CRD-Auktionslos.', 'default_summary' => 'Auktionslos des Clubs.', 'current_price' => 'Aktueller Preis:', 'min_step' => 'Mindestschritt:', 'end' => 'Ende:', 'reserve' => 'Mindestpreis:', 'reserve_met' => 'Mindestpreis erreicht', 'reserve_not_met' => 'Mindestpreis nicht erreicht', 'provisional_winner' => 'Vorläufiger Gewinner:', 'bid' => 'Bieten', 'inactive' => 'Dieses Los befindet sich derzeit nicht in einer aktiven Bietphase.', 'login_needed' => 'Zum Bieten müssen Sie angemeldet sein.', 'member_login' => 'Mitglieder-Login', 'your_bid' => 'Ihr Gebot', 'min_offer' => 'Aktuelles Mindestgebot:', 'place_bid' => 'Gebot abgeben', 'history' => 'Gebotsverlauf', 'no_bids' => 'Noch keine Gebote.'],
    'nl' => ['lot_not_found' => 'Kavel niet gevonden', 'lot_not_exists' => 'Deze kavel bestaat niet.', 'lot_not_public' => 'Deze kavel is niet openbaar.', 'default_desc' => 'ON4CRD-veilingkavel.', 'default_summary' => 'Veilingkavel van de club.', 'current_price' => 'Huidige prijs:', 'min_step' => 'Minimale stap:', 'end' => 'Einde:', 'reserve' => 'Reserveprijs:', 'reserve_met' => 'reserve bereikt', 'reserve_not_met' => 'reserve niet bereikt', 'provisional_winner' => 'Voorlopige winnaar:', 'bid' => 'Bieden', 'inactive' => 'Deze kavel bevindt zich momenteel niet in een actieve biedfase.', 'login_needed' => 'Je moet ingelogd zijn om te bieden.', 'member_login' => 'Ledenlogin', 'your_bid' => 'Jouw bod', 'min_offer' => 'Huidig minimumbod:', 'place_bid' => 'Bod plaatsen', 'history' => 'Biedgeschiedenis', 'no_bids' => 'Nog geen biedingen.'],
    'pt' => ['lot_not_found' => 'Lote não encontrado', 'lot_not_exists' => 'Este lote não existe.', 'lot_not_public' => 'Este lote não é público.', 'default_desc' => 'Lote de leilão ON4CRD.', 'default_summary' => 'Lote de leilão do clube.', 'current_price' => 'Preço atual:', 'min_step' => 'Incremento mínimo:', 'end' => 'Fim:', 'reserve' => 'Preço de reserva:', 'reserve_met' => 'reserva atingida', 'reserve_not_met' => 'reserva não atingida', 'provisional_winner' => 'Vencedor provisório:', 'bid' => 'Licitar', 'inactive' => 'Este lote não está atualmente numa fase ativa de licitação.', 'login_needed' => 'Tem de iniciar sessão para licitar.', 'member_login' => 'Login de membro', 'your_bid' => 'A sua oferta', 'min_offer' => 'Oferta mínima atual:', 'place_bid' => 'Fazer oferta', 'history' => 'Histórico de ofertas', 'no_bids' => 'Ainda não há ofertas.'],
    'it' => ['lot_not_found' => 'Lotto non trovato', 'lot_not_exists' => 'Questo lotto non esiste.', 'lot_not_public' => 'Questo lotto non è pubblico.', 'default_desc' => 'Lotto d’asta ON4CRD.', 'default_summary' => 'Lotto d’asta del club.', 'current_price' => 'Prezzo attuale:', 'min_step' => 'Rialzo minimo:', 'end' => 'Fine:', 'reserve' => 'Prezzo di riserva:', 'reserve_met' => 'riserva raggiunta', 'reserve_not_met' => 'riserva non raggiunta', 'provisional_winner' => 'Vincitore provvisorio:', 'bid' => 'Offri', 'inactive' => 'Questo lotto non è attualmente in una fase d’asta attiva.', 'login_needed' => 'Devi essere connesso per fare un’offerta.', 'member_login' => 'Accesso membri', 'your_bid' => 'La tua offerta', 'min_offer' => 'Offerta minima attuale:', 'place_bid' => 'Invia offerta', 'history' => 'Storico offerte', 'no_bids' => 'Nessuna offerta per ora.'],
    'es' => ['lot_not_found' => 'Lote no encontrado', 'lot_not_exists' => 'Este lote no existe.', 'lot_not_public' => 'Este lote no es público.', 'default_desc' => 'Lote de subasta ON4CRD.', 'default_summary' => 'Lote de subasta del club.', 'current_price' => 'Precio actual:', 'min_step' => 'Incremento mínimo:', 'end' => 'Fin:', 'reserve' => 'Precio de reserva:', 'reserve_met' => 'reserva alcanzada', 'reserve_not_met' => 'reserva no alcanzada', 'provisional_winner' => 'Ganador provisional:', 'bid' => 'Pujar', 'inactive' => 'Este lote no está actualmente en una fase activa de puja.', 'login_needed' => 'Debes iniciar sesión para pujar.', 'member_login' => 'Inicio de sesión miembro', 'your_bid' => 'Tu oferta', 'min_offer' => 'Oferta mínima actual:', 'place_bid' => 'Realizar oferta', 'history' => 'Historial de ofertas', 'no_bids' => 'Aún no hay ofertas.'],
    'ar' => ['lot_not_found' => 'تعذر العثور على القطعة', 'lot_not_exists' => 'هذه القطعة غير موجودة.', 'lot_not_public' => 'هذه القطعة ليست عامة.', 'default_desc' => 'قطعة مزاد ON4CRD.', 'default_summary' => 'قطعة مزاد للنادي.', 'current_price' => 'السعر الحالي:', 'min_step' => 'الحد الأدنى للزيادة:', 'end' => 'النهاية:', 'reserve' => 'سعر الاحتياط:', 'reserve_met' => 'تم بلوغ سعر الاحتياط', 'reserve_not_met' => 'لم يتم بلوغ سعر الاحتياط', 'provisional_winner' => 'الفائز المؤقت:', 'bid' => 'المزايدة', 'inactive' => 'هذه القطعة ليست في مرحلة مزايدة نشطة حاليًا.', 'login_needed' => 'يجب تسجيل الدخول للمزايدة.', 'member_login' => 'دخول الأعضاء', 'your_bid' => 'عرضك', 'min_offer' => 'الحد الأدنى الحالي للعرض:', 'place_bid' => 'إرسال العرض', 'history' => 'سجل العروض', 'no_bids' => 'لا توجد عروض بعد.'],
    'hi' => ['lot_not_found' => 'लॉट नहीं मिला', 'lot_not_exists' => 'यह लॉट मौजूद नहीं है।', 'lot_not_public' => 'यह लॉट सार्वजनिक नहीं है।', 'default_desc' => 'ON4CRD नीलामी लॉट।', 'default_summary' => 'क्लब नीलामी लॉट।', 'current_price' => 'वर्तमान मूल्य:', 'min_step' => 'न्यूनतम वृद्धि:', 'end' => 'समाप्ति:', 'reserve' => 'रिज़र्व मूल्य:', 'reserve_met' => 'रिज़र्व पूरा हुआ', 'reserve_not_met' => 'रिज़र्व पूरा नहीं हुआ', 'provisional_winner' => 'अस्थायी विजेता:', 'bid' => 'बोली लगाएँ', 'inactive' => 'यह लॉट फिलहाल सक्रिय बोली चरण में नहीं है।', 'login_needed' => 'बोली लगाने के लिए लॉगिन आवश्यक है।', 'member_login' => 'सदस्य लॉगिन', 'your_bid' => 'आपकी बोली', 'min_offer' => 'वर्तमान न्यूनतम बोली:', 'place_bid' => 'बोली जमा करें', 'history' => 'बोली इतिहास', 'no_bids' => 'अभी तक कोई बोली नहीं।'],
    'ja' => ['lot_not_found' => 'ロットが見つかりません', 'lot_not_exists' => 'このロットは存在しません。', 'lot_not_public' => 'このロットは公開されていません。', 'default_desc' => 'ON4CRDオークションロット。', 'default_summary' => 'クラブのオークションロット。', 'current_price' => '現在価格:', 'min_step' => '最小入札幅:', 'end' => '終了:', 'reserve' => 'リザーブ価格:', 'reserve_met' => 'リザーブ到達', 'reserve_not_met' => 'リザーブ未到達', 'provisional_winner' => '暫定落札者:', 'bid' => '入札', 'inactive' => 'このロットは現在アクティブな入札段階ではありません。', 'login_needed' => '入札するにはログインが必要です。', 'member_login' => '会員ログイン', 'your_bid' => 'あなたの入札額', 'min_offer' => '現在の最低入札額:', 'place_bid' => '入札する', 'history' => '入札履歴', 'no_bids' => 'まだ入札はありません。'],
    'zh' => ['lot_not_found' => '未找到拍品', 'lot_not_exists' => '该拍品不存在。', 'lot_not_public' => '该拍品未公开。', 'default_desc' => 'ON4CRD 拍卖拍品。', 'default_summary' => '俱乐部拍卖拍品。', 'current_price' => '当前价格：', 'min_step' => '最小加价：', 'end' => '结束时间：', 'reserve' => '保留价：', 'reserve_met' => '已达到保留价', 'reserve_not_met' => '未达到保留价', 'provisional_winner' => '暂定中标者：', 'bid' => '出价', 'inactive' => '该拍品当前不在有效竞价阶段。', 'login_needed' => '您必须登录后才能出价。', 'member_login' => '会员登录', 'your_bid' => '您的出价', 'min_offer' => '当前最低出价：', 'place_bid' => '提交出价', 'history' => '出价记录', 'no_bids' => '暂无出价。'],
    'bn' => ['lot_not_found' => 'লট পাওয়া যায়নি', 'lot_not_exists' => 'এই লটটি বিদ্যমান নয়।', 'lot_not_public' => 'এই লটটি সর্বসাধারণের জন্য নয়।', 'default_desc' => 'ON4CRD নিলাম লট।', 'default_summary' => 'ক্লাব নিলাম লট।', 'current_price' => 'বর্তমান মূল্য:', 'min_step' => 'ন্যূনতম ধাপ:', 'end' => 'শেষ:', 'reserve' => 'রিজার্ভ মূল্য:', 'reserve_met' => 'রিজার্ভ পূরণ হয়েছে', 'reserve_not_met' => 'রিজার্ভ পূরণ হয়নি', 'provisional_winner' => 'অস্থায়ী বিজয়ী:', 'bid' => 'বিড করুন', 'inactive' => 'এই লটটি বর্তমানে সক্রিয় বিডিং পর্যায়ে নেই।', 'login_needed' => 'বিড করতে লগইন করতে হবে।', 'member_login' => 'সদস্য লগইন', 'your_bid' => 'আপনার বিড', 'min_offer' => 'বর্তমান ন্যূনতম অফার:', 'place_bid' => 'বিড দিন', 'history' => 'বিড ইতিহাস', 'no_bids' => 'এখনও কোনো বিড নেই।'],
    'ru' => ['lot_not_found' => 'Лот не найден', 'lot_not_exists' => 'Этот лот не существует.', 'lot_not_public' => 'Этот лот не является публичным.', 'default_desc' => 'Лот аукциона ON4CRD.', 'default_summary' => 'Аукционный лот клуба.', 'current_price' => 'Текущая цена:', 'min_step' => 'Минимальный шаг:', 'end' => 'Окончание:', 'reserve' => 'Резервная цена:', 'reserve_met' => 'резерв достигнут', 'reserve_not_met' => 'резерв не достигнут', 'provisional_winner' => 'Предварительный победитель:', 'bid' => 'Сделать ставку', 'inactive' => 'Этот лот сейчас не находится в активной фазе торгов.', 'login_needed' => 'Чтобы сделать ставку, необходимо войти.', 'member_login' => 'Вход для участников', 'your_bid' => 'Ваша ставка', 'min_offer' => 'Текущая минимальная ставка:', 'place_bid' => 'Отправить ставку', 'history' => 'История ставок', 'no_bids' => 'Ставок пока нет.'],
    'id' => ['lot_not_found' => 'Lot tidak ditemukan', 'lot_not_exists' => 'Lot ini tidak ada.', 'lot_not_public' => 'Lot ini tidak publik.', 'default_desc' => 'Lot lelang ON4CRD.', 'default_summary' => 'Lot lelang klub.', 'current_price' => 'Harga saat ini:', 'min_step' => 'Kenaikan minimum:', 'end' => 'Berakhir:', 'reserve' => 'Harga cadangan:', 'reserve_met' => 'harga cadangan tercapai', 'reserve_not_met' => 'harga cadangan belum tercapai', 'provisional_winner' => 'Pemenang sementara:', 'bid' => 'Tawar', 'inactive' => 'Lot ini saat ini tidak berada dalam fase penawaran aktif.', 'login_needed' => 'Anda harus masuk untuk menawar.', 'member_login' => 'Login anggota', 'your_bid' => 'Tawaran Anda', 'min_offer' => 'Tawaran minimum saat ini:', 'place_bid' => 'Kirim tawaran', 'history' => 'Riwayat tawaran', 'no_bids' => 'Belum ada tawaran.'],
];
$t = $i18n[$locale] ?? $i18n['fr'];

$slug = trim((string) ($_GET['slug'] ?? ''));
$lot = $slug !== '' ? auction_lot_by_slug($slug) : null;
if ($lot === null) {
    http_response_code(404);
    echo render_layout('<div class="card"><h1>' . e((string) $t['lot_not_found']) . '</h1><p>' . e((string) $t['lot_not_exists']) . '</p></div>', (string) $t['lot_not_found']);
    return;
}

if (in_array((string) ($lot['status'] ?? ''), ['draft', 'cancelled'], true) && !has_permission('auctions.manage')) {
    http_response_code(404);
    echo render_layout('<div class="card"><h1>' . e((string) $t['lot_not_found']) . '</h1><p>' . e((string) $t['lot_not_public']) . '</p></div>', (string) $t['lot_not_found']);
    return;
}

$runtime = auction_runtime_status($lot);
$bids = auction_bids_for_lot((int) $lot['id'], 20);
$minimumBid = auction_minimum_bid_cents($lot);
$reserveCents = (int) ($lot['reserve_price_cents'] ?? 0);
$displayPriceCents = max((int) $lot['current_price_cents'], (int) $lot['starting_price_cents']);
$reserveReached = auction_reserve_met($lot, $displayPriceCents);
set_page_meta([
    'title' => (string) $lot['title'],
    'description' => (string) ($lot['summary'] ?: (string) $t['default_desc']),
]);

ob_start();
?>
<div class="split auction-detail-layout">
    <article class="card auction-detail-main">
        <div class="badge <?= $runtime === 'closed' ? 'muted' : '' ?>"><?= e(auction_status_label($runtime)) ?></div>
        <h1><?= e((string) $lot['title']) ?></h1>
        <p class="hero-lead"><?= e((string) ($lot['summary'] ?: (string) $t['default_summary'])) ?></p>
        <div class="catalog auction-meta-list">
            <span class="pill"><?= e((string) $t['current_price']) ?> <?= e(format_price_eur($displayPriceCents)) ?></span>
            <span class="pill"><?= e((string) $t['min_step']) ?> <?= e(format_price_eur((int) $lot['min_increment_cents'])) ?></span>
            <span class="pill"><?= e((string) $t['end']) ?> <?= e(date('d/m/Y H:i', strtotime((string) $lot['ends_at']))) ?></span>
            <?php if ($reserveCents > 0): ?>
                <span class="pill"><?= e((string) $t['reserve']) ?> <?= e(format_price_eur($reserveCents)) ?> (<?= e((string) ($reserveReached ? $t['reserve_met'] : $t['reserve_not_met'])) ?>)</span>
            <?php endif; ?>
        </div>
        <div class="inner-card"><?= sanitize_rich_html((string) ($lot['description'] ?? '')) ?></div>
        <?php if ($runtime === 'closed' && !empty($lot['winner_callsign'])): ?>
            <p><strong><?= e((string) $t['provisional_winner']) ?></strong> <?= e((string) $lot['winner_callsign']) ?></p>
        <?php endif; ?>
    </article>
    <aside class="card auction-detail-side">
        <h2><?= e((string) $t['bid']) ?></h2>
        <?php if ($runtime !== 'active'): ?>
            <p class="help"><?= e((string) $t['inactive']) ?></p>
        <?php elseif (!current_user()): ?>
            <p class="help"><?= e((string) $t['login_needed']) ?></p>
            <p><a class="button" href="<?= e(route_url('login')) ?>"><?= e((string) $t['member_login']) ?></a></p>
        <?php else: ?>
            <form method="post" action="<?= e(route_url('auction_bid')) ?>" class="stack">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="lot_id" value="<?= (int) $lot['id'] ?>">
                <label><?= e((string) $t['your_bid']) ?>
                    <input type="text" name="amount" value="<?= e(number_format($minimumBid / 100, 2, ',', '')) ?>">
                </label>
                <p class="help"><?= e((string) $t['min_offer']) ?> <?= e(format_price_eur($minimumBid)) ?></p>
                <button class="button"><?= e((string) $t['place_bid']) ?></button>
            </form>
        <?php endif; ?>

        <div class="inner-card">
            <h3><?= e((string) $t['history']) ?></h3>
            <?php if ($bids === []): ?>
                <p class="help"><?= e((string) $t['no_bids']) ?></p>
            <?php else: ?>
                <ul class="list-clean list-spaced">
                    <?php foreach ($bids as $bid): ?>
                        <li><strong><?= e((string) $bid['callsign']) ?></strong><span class="help"><?= e(format_price_eur((int) $bid['amount_cents'])) ?> — <?= e(date('d/m/Y H:i', strtotime((string) $bid['created_at']))) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </aside>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $lot['title']);
