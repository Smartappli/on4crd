<?php
declare(strict_types=1);

$locale = current_locale();
$t = i18n_domain_translator('footer_contact', $locale);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit($t('method_not_allowed'));
}

$returnRoute = trim((string) ($_POST['return_route'] ?? 'home'));
$allowedReturnRoutes = ['home' => true, 'donation' => true];
if (!isset($allowedReturnRoutes[$returnRoute])) {
    $returnRoute = 'home';
}

try {
    verify_csrf();
    public_form_rate_limit('footer_contact', 5, 900);

    if (public_form_honeypot_triggered('contact_website')) {
        set_flash('success', $t('ok_sent'));
        redirect($returnRoute);
    }

    if (!public_form_verify_captcha('footer_contact', (string) ($_POST['contact_captcha'] ?? ''))) {
        throw new RuntimeException('invalid');
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $rawMessage = trim((string) ($_POST['message'] ?? ''));
    $message = trim(strip_tags($rawMessage));

    if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('invalid');
    }

    $safeName = str_replace(["\r", "\n"], ' ', $name);
    $safeEmail = str_replace(["\r", "\n"], '', $email);

    $subject = $t('subject');
    $body = $t('label_name') . ": {$safeName}\n" . $t('label_email') . ": {$safeEmail}\n\n" . $t('label_message') . ":\n{$message}\n";
    $headers = 'From: ON4CRD Website <no-reply@on4crd.be>' . "\r\n"
        . 'Reply-To: ' . $safeEmail . "\r\n"
        . 'Content-Type: text/plain; charset=UTF-8';

    $sent = @mail('crdurnal@gmail.com', $subject, $body, $headers);

    if ($sent) {
        set_flash('success', $t('ok_sent'));
    } else {
        set_flash('error', $t('err_send'));
    }
} catch (Throwable $throwable) {
    $message = $throwable->getMessage() === 'too_many_requests'
        ? 'Trop de demandes. Merci de reessayer plus tard.'
        : $t('invalid_form');
    set_flash('error', $message);
}

redirect($returnRoute);
