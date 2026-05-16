<?php
declare(strict_types=1);

$user = require_login();
newsletter_ensure_tables();
$locale = current_locale();
$i18n = [
    'fr' => ['meta_title' => 'Préférences newsletter', 'meta_desc' => 'Gestion de votre abonnement newsletter ON4CRD.', 'err_invalid_email' => 'Adresse email invalide.', 'ok_subscribed' => 'Vous êtes abonné à la newsletter.', 'err_no_sub' => 'Aucun abonnement trouvé.', 'ok_unsubscribed' => 'Vous êtes désabonné de la newsletter.', 'title' => 'Préférences newsletter', 'intro' => 'Abonnez-vous pour recevoir les actualités du radio-club par email. Vous pouvez vous désabonner à tout moment.', 'status' => 'Statut actuel :', 'subscribed' => 'abonné', 'not_subscribed' => 'non abonné', 'unsubscribe' => 'Se désabonner', 'email_label' => 'Email de contact', 'subscribe' => "S'abonner", 'layout_title' => 'Newsletter'],
    'en' => ['meta_title' => 'Newsletter preferences', 'meta_desc' => 'Manage your ON4CRD newsletter subscription.', 'err_invalid_email' => 'Invalid email address.', 'ok_subscribed' => 'You are subscribed to the newsletter.', 'err_no_sub' => 'No subscription found.', 'ok_unsubscribed' => 'You are unsubscribed from the newsletter.', 'title' => 'Newsletter preferences', 'intro' => 'Subscribe to receive radio-club news by email. You can unsubscribe at any time.', 'status' => 'Current status:', 'subscribed' => 'subscribed', 'not_subscribed' => 'not subscribed', 'unsubscribe' => 'Unsubscribe', 'email_label' => 'Contact email', 'subscribe' => 'Subscribe', 'layout_title' => 'Newsletter'],
    'de' => ['meta_title' => 'Newsletter-Einstellungen', 'meta_desc' => 'Verwalten Sie Ihr ON4CRD-Newsletter-Abonnement.', 'err_invalid_email' => 'Ungültige E-Mail-Adresse.', 'ok_subscribed' => 'Sie sind für den Newsletter angemeldet.', 'err_no_sub' => 'Kein Abonnement gefunden.', 'ok_unsubscribed' => 'Sie sind vom Newsletter abgemeldet.', 'title' => 'Newsletter-Einstellungen', 'intro' => 'Abonnieren Sie, um Neuigkeiten des Radioclubs per E-Mail zu erhalten. Sie können sich jederzeit abmelden.', 'status' => 'Aktueller Status:', 'subscribed' => 'abonniert', 'not_subscribed' => 'nicht abonniert', 'unsubscribe' => 'Abmelden', 'email_label' => 'Kontakt-E-Mail', 'subscribe' => 'Abonnieren', 'layout_title' => 'Newsletter'],
    'es' => ['meta_title' => 'Preferencias del boletín', 'meta_desc' => 'Gestione su suscripción al boletín ON4CRD.', 'err_invalid_email' => 'Correo electrónico no válido.', 'ok_subscribed' => 'Está suscrito al boletín.', 'err_no_sub' => 'No se encontró ninguna suscripción.', 'ok_unsubscribed' => 'Se ha dado de baja del boletín.', 'title' => 'Preferencias del boletín', 'intro' => 'Suscríbase para recibir noticias del radioclub por correo. Puede darse de baja en cualquier momento.', 'status' => 'Estado actual:', 'subscribed' => 'suscrito', 'not_subscribed' => 'no suscrito', 'unsubscribe' => 'Darse de baja', 'email_label' => 'Correo de contacto', 'subscribe' => 'Suscribirse', 'layout_title' => 'Boletín'],
    'it' => ['meta_title' => 'Preferenze newsletter', 'meta_desc' => 'Gestisci il tuo abbonamento newsletter ON4CRD.', 'err_invalid_email' => 'Indirizzo email non valido.', 'ok_subscribed' => 'Sei iscritto alla newsletter.', 'err_no_sub' => 'Nessuna iscrizione trovata.', 'ok_unsubscribed' => 'Ti sei disiscritto dalla newsletter.', 'title' => 'Preferenze newsletter', 'intro' => 'Iscriviti per ricevere le notizie del radioclub via email. Puoi disiscriverti in qualsiasi momento.', 'status' => 'Stato attuale:', 'subscribed' => 'iscritto', 'not_subscribed' => 'non iscritto', 'unsubscribe' => 'Disiscriviti', 'email_label' => 'Email di contatto', 'subscribe' => 'Iscriviti', 'layout_title' => 'Newsletter'],
    'pt' => ['meta_title' => 'Preferências da newsletter', 'meta_desc' => 'Gira a sua subscrição da newsletter ON4CRD.', 'err_invalid_email' => 'Email inválido.', 'ok_subscribed' => 'Está subscrito na newsletter.', 'err_no_sub' => 'Nenhuma subscrição encontrada.', 'ok_unsubscribed' => 'Foi removido da newsletter.', 'title' => 'Preferências da newsletter', 'intro' => 'Subscreva para receber notícias do radioclube por email. Pode cancelar a qualquer momento.', 'status' => 'Estado atual:', 'subscribed' => 'subscrito', 'not_subscribed' => 'não subscrito', 'unsubscribe' => 'Cancelar subscrição', 'email_label' => 'Email de contacto', 'subscribe' => 'Subscrever', 'layout_title' => 'Newsletter'],
    'nl' => ['meta_title' => 'Nieuwsbriefvoorkeuren', 'meta_desc' => 'Beheer je ON4CRD-nieuwsbriefabonnement.', 'err_invalid_email' => 'Ongeldig e-mailadres.', 'ok_subscribed' => 'Je bent geabonneerd op de nieuwsbrief.', 'err_no_sub' => 'Geen abonnement gevonden.', 'ok_unsubscribed' => 'Je bent uitgeschreven voor de nieuwsbrief.', 'title' => 'Nieuwsbriefvoorkeuren', 'intro' => 'Schrijf je in om nieuws van de radioclub per e-mail te ontvangen. Je kunt je op elk moment uitschrijven.', 'status' => 'Huidige status:', 'subscribed' => 'geabonneerd', 'not_subscribed' => 'niet geabonneerd', 'unsubscribe' => 'Uitschrijven', 'email_label' => 'Contact e-mail', 'subscribe' => 'Inschrijven', 'layout_title' => 'Nieuwsbrief'],
    'ar' => ['meta_title' => 'تفضيلات النشرة', 'meta_desc' => 'إدارة اشتراكك في نشرة ON4CRD.', 'err_invalid_email' => 'بريد إلكتروني غير صالح.', 'ok_subscribed' => 'أنت مشترك في النشرة.', 'err_no_sub' => 'لم يتم العثور على اشتراك.', 'ok_unsubscribed' => 'تم إلغاء اشتراكك في النشرة.', 'title' => 'تفضيلات النشرة', 'intro' => 'اشترك لتلقي أخبار نادي الراديو عبر البريد الإلكتروني. يمكنك إلغاء الاشتراك في أي وقت.', 'status' => 'الحالة الحالية:', 'subscribed' => 'مشترك', 'not_subscribed' => 'غير مشترك', 'unsubscribe' => 'إلغاء الاشتراك', 'email_label' => 'بريد التواصل', 'subscribe' => 'اشتراك', 'layout_title' => 'النشرة'],
    'hi' => ['meta_title' => 'न्यूज़लेटर प्राथमिकताएँ', 'meta_desc' => 'अपने ON4CRD न्यूज़लेटर सदस्यता का प्रबंधन करें।', 'err_invalid_email' => 'अमान्य ईमेल।', 'ok_subscribed' => 'आप न्यूज़लेटर के सदस्य हैं।', 'err_no_sub' => 'कोई सदस्यता नहीं मिली।', 'ok_unsubscribed' => 'आपकी न्यूज़लेटर सदस्यता समाप्त कर दी गई है।', 'title' => 'न्यूज़लेटर प्राथमिकताएँ', 'intro' => 'रेडियो क्लब की खबरें ईमेल से पाने के लिए सदस्यता लें। आप कभी भी सदस्यता समाप्त कर सकते हैं।', 'status' => 'वर्तमान स्थिति:', 'subscribed' => 'सदस्य', 'not_subscribed' => 'सदस्य नहीं', 'unsubscribe' => 'सदस्यता समाप्त करें', 'email_label' => 'संपर्क ईमेल', 'subscribe' => 'सदस्यता लें', 'layout_title' => 'न्यूज़लेटर'],
    'ja' => ['meta_title' => 'ニュースレター設定', 'meta_desc' => 'ON4CRDニュースレターの購読設定を管理します。', 'err_invalid_email' => '無効なメールアドレスです。', 'ok_subscribed' => 'ニュースレターを購読しました。', 'err_no_sub' => '購読が見つかりません。', 'ok_unsubscribed' => 'ニュースレターの購読を解除しました。', 'title' => 'ニュースレター設定', 'intro' => 'ラジオクラブのニュースをメールで受け取るには購読してください。いつでも解除できます。', 'status' => '現在の状態:', 'subscribed' => '購読中', 'not_subscribed' => '未購読', 'unsubscribe' => '購読解除', 'email_label' => '連絡先メール', 'subscribe' => '購読する', 'layout_title' => 'ニュースレター'],
    'zh' => ['meta_title' => '通讯偏好设置', 'meta_desc' => '管理您的 ON4CRD 通讯订阅。', 'err_invalid_email' => '邮箱无效。', 'ok_subscribed' => '您已订阅通讯。', 'err_no_sub' => '未找到订阅记录。', 'ok_unsubscribed' => '您已取消订阅通讯。', 'title' => '通讯偏好设置', 'intro' => '订阅后可通过邮件接收无线电俱乐部新闻。您可随时退订。', 'status' => '当前状态：', 'subscribed' => '已订阅', 'not_subscribed' => '未订阅', 'unsubscribe' => '取消订阅', 'email_label' => '联系邮箱', 'subscribe' => '订阅', 'layout_title' => '通讯'],
    'bn' => ['meta_title' => 'নিউজলেটার পছন্দসমূহ', 'meta_desc' => 'আপনার ON4CRD নিউজলেটার সাবস্ক্রিপশন পরিচালনা করুন।', 'err_invalid_email' => 'অবৈধ ইমেইল।', 'ok_subscribed' => 'আপনি নিউজলেটারে সাবস্ক্রাইব করেছেন।', 'err_no_sub' => 'কোনো সাবস্ক্রিপশন পাওয়া যায়নি।', 'ok_unsubscribed' => 'আপনার নিউজলেটার সাবস্ক্রিপশন বাতিল হয়েছে।', 'title' => 'নিউজলেটার পছন্দসমূহ', 'intro' => 'রেডিও ক্লাবের খবর ইমেইলে পেতে সাবস্ক্রাইব করুন। আপনি যেকোনো সময় আনসাবস্ক্রাইব করতে পারবেন।', 'status' => 'বর্তমান অবস্থা:', 'subscribed' => 'সাবস্ক্রাইবড', 'not_subscribed' => 'সাবস্ক্রাইবড নয়', 'unsubscribe' => 'আনসাবস্ক্রাইব', 'email_label' => 'যোগাযোগের ইমেইল', 'subscribe' => 'সাবস্ক্রাইব', 'layout_title' => 'নিউজলেটার'],
    'ru' => ['meta_title' => 'Настройки рассылки', 'meta_desc' => 'Управляйте подпиской на рассылку ON4CRD.', 'err_invalid_email' => 'Неверный email.', 'ok_subscribed' => 'Вы подписаны на рассылку.', 'err_no_sub' => 'Подписка не найдена.', 'ok_unsubscribed' => 'Вы отписались от рассылки.', 'title' => 'Настройки рассылки', 'intro' => 'Подпишитесь, чтобы получать новости радиоклуба по email. Вы можете отписаться в любое время.', 'status' => 'Текущий статус:', 'subscribed' => 'подписан', 'not_subscribed' => 'не подписан', 'unsubscribe' => 'Отписаться', 'email_label' => 'Контактный email', 'subscribe' => 'Подписаться', 'layout_title' => 'Рассылка'],
    'id' => ['meta_title' => 'Preferensi newsletter', 'meta_desc' => 'Kelola langganan newsletter ON4CRD Anda.', 'err_invalid_email' => 'Email tidak valid.', 'ok_subscribed' => 'Anda berlangganan newsletter.', 'err_no_sub' => 'Langganan tidak ditemukan.', 'ok_unsubscribed' => 'Anda berhenti berlangganan newsletter.', 'title' => 'Preferensi newsletter', 'intro' => 'Berlangganan untuk menerima berita klub radio melalui email. Anda dapat berhenti berlangganan kapan saja.', 'status' => 'Status saat ini:', 'subscribed' => 'berlangganan', 'not_subscribed' => 'tidak berlangganan', 'unsubscribe' => 'Berhenti berlangganan', 'email_label' => 'Email kontak', 'subscribe' => 'Berlangganan', 'layout_title' => 'Newsletter'],
];
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};

