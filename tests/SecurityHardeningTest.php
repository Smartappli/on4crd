<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SecurityHardeningTest extends TestCase
{
    public function testRootHtaccessBlocksDirectAccessToInternalApplicationDirectories(): void
    {
        $contents = file_get_contents(__DIR__ . '/../.htaccess');
        self::assertIsString($contents);

        self::assertStringContainsString(
            '^(?:app|config|pages|schema|tests|tools|vendor|storage/auth|storage/cache|storage/private)(?:/|$)',
            $contents
        );
    }

    public function testStorageHtaccessForcesActiveContentToDownload(): void
    {
        foreach (['storage/.htaccess', 'storage/uploads/.htaccess', 'storage/press/.htaccess'] as $relativePath) {
            $contents = file_get_contents(__DIR__ . '/../' . $relativePath);
            self::assertIsString($contents);

            self::assertStringContainsString('FilesMatch "\\.(?:php|phtml|phar|cgi|pl|py|sh|exe)$"', $contents, $relativePath);
            self::assertStringContainsString('Require all denied', $contents, $relativePath);
            self::assertStringContainsString('FilesMatch "\\.(?:html?|svg|xml)$"', $contents, $relativePath);
            self::assertStringContainsString('ForceType application/octet-stream', $contents, $relativePath);
            self::assertStringContainsString('Header set Content-Disposition "attachment"', $contents, $relativePath);
            self::assertStringContainsString('Header set X-Content-Type-Options "nosniff"', $contents, $relativePath);
        }
    }

    public function testPostHandlersKeepCsrfVerification(): void
    {
        $roots = [__DIR__ . '/../pages', __DIR__ . '/../app'];

        foreach ($roots as $root) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $fileInfo) {
                if (!$fileInfo instanceof SplFileInfo || $fileInfo->getExtension() !== 'php') {
                    continue;
                }

                $path = $fileInfo->getPathname();
                $contents = file_get_contents($path);
                self::assertIsString($contents);

                $handlesPost = str_contains($contents, '$_POST')
                    || preg_match('/REQUEST_METHOD.{0,160}POST|POST.{0,160}REQUEST_METHOD/s', $contents) === 1;
                if (!$handlesPost) {
                    continue;
                }

                self::assertStringContainsString('verify_csrf(', $contents, sprintf('POST handler without CSRF verification: %s', $path));
            }
        }
    }
}
