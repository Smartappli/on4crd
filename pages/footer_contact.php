<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['method_not_allowed' => 'Méthode non autorisée.', 'invalid_form' => 'Veuillez compléter le formulaire de contact correctement.', 'subject' => 'Nouveau message de contact footer ON4CRD', 'label_name' => 'Nom', 'label_email' => 'Email', 'label_message' => 'Message', 'ok_sent' => 'Votre message a bien été envoyé.', 'err_send' => 'Impossible d’envoyer le message pour le moment.'],
    'en' => ['method_not_allowed' => 'Method not allowed.', 'invalid_form' => 'Please complete the contact form correctly.', 'subject' => 'New ON4CRD footer contact message', 'label_name' => 'Name', 'label_email' => 'Email', 'label_message' => 'Message', 'ok_sent' => 'Your message has been sent.', 'err_send' => 'Unable to send the message right now.'],
    'de' => ['method_not_allowed' => 'Methode nicht erlaubt.', 'invalid_form' => 'Bitte füllen Sie das Kontaktformular korrekt aus.', 'subject' => 'Neue ON4CRD-Footer-Kontaktnachricht', 'label_name' => 'Name', 'label_email' => 'E-Mail', 'label_message' => 'Nachricht', 'ok_sent' => 'Ihre Nachricht wurde gesendet.', 'err_send' => 'Die Nachricht kann derzeit nicht gesendet werden.'],
    'nl' => ['method_not_allowed' => 'Methode niet toegestaan.', 'invalid_form' => 'Vul het contactformulier correct in.', 'subject' => 'Nieuw ON4CRD footer-contactbericht', 'label_name' => 'Naam', 'label_email' => 'E-mail', 'label_message' => 'Bericht', 'ok_sent' => 'Je bericht is verzonden.', 'err_send' => 'Het bericht kan momenteel niet worden verzonden.'],
];
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
