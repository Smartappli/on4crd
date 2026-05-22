<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/app/functions.php';

$domains = ['press', 'sponsoring', 'mentions_legales', 'conditions_utilisation', 'reglement_interieur'];
$locales = supported_locales();

foreach ($domains as $domain) {
    $dir = $root . '/app/i18n/' . $domain;
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        fwrite(STDERR, 'Unable to create i18n dir: ' . $dir . PHP_EOL);
        exit(1);
    }

    $index = [];
    foreach ($locales as $locale) {
        $data = [
            'title' => t_page($domain, 'title', $locale),
            'body' => t_page($domain, 'body', $locale),
        ];
        file_put_contents(
            $dir . '/' . $locale . '.php',
            "<?php\n"
            . "declare(strict_types=1);\n\n"
            . 'return ' . var_export($data, true) . ";\n"
        );
        $index[] = "    '" . $locale . "' => require __DIR__ . '/" . $domain . '/' . $locale . ".php',";
    }

    file_put_contents(
        $root . '/app/i18n/' . $domain . '.php',
        "<?php\n"
        . "declare(strict_types=1);\n\n"
        . "return [\n"
        . implode("\n", $index)
        . "\n];\n"
    );
}

echo 'Generated t_page i18n domains: ' . implode(', ', $domains) . PHP_EOL;
