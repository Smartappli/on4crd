<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../tools/check_duplication.php';

final class CodeDuplicationTest extends TestCase
{
    public function testNormalizeLinesStripsCommentsDeclarationsAndPunctuationOnlyLines(): void
    {
        $source = <<<'PHP'
<?php
declare(strict_types=1);

// ignored
function demo(): void
{
    $value = 1; /* ignored */
}
PHP;

        $lines = duplication_normalize_lines($source, 'php');

        self::assertSame(
            ['function demo(): void', '$value = 1;'],
            array_column($lines, 'code')
        );
    }

    public function testAnalyzerDetectsRepeatedBlocksAndHonorsExcludedSegments(): void
    {
        $root = $this->temporaryProjectRoot();

        try {
            mkdir($root . '/app/i18n', 0777, true);
            file_put_contents($root . '/app/one.php', $this->duplicateFixture());
            file_put_contents($root . '/app/two.php', $this->duplicateFixture());
            file_put_contents($root . '/app/i18n/ignored.php', $this->duplicateFixture());

            $result = duplication_analyze($root, [
                'roots' => ['app'],
                'block_size' => 4,
            ]);

            self::assertSame(['app/one.php', 'app/two.php'], $result['files']);
            self::assertSame(10, $result['total_lines']);
            self::assertSame(10, $result['duplicated_lines']);
            self::assertSame(100.0, (float) $result['percentage']);
            self::assertNotEmpty($result['duplicate_blocks']);
        } finally {
            $this->removeTree($root);
        }
    }

    private function duplicateFixture(): string
    {
        return <<<'PHP'
<?php
$alpha = 1;
$beta = 2;
$gamma = 3;
$delta = 4;
$epsilon = 5;
PHP;
    }

    private function temporaryProjectRoot(): string
    {
        $root = sys_get_temp_dir() . '/on4crd-dup-' . bin2hex(random_bytes(6));
        mkdir($root . '/app', 0777, true);

        return $root;
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if (!$item instanceof SplFileInfo) {
                continue;
            }
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        @rmdir($path);
    }
}
