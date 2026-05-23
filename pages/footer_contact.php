<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['method_not_allowed' => 'Méthode non autorisée.', 'invalid_form' => 'Veuillez compléter le formulaire de contact correctement.', 'subject' => 'Nouveau message de contact footer ON4CRD', 'label_name' => 'Nom', 'label_email' => 'Email', 'label_message' => 'Message', 'ok_sent' => 'Votre message a bien été envoyé.', 'err_send' => 'Impossible d’envoyer le message pour le moment.'],
    'en' => ['method_not_allowed' => 'Method not allowed.', 'invalid_form' => 'Please complete the contact form correctly.', 'subject' => 'New ON4CRD footer contact message', 'label_name' => 'Name', 'label_email' => 'Email', 'label_message' => 'Message', 'ok_sent' => 'Your message has been sent.', 'err_send' => 'Unable to send the message right now.'],
    'de' => ['method_not_allowed' => 'Methode nicht erlaubt.', 'invalid_form' => 'Bitte füllen Sie das Kontaktformular korrekt aus.', 'subject' => 'Neue ON4CRD-Footer-Kontaktnachricht', 'label_name' => 'Name', 'label_email' => 'E-Mail', 'label_message' => 'Nachricht', 'ok_sent' => 'Ihre Nachricht wurde gesendet.', 'err_send' => 'Die Nachricht kann derzeit nicht gesendet werden.'],
    'es' => ['method_not_allowed' => 'Método no permitido.', 'invalid_form' => 'Complete correctamente el formulario de contacto.', 'subject' => 'Nuevo mensaje de contacto footer ON4CRD', 'label_name' => 'Nombre', 'label_email' => 'Correo', 'label_message' => 'Mensaje', 'ok_sent' => 'Su mensaje ha sido enviado.', 'err_send' => 'No se puede enviar el mensaje ahora.'],
    'it' => ['method_not_allowed' => 'Metodo non consentito.', 'invalid_form' => 'Compila correttamente il modulo di contatto.', 'subject' => 'Nuovo messaggio contatto footer ON4CRD', 'label_name' => 'Nome', 'label_email' => 'Email', 'label_message' => 'Messaggio', 'ok_sent' => 'Il tuo messaggio è stato inviato.', 'err_send' => 'Impossibile inviare il messaggio ora.'],
    'pt' => ['method_not_allowed' => 'Método não permitido.', 'invalid_form' => 'Preencha corretamente o formulário de contacto.', 'subject' => 'Nova mensagem de contacto footer ON4CRD', 'label_name' => 'Nome', 'label_email' => 'Email', 'label_message' => 'Mensagem', 'ok_sent' => 'A sua mensagem foi enviada.', 'err_send' => 'Não é possível enviar a mensagem neste momento.'],
    'nl' => ['method_not_allowed' => 'Methode niet toegestaan.', 'invalid_form' => 'Vul het contactformulier correct in.', 'subject' => 'Nieuw ON4CRD footer-contactbericht', 'label_name' => 'Naam', 'label_email' => 'E-mail', 'label_message' => 'Bericht', 'ok_sent' => 'Je bericht is verzonden.', 'err_send' => 'Het bericht kan momenteel niet worden verzonden.'],
    'ar' => ['method_not_allowed' => 'الطريقة غير مسموحة.', 'invalid_form' => 'يرجى إكمال نموذج الاتصال بشكل صحيح.', 'subject' => 'رسالة اتصال جديدة من تذييل ON4CRD', 'label_name' => 'الاسم', 'label_email' => 'البريد الإلكتروني', 'label_message' => 'الرسالة', 'ok_sent' => 'تم إرسال رسالتك.', 'err_send' => 'تعذر إرسال الرسالة حالياً.'],
    'bn' => ['method_not_allowed' => 'পদ্ধতিটি অনুমোদিত নয়।', 'invalid_form' => 'অনুগ্রহ করে যোগাযোগ ফর্মটি সঠিকভাবে পূরণ করুন।', 'subject' => 'ON4CRD ফুটার থেকে নতুন যোগাযোগ বার্তা', 'label_name' => 'নাম', 'label_email' => 'ইমেইল', 'label_message' => 'বার্তা', 'ok_sent' => 'আপনার বার্তা পাঠানো হয়েছে।', 'err_send' => 'এই মুহূর্তে বার্তাটি পাঠানো যাচ্ছে না।'],
    'hi' => ['method_not_allowed' => 'विधि अनुमत नहीं है।', 'invalid_form' => 'कृपया संपर्क फ़ॉर्म सही ढंग से पूरा करें।', 'subject' => 'ON4CRD फुटर से नया संपर्क संदेश', 'label_name' => 'नाम', 'label_email' => 'ईमेल', 'label_message' => 'संदेश', 'ok_sent' => 'आपका संदेश भेज दिया गया है।', 'err_send' => 'अभी संदेश भेजा नहीं जा सकता।'],
    'id' => ['method_not_allowed' => 'Metode tidak diizinkan.', 'invalid_form' => 'Silakan lengkapi formulir kontak dengan benar.', 'subject' => 'Pesan kontak footer ON4CRD baru', 'label_name' => 'Nama', 'label_email' => 'Email', 'label_message' => 'Pesan', 'ok_sent' => 'Pesan Anda telah dikirim.', 'err_send' => 'Pesan belum dapat dikirim saat ini.'],
    'ja' => ['method_not_allowed' => 'このメソッドは許可されていません。', 'invalid_form' => 'お問い合わせフォームを正しく入力してください。', 'subject' => 'ON4CRDフッターからの新しい問い合わせ', 'label_name' => '名前', 'label_email' => 'メール', 'label_message' => 'メッセージ', 'ok_sent' => 'メッセージを送信しました。', 'err_send' => '現在メッセージを送信できません。'],
    'ru' => ['method_not_allowed' => 'Метод не разрешён.', 'invalid_form' => 'Пожалуйста, корректно заполните контактную форму.', 'subject' => 'Новое сообщение из футера ON4CRD', 'label_name' => 'Имя', 'label_email' => 'Email', 'label_message' => 'Сообщение', 'ok_sent' => 'Ваше сообщение отправлено.', 'err_send' => 'Сейчас невозможно отправить сообщение.'],
    'zh' => ['method_not_allowed' => '不允许使用此方法。', 'invalid_form' => '请正确填写联系表单。', 'subject' => '新的 ON4CRD 页脚联系消息', 'label_name' => '姓名', 'label_email' => '电子邮箱', 'label_message' => '消息', 'ok_sent' => '你的消息已发送。', 'err_send' => '目前无法发送消息。'],
];
$i18n = i18n_expand_supported_locales($i18n);
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit($t('method_not_allowed'));
}

verify_csrf();

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$rawMessage = trim((string) ($_POST['message'] ?? ''));
$message = trim(strip_tags($rawMessage));
$returnRoute = trim((string) ($_POST['return_route'] ?? 'home'));

if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('error', $t('invalid_form'));
    redirect($returnRoute !== '' ? $returnRoute : 'home');
}

$safeName = str_replace(["\r", "\n"], ' ', $name);
$safeEmail = str_replace(["\r", "\n"], '', $email);

$subject = $t('subject');
$body = $t('label_name') . ": {$safeName}\n" . $t('label_email') . ": {$safeEmail}\n\n" . $t('label_message') . ":\n{$message}\n";
$headers = 'From: ON4CRD Website <no-reply@on4crd.be>' . "\r\n"
    . 'Reply-To: ' . $safeEmail . "\r\n"
    . 'Content-Type: text/plain; charset=UTF-8';

$sent = @mail('on4crd@gmail.com', $subject, $body, $headers);

if ($sent) {
    set_flash('success', $t('ok_sent'));
} else {
    set_flash('error', $t('err_send'));
}

redirect($returnRoute !== '' ? $returnRoute : 'home');
