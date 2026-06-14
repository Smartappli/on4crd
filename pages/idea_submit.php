<?php
declare(strict_types=1);

$locale = current_locale();
$messages = i18n_domain_locale('idea', $locale);
$t = static function (string $key, string $fallback) use ($messages): string {
    $value = trim((string) ($messages[$key] ?? ''));

    return $value !== '' ? $value : $fallback;
};

$returnUrl = trim((string) ($_POST['return_url'] ?? ''));
$redirectUrl = safe_login_next_url($returnUrl) ?? route_url('home');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit($t('method_not_allowed', 'Method not allowed.'));
}

$cleanLine = static function (string $value, int $maxLength): string {
    $value = str_replace(["\r", "\n"], ' ', strip_tags($value));
    $value = trim((string) preg_replace('/\s+/u', ' ', $value));
    if ($value !== '' && mb_strlen($value) > $maxLength) {
        throw new RuntimeException('invalid');
    }

    return $value;
};

$cleanMessage = static function (string $value, int $maxLength): string {
    $value = str_replace(["\r\n", "\r"], "\n", strip_tags($value));
    $value = trim((string) preg_replace('/[ \t]+/u', ' ', $value));
    if ($value !== '' && mb_strlen($value) > $maxLength) {
        throw new RuntimeException('invalid');
    }

    return $value;
};

try {
    verify_csrf();

    $name = $cleanLine((string) ($_POST['idea_name'] ?? ''), 160);
    $email = $cleanLine((string) ($_POST['idea_email'] ?? ''), 190);
    $category = $cleanLine((string) ($_POST['idea_category'] ?? 'general'), 80);
    $keywords = $cleanLine((string) ($_POST['idea_keywords'] ?? ''), 255);
    $title = $cleanLine((string) ($_POST['idea_title'] ?? ''), 190);
    $message = $cleanMessage((string) ($_POST['idea_message'] ?? ''), 4000);

    if ($name === '' || $title === '' || $message === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        throw new RuntimeException('invalid');
    }

    $subject = $cleanLine($t('email_subject', 'New idea for the ON4CRD committee'), 190);
    $body = $t('email_intro', 'New idea sent from the ON4CRD website:') . "\n\n"
        . $t('name_label', 'Your name') . ': ' . $name . "\n"
        . $t('email_label', 'Your email') . ': ' . $email . "\n"
        . $t('category_label', 'Topic') . ': ' . $category . "\n"
        . $t('keywords_label', 'Keywords') . ': ' . $keywords . "\n"
        . $t('idea_title_label', 'Idea title') . ': ' . $title . "\n\n"
        . $t('message_label', 'Your idea') . ":\n" . $message . "\n";
    $headers = 'From: ON4CRD Website <no-reply@on4crd.be>' . "\r\n"
        . 'Reply-To: ' . $email . "\r\n"
        . 'Content-Type: text/plain; charset=UTF-8';

    if (!@mail(site_contact_email(), $subject, $body, $headers)) {
        throw new RuntimeException('send');
    }

    set_flash('success', $t('sent', 'Thank you, your idea has been sent to the committee.'));
} catch (Throwable $throwable) {
    $rawMessage = $throwable->getMessage();
    if ($rawMessage === 'send') {
        set_flash('error', $t('send_error', 'The idea could not be sent. Please try again later.'));
    } elseif ($rawMessage === 'invalid' || $rawMessage === '') {
        set_flash('error', $t('invalid', 'Please complete the idea form correctly.'));
    } else {
        set_flash('error', $rawMessage);
    }
}

redirect_url($redirectUrl);
