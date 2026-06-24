<?php
declare(strict_types=1);

$locale = current_locale();
$messages = i18n_domain_locale('idea', $locale);
$t = static fn(string $key): string => (string) ($messages[$key] ?? $key);

$ideaCategoryTranslationKeys = [
    'general' => 'category_general',
    'activity' => 'category_activity',
    'training' => 'category_training',
    'technical' => 'category_technical',
    'website' => 'category_website',
    'equipment' => 'category_equipment',
    'event' => 'category_event',
];

$returnUrl = trim((string) ($_POST['return_url'] ?? ''));
$redirectUrl = safe_login_next_url($returnUrl) ?? route_url('home');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit($t('method_not_allowed'));
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
    public_form_rate_limit('idea_submit', 5, 900);

    if (public_form_honeypot_triggered('idea_website')) {
        set_flash('success', $t('sent'));
        redirect_url($redirectUrl);
    }

    $name = $cleanLine((string) ($_POST['idea_name'] ?? ''), 160);
    $email = $cleanLine((string) ($_POST['idea_email'] ?? ''), 190);
    $category = $cleanLine((string) ($_POST['idea_category'] ?? 'general'), 80);
    $keywords = $cleanLine((string) ($_POST['idea_keywords'] ?? ''), 255);
    $title = $cleanLine((string) ($_POST['idea_title'] ?? ''), 190);
    $message = $cleanMessage((string) ($_POST['idea_message'] ?? ''), 4000);

    if ($name === '' || $title === '' || $message === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        throw new RuntimeException('invalid');
    }
    if (!isset($ideaCategoryTranslationKeys[$category])) {
        throw new RuntimeException('invalid');
    }
    $categoryLabel = $t($ideaCategoryTranslationKeys[$category]);

    $subject = $cleanLine($t('email_subject'), 190);
    $body = $t('email_intro') . "\n\n"
        . $t('name_label') . ': ' . $name . "\n"
        . $t('email_label') . ': ' . $email . "\n"
        . $t('category_label') . ': ' . $categoryLabel . "\n"
        . $t('keywords_label') . ': ' . $keywords . "\n"
        . $t('idea_title_label') . ': ' . $title . "\n\n"
        . $t('message_label') . ":\n" . $message . "\n";
    $headers = 'From: ON4CRD Website <no-reply@on4crd.be>' . "\r\n"
        . 'Reply-To: ' . $email . "\r\n"
        . 'Content-Type: text/plain; charset=UTF-8';

    if (!@mail(site_contact_email(), $subject, $body, $headers)) {
        throw new RuntimeException('send');
    }

    set_flash('success', $t('sent'));
} catch (Throwable $throwable) {
    $rawMessage = $throwable->getMessage();
    if ($rawMessage === 'send') {
        set_flash('error', $t('send_error'));
    } elseif ($rawMessage === 'too_many_requests') {
        set_flash('error', $t('too_many_requests'));
    } elseif ($rawMessage === 'invalid' || $rawMessage === '') {
        set_flash('error', $t('invalid'));
    } else {
        set_flash('error', $rawMessage);
    }
}

redirect_url($redirectUrl);
