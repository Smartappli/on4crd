<?php
$root = dirname(__DIR__);
$locales = ['fr', 'en', 'de', 'nl', 'it', 'es', 'pt', 'bg', 'hr', 'cs', 'da', 'et', 'fi', 'el', 'hu', 'ga', 'lv', 'lt', 'mt', 'pl', 'ro', 'sk', 'sl', 'sv', 'ar', 'hi', 'ja', 'zh', 'bn', 'ru', 'id'];
$fr = [
    'email_subject' => 'ON4CRD - Réinitialisation du mot de passe',
    'email_body' => "Bonjour,\n\nUtilisez ce lien pour réinitialiser votre mot de passe ON4CRD :\n{reset_link}\n\nSi vous n’êtes pas à l’origine de cette demande, ignorez ce message.",
];
$en = [
    'email_subject' => 'ON4CRD - Password reset',
    'email_body' => "Hello,\n\nUse this link to reset your ON4CRD password:\n{reset_link}\n\nIf you did not request this, ignore this message.",
];
$added = 0;
foreach ($locales as $locale) {
    $path = $root . '/app/i18n/forgot_password/' . $locale . '.php';
    $values = require $path;
    $additions = $locale === 'fr' ? $fr : ($locale === 'en' ? $en : $en);
    foreach ($additions as $key => $value) {
        if (!array_key_exists($key, $values)) {
            $values[$key] = $value;
            $added++;
        }
    }
    file_put_contents($path, "<?php\ndeclare(strict_types=1);\n\nreturn " . var_export($values, true) . ";\n");
}
echo "ADDED=$added\n";