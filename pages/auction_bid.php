<?php
declare(strict_types=1);

$user = require_login();
$locale = current_locale();
$i18n = [
    'fr' => ['method_not_allowed' => 'Méthode non autorisée.', 'bid_saved' => 'Offre enregistrée.'],
    'en' => ['method_not_allowed' => 'Method not allowed.', 'bid_saved' => 'Bid submitted.'],
    'de' => ['method_not_allowed' => 'Methode nicht erlaubt.', 'bid_saved' => 'Gebot gespeichert.'],
    'es' => ['method_not_allowed' => 'Método no permitido.', 'bid_saved' => 'Oferta registrada.'],
    'it' => ['method_not_allowed' => 'Metodo non consentito.', 'bid_saved' => 'Offerta registrata.'],
    'pt' => ['method_not_allowed' => 'Método não permitido.', 'bid_saved' => 'Lance registado.'],
    'nl' => ['method_not_allowed' => 'Methode niet toegestaan.', 'bid_saved' => 'Bod opgeslagen.'],

    'ar' => ['method_not_allowed' => 'الطريقة غير مسموح بها.', 'bid_saved' => 'تم تسجيل العرض.'],
    'bn' => ['method_not_allowed' => 'এই পদ্ধতি অনুমোদিত নয়।', 'bid_saved' => 'বিড সংরক্ষণ করা হয়েছে।'],
    'hi' => ['method_not_allowed' => 'यह विधि अनुमत नहीं है।', 'bid_saved' => 'बोली सहेज दी गई है।'],
    'id' => ['method_not_allowed' => 'Metode tidak diizinkan.', 'bid_saved' => 'Tawaran disimpan.'],
    'ja' => ['method_not_allowed' => '許可されていないメソッドです。', 'bid_saved' => '入札を保存しました。'],
    'ru' => ['method_not_allowed' => 'Метод не разрешён.', 'bid_saved' => 'Ставка сохранена.'],
    'zh' => ['method_not_allowed' => '不允许此请求方法。', 'bid_saved' => '出价已保存。'],
];
$i18n = i18n_expand_supported_locales($i18n);
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit($t('method_not_allowed'));
}

try {
    verify_csrf();
    $lotId = (int) ($_POST['lot_id'] ?? 0);
    $amountCents = parse_price_to_cents((string) ($_POST['amount'] ?? '0'));
    place_auction_bid($lotId, (int) $user['id'], $amountCents);
    $lot = auction_lot_by_id($lotId);
    set_flash('success', $t('bid_saved'));
    redirect_url($lot ? route_url('auction_view', ['slug' => (string) $lot['slug']]) : route_url('auctions'));
} catch (Throwable $throwable) {
    set_flash('error', $throwable->getMessage());
    $lot = auction_lot_by_id((int) ($_POST['lot_id'] ?? 0));
    redirect_url($lot ? route_url('auction_view', ['slug' => (string) $lot['slug']]) : route_url('auctions'));
}
