<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class FrenchTranslationsAccentContractTest extends TestCase
{
    public function testFrenchCatalogsDoNotContainReplacementCharactersInsideWords(): void
    {
        $i18nDirectory = new RecursiveDirectoryIterator(__DIR__ . '/../app/i18n', FilesystemIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($i18nDirectory);
        $checked = 0;

        foreach ($files as $file) {
            if (!$file instanceof SplFileInfo || $file->getFilename() !== 'fr.php') {
                continue;
            }
            $checked++;
            $content = file_get_contents($file->getPathname());
            self::assertIsString($content);
            self::assertDoesNotMatchRegularExpression(
                '/\p{L}\?\p{L}/u',
                $content,
                sprintf('French translation contains a replacement marker: %s', $file->getPathname())
            );
        }

        self::assertGreaterThan(0, $checked);
    }

    public function testFrenchEncodingScriptChecksAccentReplacementMarkers(): void
    {
        $script = file_get_contents(__DIR__ . '/../scripts/check_i18n_encoding.php');
        self::assertIsString($script);
        self::assertStringContainsString('french_replacement_between_letters', $script);
        self::assertStringContainsString("basename(\$path) === 'fr.php'", $script);
    }
}
