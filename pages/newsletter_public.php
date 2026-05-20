<?php
declare(strict_types=1);

newsletter_ensure_tables();
$locale = current_locale();
$i18n = [
    'fr' => ['title' => 'Inscription newsletter', 'desc' => 'Inscrivez-vous à la newsletter ON4CRD.', 'invalid_email' => 'Adresse email invalide.', 'ok' => 'Votre inscription à la newsletter est confirmée.', 'intro' => 'Recevez les actualités du Radio Club Durnal directement par email.', 'email_label' => 'Email newsletter', 'submit' => "S'inscrire à la newsletter"],
    'en' => ['title' => 'Newsletter signup', 'desc' => 'Subscribe to the ON4CRD newsletter.', 'invalid_email' => 'Invalid email address.', 'ok' => 'Your newsletter subscription is confirmed.', 'intro' => 'Receive Radio Club Durnal news directly by email.', 'email_label' => 'Newsletter email', 'submit' => 'Subscribe to newsletter'],
    'de' => ['title' => 'Newsletter-Anmeldung', 'desc' => 'Melden Sie sich für den ON4CRD-Newsletter an.', 'invalid_email' => 'Ungültige E-Mail-Adresse.', 'ok' => 'Ihre Newsletter-Anmeldung wurde bestätigt.', 'intro' => 'Erhalten Sie Neuigkeiten des Radio Club Durnal direkt per E-Mail.', 'email_label' => 'Newsletter-E-Mail', 'submit' => 'Für Newsletter anmelden'],
    'es' => ['title' => 'Suscripción al boletín', 'desc' => 'Suscríbase al boletín de ON4CRD.', 'invalid_email' => 'Dirección de correo no válida.', 'ok' => 'Su suscripción al boletín está confirmada.', 'intro' => 'Reciba las noticias de Radio Club Durnal directamente por correo electrónico.', 'email_label' => 'Correo del boletín', 'submit' => 'Suscribirse al boletín'],
    'it' => ['title' => 'Iscrizione newsletter', 'desc' => 'Iscriviti alla newsletter ON4CRD.', 'invalid_email' => 'Indirizzo email non valido.', 'ok' => 'La tua iscrizione alla newsletter è confermata.', 'intro' => 'Ricevi le notizie del Radio Club Durnal direttamente via email.', 'email_label' => 'Email newsletter', 'submit' => 'Iscriviti alla newsletter'],
    'pt' => ['title' => 'Inscrição newsletter', 'desc' => 'Subscreva a newsletter ON4CRD.', 'invalid_email' => 'Endereço de email inválido.', 'ok' => 'A sua subscrição da newsletter está confirmada.', 'intro' => 'Receba as notícias do Radio Club Durnal diretamente por email.', 'email_label' => 'Email da newsletter', 'submit' => 'Subscrever newsletter'],
    'nl' => ['title' => 'Nieuwsbriefinschrijving', 'desc' => 'Schrijf je in voor de ON4CRD-nieuwsbrief.', 'invalid_email' => 'Ongeldig e-mailadres.', 'ok' => 'Je nieuwsbriefinschrijving is bevestigd.', 'intro' => 'Ontvang nieuws van Radio Club Durnal rechtstreeks per e-mail.', 'email_label' => 'Nieuwsbrief e-mail', 'submit' => 'Inschrijven op nieuwsbrief'],

    'ar' => ['title' => 'الاشتراك في النشرة', 'desc' => 'اشترك في نشرة ON4CRD البريدية.', 'invalid_email' => 'عنوان البريد الإلكتروني غير صالح.', 'ok' => 'تم تأكيد اشتراكك في النشرة البريدية.', 'intro' => 'تلقَّ أخبار Radio Club Durnal مباشرة عبر البريد الإلكتروني.', 'email_label' => 'بريد النشرة', 'submit' => 'الاشتراك في النشرة'],
    'bn' => ['title' => 'নিউজলেটার নিবন্ধন', 'desc' => 'ON4CRD নিউজলেটারে সাবস্ক্রাইব করুন।', 'invalid_email' => 'ইমেইল ঠিকানা সঠিক নয়।', 'ok' => 'আপনার নিউজলেটার সাবস্ক্রিপশন নিশ্চিত হয়েছে।', 'intro' => 'রেডিও ক্লাব দুরনাল-এর খবর সরাসরি ইমেইলে পান।', 'email_label' => 'নিউজলেটার ইমেইল', 'submit' => 'নিউজলেটারে সাবস্ক্রাইব করুন'],
    'hi' => ['title' => 'न्यूज़लेटर सदस्यता', 'desc' => 'ON4CRD न्यूज़लेटर की सदस्यता लें।', 'invalid_email' => 'अमान्य ईमेल पता।', 'ok' => 'आपकी न्यूज़लेटर सदस्यता की पुष्टि हो गई है।', 'intro' => 'Radio Club Durnal की खबरें सीधे ईमेल पर प्राप्त करें।', 'email_label' => 'न्यूज़लेटर ईमेल', 'submit' => 'न्यूज़लेटर सदस्यता लें'],
    'id' => ['title' => 'Pendaftaran buletin', 'desc' => 'Berlangganan buletin ON4CRD.', 'invalid_email' => 'Alamat email tidak valid.', 'ok' => 'Langganan buletin Anda telah dikonfirmasi.', 'intro' => 'Terima berita Radio Club Durnal langsung melalui email.', 'email_label' => 'Email buletin', 'submit' => 'Berlangganan buletin'],
    'ja' => ['title' => 'ニュースレター登録', 'desc' => 'ON4CRD ニュースレターに登録してください。', 'invalid_email' => 'メールアドレスが無効です。', 'ok' => 'ニュースレター登録が確認されました。', 'intro' => 'Radio Club Durnal のニュースをメールで直接受け取りましょう。', 'email_label' => 'ニュースレターメール', 'submit' => 'ニュースレターに登録'],
    'ru' => ['title' => 'Подписка на рассылку', 'desc' => 'Подпишитесь на рассылку ON4CRD.', 'invalid_email' => 'Неверный адрес электронной почты.', 'ok' => 'Ваша подписка на рассылку подтверждена.', 'intro' => 'Получайте новости Radio Club Durnal напрямую по электронной почте.', 'email_label' => 'Email для рассылки', 'submit' => 'Подписаться на рассылку'],
    'zh' => ['title' => '新闻简报订阅', 'desc' => '订阅 ON4CRD 新闻简报。', 'invalid_email' => '邮箱地址无效。', 'ok' => '你的新闻简报订阅已确认。', 'intro' => '通过电子邮箱直接接收 Radio Club Durnal 的新闻。', 'email_label' => '简报邮箱', 'submit' => '订阅新闻简报'],
];
$i18n = i18n_expand_supported_locales($i18n);
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};

set_page_meta([
    'title' => $t('title'),
    'description' => $t('desc'),
]);

$prefillEmail = trim((string) ($_POST['email'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $email = newsletter_normalize_email((string) ($_POST['email'] ?? ''));
        if ($email === '') {
            throw new RuntimeException($t('invalid_email'));
        }

        if (!newsletter_upsert_subscriber($email, null, 'public_form')) {
            throw new RuntimeException($t('invalid_email'));
        }

        set_flash('success', $t('ok'));
        redirect('newsletter_public');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        $prefillEmail = trim((string) ($_POST['email'] ?? ''));
    }
}

ob_start();
?>
<div class="card">
    <h1><?= e($t('title')) ?></h1>
    <p><?= e($t('intro')) ?></p>
    <form method="post" class="stack">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <label for="newsletter-public-email"><?= e($t('email_label')) ?></label>
        <input id="newsletter-public-email" type="email" name="email" value="<?= e($prefillEmail) ?>" required>
        <button type="submit" class="button"><?= e($t('submit')) ?></button>
    </form>
</div>
<?php

echo render_layout((string) ob_get_clean(), $t('title'));
