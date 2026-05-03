<?php
declare(strict_types=1);

newsletter_ensure_tables();
$locale = current_locale();
$i18n = [
    'fr' => ['title' => 'Désabonnement newsletter', 'desc' => 'Confirmation de désabonnement à la newsletter ON4CRD.', 'heading' => 'Newsletter', 'ok' => 'Votre désabonnement a été pris en compte.', 'invalid' => 'Lien invalide ou déjà traité.'],
    'en' => ['title' => 'Newsletter unsubscribe', 'desc' => 'Unsubscribe confirmation for the ON4CRD newsletter.', 'heading' => 'Newsletter', 'ok' => 'Your unsubscribe request has been processed.', 'invalid' => 'Invalid or already used link.'],
    'de' => ['title' => 'Newsletter-Abmeldung', 'desc' => 'Bestätigung der Abmeldung vom ON4CRD-Newsletter.', 'heading' => 'Newsletter', 'ok' => 'Ihre Abmeldung wurde berücksichtigt.', 'invalid' => 'Ungültiger oder bereits verwendeter Link.'],
    'nl' => ['title' => 'Nieuwsbrief uitschrijven', 'desc' => 'Bevestiging van uitschrijving voor de ON4CRD-nieuwsbrief.', 'heading' => 'Nieuwsbrief', 'ok' => 'Je uitschrijving is verwerkt.', 'invalid' => 'Ongeldige of al gebruikte link.'],
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
