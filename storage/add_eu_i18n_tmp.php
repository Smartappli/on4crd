<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$i18nRoot = $root . '/app/i18n';

$euMissing = ['bg', 'hr', 'cs', 'da', 'et', 'fi', 'el', 'hu', 'ga', 'lv', 'lt', 'mt', 'pl', 'ro', 'sk', 'sl', 'sv'];
$googleTargets = [
    'bg' => 'bg',
    'hr' => 'hr',
    'cs' => 'cs',
    'da' => 'da',
    'et' => 'et',
    'fi' => 'fi',
    'el' => 'el',
    'hu' => 'hu',
    'ga' => 'ga',
    'lv' => 'lv',
    'lt' => 'lt',
    'mt' => 'mt',
    'pl' => 'pl',
    'ro' => 'ro',
    'sk' => 'sk',
    'sl' => 'sl',
    'sv' => 'sv',
];

function load_catalog(string $file): array
{
    $t = static fn(string $key): string => $key;
    $data = require $file;
    return is_array($data) ? $data : [];
}

function protect_tokens(string $value, array &$tokens): string
{
    $patterns = [
        '/%(\d+\$)?[-+ 0#]*(\d+|\*)?(\.(\d+|\*))?[bcdeEfFgGosuxX]/',
        '/\{[A-Za-z_][A-Za-z0-9_]*\}/',
        '/:[A-Za-z_][A-Za-z0-9_]*/',
        '/<[^>]+>/',
        '~https?://[^\s<>"\']+~',
        '/\b[A-Z0-9]{2,}(?:[\/._-][A-Z0-9]+)*\b/',
    ];

    foreach ($patterns as $pattern) {
        $value = preg_replace_callback($pattern, static function (array $match) use (&$tokens): string {
            $placeholder = 'ZXQ' . count($tokens) . 'QXZ';
            $tokens[$placeholder] = $match[0];
            return $placeholder;
        }, $value) ?? $value;
    }

    return $value;
}

function restore_tokens(string $value, array $tokens): string
{
    if ($tokens === []) {
        return $value;
    }

    return str_replace(array_keys($tokens), array_values($tokens), $value);
}

function should_translate(string $value): bool
{
    $trimmed = trim($value);
    if ($trimmed === '') {
        return false;
    }
    if (preg_match('/^[0-9\s:.,+\/()%€Ωµ-]+$/u', $trimmed)) {
        return false;
    }
    if (preg_match('/^https?:\/\//i', $trimmed)) {
        return false;
    }
    return true;
}

function translate_batch(array $values, string $target): array
{
    if ($values === []) {
        return [];
    }

    $markers = [];
    $text = '';
    foreach ($values as $idx => $value) {
        $marker = '[[[T' . $idx . ']]]';
        $markers[$idx] = $marker;
        $text .= $marker . "\n" . $value . "\n";
    }

    $url = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=fr&tl=' . rawurlencode($target) . '&dt=t&q=' . rawurlencode(trim($text));
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'header' => "User-Agent: ON4CRD-i18n\r\n",
        ],
    ]);
    $raw = @file_get_contents($url, false, $context);
    if (!is_string($raw) || $raw === '') {
        throw new RuntimeException('Translation request failed for ' . $target);
    }

    $json = json_decode($raw, true);
    if (!is_array($json) || !isset($json[0]) || !is_array($json[0])) {
        throw new RuntimeException('Unexpected translation response for ' . $target);
    }

    $translatedText = '';
    foreach ($json[0] as $chunk) {
        if (is_array($chunk) && isset($chunk[0]) && is_string($chunk[0])) {
            $translatedText .= $chunk[0];
        }
    }

    $result = [];
    foreach ($values as $idx => $_) {
        $current = preg_quote($markers[$idx], '/');
        $next = $markers[$idx + 1] ?? null;
        $pattern = $next === null
            ? '/' . $current . '\s*(.*)\s*$/su'
            : '/' . $current . '\s*(.*?)\s*' . preg_quote($next, '/') . '/su';
        if (!preg_match($pattern, $translatedText, $match)) {
            throw new RuntimeException('Unable to split translation batch for ' . $target);
        }
        $result[$idx] = trim((string) $match[1]);
    }

    return $result;
}

function translate_single(string $value, string $target): string
{
    $url = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=fr&tl=' . rawurlencode($target) . '&dt=t&q=' . rawurlencode($value);
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'header' => "User-Agent: ON4CRD-i18n\r\n",
        ],
    ]);
    $raw = @file_get_contents($url, false, $context);
    if (!is_string($raw) || $raw === '') {
        throw new RuntimeException('Single translation request failed for ' . $target);
    }
    $json = json_decode($raw, true);
    if (!is_array($json) || !isset($json[0]) || !is_array($json[0])) {
        throw new RuntimeException('Unexpected single translation response for ' . $target);
    }
    $translated = '';
    foreach ($json[0] as $chunk) {
        if (is_array($chunk) && isset($chunk[0]) && is_string($chunk[0])) {
            $translated .= $chunk[0];
        }
    }
    return trim($translated);
}

