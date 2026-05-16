<?php
declare(strict_types=1);

newsletter_ensure_tables();
$locale = current_locale();
$i18n = [
    'fr' => ['title' => 'Désabonnement newsletter', 'desc' => 'Confirmation de désabonnement à la newsletter ON4CRD.', 'heading' => 'Newsletter', 'ok' => 'Votre désabonnement a été pris en compte.', 'invalid' => 'Lien invalide ou déjà traité.'],
    'en' => ['title' => 'Newsletter unsubscribe', 'desc' => 'Unsubscribe confirmation for the ON4CRD newsletter.', 'heading' => 'Newsletter', 'ok' => 'Your unsubscribe request has been processed.', 'invalid' => 'Invalid or already used link.'],
    'de' => ['title' => 'Newsletter-Abmeldung', 'desc' => 'Bestätigung der Abmeldung vom ON4CRD-Newsletter.', 'heading' => 'Newsletter', 'ok' => 'Ihre Abmeldung wurde berücksichtigt.', 'invalid' => 'Ungültiger oder bereits verwendeter Link.'],
    'es' => ['title' => 'Baja del boletín', 'desc' => 'Confirmación de baja del boletín ON4CRD.', 'heading' => 'Boletín', 'ok' => 'Su baja ha sido procesada.', 'invalid' => 'Enlace no válido o ya usado.'],
    'it' => ['title' => 'Disiscrizione newsletter', 'desc' => 'Conferma di disiscrizione alla newsletter ON4CRD.', 'heading' => 'Newsletter', 'ok' => 'La disiscrizione è stata registrata.', 'invalid' => 'Link non valido o già utilizzato.'],
    'pt' => ['title' => 'Cancelar subscrição newsletter', 'desc' => 'Confirmação de cancelamento da newsletter ON4CRD.', 'heading' => 'Newsletter', 'ok' => 'O seu cancelamento foi processado.', 'invalid' => 'Ligação inválida ou já utilizada.'],
    'nl' => ['title' => 'Nieuwsbrief uitschrijven', 'desc' => 'Bevestiging van uitschrijving voor de ON4CRD-nieuwsbrief.', 'heading' => 'Nieuwsbrief', 'ok' => 'Je uitschrijving is verwerkt.', 'invalid' => 'Ongeldige of al gebruikte link.'],
    'ar' => ['title' => 'إلغاء الاشتراك في النشرة', 'desc' => 'تأكيد إلغاء الاشتراك في نشرة ON4CRD.', 'heading' => 'النشرة', 'ok' => 'تمت معالجة طلب إلغاء الاشتراك.', 'invalid' => 'رابط غير صالح أو مستخدم مسبقًا.'],
    'hi' => ['title' => 'न्यूज़लेटर सदस्यता समाप्त', 'desc' => 'ON4CRD न्यूज़लेटर सदस्यता समाप्ति की पुष्टि।', 'heading' => 'न्यूज़लेटर', 'ok' => 'आपकी सदस्यता समाप्ति अनुरोध प्रक्रिया पूरी हो गई है।', 'invalid' => 'अमान्य या पहले से उपयोग किया हुआ लिंक।'],
    'ja' => ['title' => 'ニュースレター配信停止', 'desc' => 'ON4CRDニュースレターの配信停止確認。', 'heading' => 'ニュースレター', 'ok' => '配信停止リクエストを処理しました。', 'invalid' => '無効または既に使用済みのリンクです。'],
    'zh' => ['title' => '取消订阅通讯', 'desc' => 'ON4CRD 通讯取消订阅确认。', 'heading' => '通讯', 'ok' => '您的退订请求已处理。', 'invalid' => '链接无效或已被使用。'],
    'bn' => ['title' => 'নিউজলেটার আনসাবস্ক্রাইব', 'desc' => 'ON4CRD নিউজলেটার আনসাবস্ক্রাইব নিশ্চিতকরণ।', 'heading' => 'নিউজলেটার', 'ok' => 'আপনার আনসাবস্ক্রাইব অনুরোধ প্রক্রিয়া সম্পন্ন হয়েছে।', 'invalid' => 'অবৈধ বা ইতোমধ্যে ব্যবহৃত লিংক।'],
    'ru' => ['title' => 'Отписка от рассылки', 'desc' => 'Подтверждение отписки от рассылки ON4CRD.', 'heading' => 'Рассылка', 'ok' => 'Ваш запрос на отписку обработан.', 'invalid' => 'Недействительная или уже использованная ссылка.'],
    'id' => ['title' => 'Berhenti berlangganan newsletter', 'desc' => 'Konfirmasi berhenti berlangganan newsletter ON4CRD.', 'heading' => 'Newsletter', 'ok' => 'Permintaan berhenti berlangganan Anda telah diproses.', 'invalid' => 'Tautan tidak valid atau sudah digunakan.'],
];
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};

set_page_meta([
    'title' => $t('title'),
    'description' => $t('desc'),
    'robots' => 'noindex,nofollow',
]);
$token = (string) ($_GET['token'] ?? '');
$ok = newsletter_unsubscribe_by_token($token);

$message = $ok
    ? '<div class="card"><h1>' . e($t('heading')) . '</h1><p>' . e($t('ok')) . '</p></div>'
    : '<div class="card"><h1>' . e($t('heading')) . '</h1><p>' . e($t('invalid')) . '</p></div>';

echo render_layout($message, $t('title'));
