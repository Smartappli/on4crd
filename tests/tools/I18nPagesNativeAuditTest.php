<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../tools/i18n_pages_native_audit.php';

final class I18nPagesNativeAuditTest extends TestCase
{
    public function testExtractLocaleBlocksWithNestedArrays(): void
    {
        $sample = <<<'PHP_SAMPLE'
<?php
$i18n = [
  'fr' => ['title' => 'Bonjour', 'items' => ['a', 'b']],
  'en' => ['title' => 'Hello', 'items' => ['a', 'b']],
  'de' => ['title' => 'Hallo', 'items' => ['a', 'b']],
];
PHP_SAMPLE;

        $blocks = extract_locale_blocks($sample);

        self::assertArrayHasKey('fr', $blocks);
        self::assertArrayHasKey('en', $blocks);
        self::assertArrayHasKey('de', $blocks);
        self::assertNotSame($blocks['fr'], $blocks['en']);
    }


    public function testExtractLocaleBlocksSupportsDoubleQuotedLocaleKeys(): void
    {
        $sample = <<<'PHP_SAMPLE'
<?php
$i18n = [
  "fr" => ['title' => 'Bonjour'],
  "en" => ['title' => 'Hello'],
  "es" => ['title' => 'Hola'],
];
PHP_SAMPLE;

        $blocks = extract_locale_blocks($sample);

        self::assertArrayHasKey('fr', $blocks);
        self::assertArrayHasKey('en', $blocks);
        self::assertArrayHasKey('es', $blocks);
    }

    public function testRunI18nAuditFindsMissingLocaleBlock(): void
    {
        $sample = <<<'PHP_SAMPLE'
<?php
$i18n = [
  'fr' => ['title' => 'Bonjour'],
  'en' => ['title' => 'Hello'],
  'de' => ['title' => 'Hallo'],
];
PHP_SAMPLE;

        $tmpDir = sys_get_temp_dir() . '/i18n-audit-' . bin2hex(random_bytes(4));
        mkdir($tmpDir);
        file_put_contents($tmpDir . '/page.php', $sample);

        try {
            $result = run_i18n_audit($tmpDir, ['en', 'fr', 'de', 'es']);
            self::assertSame(1, $result['pages_with_i18n']);

            $issueTypes = array_map(static fn(array $issue): string => $issue[2], $result['issues']);
            self::assertContains('missing_locale_block', $issueTypes);
        } finally {
            @unlink($tmpDir . '/page.php');
            @rmdir($tmpDir);
        }
    }

    public function testRunI18nAuditFindsFallbackLikeContent(): void
    {
        $sample = <<<'PHP_SAMPLE'
<?php
$i18n = [
  'fr' => ['title' => 'Bonjour'],
  'en' => ['title' => 'Hello'],
  'es' => ['title' => 'Hello'],
];
PHP_SAMPLE;

        $tmpDir = sys_get_temp_dir() . '/i18n-audit-' . bin2hex(random_bytes(4));
        mkdir($tmpDir);
        file_put_contents($tmpDir . '/page.php', $sample);

        try {
            $result = run_i18n_audit($tmpDir, ['en', 'fr', 'es']);
            self::assertSame(1, $result['pages_with_i18n']);

            $found = false;
            foreach ($result['issues'] as $issue) {
                if ($issue[1] === 'es' && $issue[2] === 'fallback_like_content') {
                    $found = true;
                    break;
                }
            }
            self::assertTrue($found);
        } finally {
            @unlink($tmpDir . '/page.php');
            @rmdir($tmpDir);
        }
    }
}