set_page_meta([
    'title' => $t('meta_title'),
    'description' => $t('meta_desc'),
    'robots' => 'noindex,nofollow',
]);

$memberId = (int) ($user['id'] ?? 0);
$memberEmail = newsletter_normalize_email((string) ($user['email'] ?? ''));
$current = newsletter_subscriber_for_member($memberId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'subscribe') {
            $email = newsletter_normalize_email((string) ($_POST['email'] ?? $memberEmail));
            if ($email === '') {
                throw new RuntimeException($t('err_invalid_email'));
            }
            newsletter_upsert_subscriber($email, $memberId, 'member');
            set_flash('success', $t('ok_subscribed'));
        } elseif ($action === 'unsubscribe') {
            if ($current === null) {
                throw new RuntimeException($t('err_no_sub'));
            }
            newsletter_set_subscriber_status((int) $current['id'], 'unsubscribed');
            set_flash('success', $t('ok_unsubscribed'));
        }
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
    }

    redirect('newsletter');
}

$current = newsletter_subscriber_for_member($memberId);
$isSubscribed = $current !== null && (string) ($current['status'] ?? '') === 'active';

ob_start();
?>
<div class="card">
    <h1><?= e($t('title')) ?></h1>
    <p><?= e($t('intro')) ?></p>

    <?php if ($isSubscribed): ?>
        <p><strong><?= e($t('status')) ?></strong> <?= e($t('subscribed')) ?> (<?= e((string) $current['email']) ?>)</p>
        <form method="post">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="unsubscribe">
            <button class="button danger"><?= e($t('unsubscribe')) ?></button>
        </form>
    <?php else: ?>
        <p><strong><?= e($t('status')) ?></strong> <?= e($t('not_subscribed')) ?></p>
        <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="subscribe">
            <label><?= e($t('email_label')) ?>
                <input type="email" name="email" value="<?= e($memberEmail) ?>" required>
            </label>
            <button class="button"><?= e($t('subscribe')) ?></button>
        </form>
    <?php endif; ?>
</div>
<?php

echo render_layout((string) ob_get_clean(), $t('layout_title'));