function translate_values(array $values, string $target): array
{
    $out = $values;
    $batch = [];
    $batchIndexes = [];
    $batchSize = 0;

    $flush = static function () use (&$out, &$batch, &$batchIndexes, &$batchSize, $target): void {
        if ($batch === []) {
            return;
        }
        try {
            $translated = translate_batch($batch, $target);
        } catch (Throwable) {
            $translated = [];
            foreach ($batch as $batchIdx => $value) {
                $translated[$batchIdx] = translate_single($value, $target);
                usleep(120000);
            }
        }
        foreach ($translated as $batchIdx => $translatedValue) {
            $originalIndex = $batchIndexes[$batchIdx];
            $out[$originalIndex] = $translatedValue;
        }
        $batch = [];
        $batchIndexes = [];
        $batchSize = 0;
        usleep(120000);
    };

    foreach ($values as $idx => $value) {
        if (!should_translate($value)) {
            continue;
        }
        $len = strlen($value) + 16;
        if ($batch !== [] && ($batchSize + $len) > 2800) {
            $flush();
        }
        $batchIndexes[] = $idx;
        $batch[] = $value;
        $batchSize += $len;
    }
    $flush();

    return $out;
}

function translate_tree(array $source, string $target): array
{
    $paths = [];
    $values = [];
    $tokensByIndex = [];

    $walk = static function (array $node, array $path) use (&$walk, &$paths, &$values, &$tokensByIndex): void {
        foreach ($node as $key => $value) {
            $nextPath = [...$path, $key];
            if (is_array($value)) {
                $walk($value, $nextPath);
                continue;
            }
            if (!is_string($value)) {
                continue;
            }
            $tokens = [];
            $protected = protect_tokens($value, $tokens);
            $paths[] = $nextPath;
            $values[] = $protected;
            $tokensByIndex[] = $tokens;
        }
    };
    $walk($source, []);

    $translated = translate_values($values, $target);
    $result = $source;
    foreach ($paths as $idx => $path) {
        $value = restore_tokens($translated[$idx] ?? $values[$idx], $tokensByIndex[$idx]);
        $cursor =& $result;
        foreach ($path as $depth => $key) {
            if ($depth === count($path) - 1) {
                $cursor[$key] = $value;
                break;
            }
            $cursor =& $cursor[$key];
        }
        unset($cursor);
    }

    return $result;
}

function write_catalog(string $file, array $data): void
{
    file_put_contents($file, "<?php\ndeclare(strict_types=1);\n\nreturn " . var_export($data, true) . ";\n");
}

function rewrite_index(string $module, array $locales): void
{
    $lines = ["<?php", "declare(strict_types=1);", "", '$messages = [];'];
    foreach ($locales as $locale) {
        $lines[] = '$messages[\'' . $locale . '\'] = require __DIR__ . \'/' . $module . '/' . $locale . '.php\';';
    }
    $lines[] = '';
    $lines[] = 'return $messages;';
    file_put_contents(dirname(__DIR__) . '/app/i18n/' . $module . '.php', implode("\n", $lines) . "\n");
}

$allLocales = ['fr', 'en', 'de', 'nl', 'it', 'es', 'pt', 'bg', 'hr', 'cs', 'da', 'et', 'fi', 'el', 'hu', 'ga', 'lv', 'lt', 'mt', 'pl', 'ro', 'sk', 'sl', 'sv', 'ar', 'hi', 'ja', 'zh', 'bn', 'ru', 'id'];
$dirs = glob($i18nRoot . '/*', GLOB_ONLYDIR) ?: [];
sort($dirs);

foreach ($dirs as $dir) {
    $module = basename($dir);
    $frFile = $dir . '/fr.php';
    if (!is_file($frFile)) {
        continue;
    }
    $fr = load_catalog($frFile);
    if ($fr === []) {
        continue;
    }
    echo 'module ' . $module . PHP_EOL;
    foreach ($euMissing as $locale) {
        $targetFile = $dir . '/' . $locale . '.php';
        if (is_file($targetFile)) {
            continue;
        }
        echo '  ' . $locale . PHP_EOL;
        $translated = translate_tree($fr, $googleTargets[$locale]);
        write_catalog($targetFile, $translated);
    }
    if (is_file($i18nRoot . '/' . $module . '.php')) {
        rewrite_index($module, $allLocales);
    }
}
