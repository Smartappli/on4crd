<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pagesRoot = $root . '/pages';
$i18nRoot = $root . '/app/i18n';
$locales = ['fr', 'en', 'de', 'nl', 'it', 'es', 'pt', 'bg', 'hr', 'cs', 'da', 'et', 'fi', 'el', 'hu', 'ga', 'lv', 'lt', 'mt', 'pl', 'ro', 'sk', 'sl', 'sv', 'ar', 'hi', 'ja', 'zh', 'bn', 'ru', 'id'];

require_once $root . '/app/functions.php';

function extract_array_assignment(string $content, string $marker = '$i18n = ['): ?array
{
    $start = strpos($content, $marker);
    if ($start === false) {
        return null;
    }

    $open = strpos($content, '[', $start);
    if ($open === false) {
        return null;
    }

    $tokens = token_get_all('<?php ' . substr($content, $open));
    $depth = 0;
    $offset = $open;
    $length = strlen($content);
    for ($i = 1; $i < count($tokens); $i++) {
        $token = $tokens[$i];
        $text = is_array($token) ? $token[1] : $token;
        $tokenLength = strlen($text);

        if ($text === '[') {
            $depth++;
        } elseif ($text === ']') {
            $depth--;
            if ($depth === 0) {
                $end = $offset + $tokenLength;
                while ($end < $length && ctype_space($content[$end])) {
                    $end++;
                }
                if (($content[$end] ?? '') === ';') {
                    $end++;
                }
                return [$start, $end, substr($content, $open, $end - $open - 1)];
            }
        }

        $offset += $tokenLength;
    }

    return null;
}

function php_array_file(array $array): string
{
    return "<?php\n"
        . "declare(strict_types=1);\n\n"
        . 'return ' . var_export($array, true) . ";\n";
}

$migrated = [];

foreach (glob($pagesRoot . '/*.php') ?: [] as $pageFile) {
    $content = (string) file_get_contents($pageFile);
    if (!str_contains($content, '$i18n = [')) {
        continue;
    }

    $assignment = extract_array_assignment($content);
    if ($assignment === null) {
        fwrite(STDERR, 'Unable to read i18n block: ' . basename($pageFile) . PHP_EOL);
        exit(1);
    }

    [$start, $end, $arraySource] = $assignment;
    $messages = eval('return ' . $arraySource . ';');
    if (!is_array($messages)) {
        fwrite(STDERR, 'Invalid i18n array: ' . basename($pageFile) . PHP_EOL);
        exit(1);
    }

    $messages = i18n_expand_supported_locales($messages, $locales);
    $domain = basename($pageFile, '.php');
    $domainDir = $i18nRoot . '/' . $domain;
    if (!is_dir($domainDir) && !mkdir($domainDir, 0775, true) && !is_dir($domainDir)) {
        fwrite(STDERR, 'Unable to create i18n dir: ' . $domainDir . PHP_EOL);
        exit(1);
    }

    $index = [];
    foreach ($locales as $locale) {
        $localeMessages = isset($messages[$locale]) && is_array($messages[$locale]) ? $messages[$locale] : [];
        file_put_contents($domainDir . '/' . $locale . '.php', php_array_file($localeMessages));
        $index[] = "    '" . $locale . "' => require __DIR__ . '/" . $domain . '/' . $locale . ".php',";
    }

    file_put_contents(
        $i18nRoot . '/' . $domain . '.php',
        "<?php\n"
        . "declare(strict_types=1);\n\n"
        . "return [\n"
        . implode("\n", $index)
        . "\n];\n"
    );

    $replacement = '$i18n = i18n_domain_messages(\'' . $domain . '\');';
    $content = substr($content, 0, $start) . $replacement . substr($content, $end);
    file_put_contents($pageFile, $content);
    $migrated[] = $domain;
}

echo 'Migrated page i18n domains: ' . implode(', ', $migrated) . PHP_EOL;
