<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CacheHelpersTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/on4crd-cache-tests-' . bin2hex(random_bytes(4));
        @mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->tmpDir)) {
            return;
        }

        foreach ((array) glob($this->tmpDir . '/*') as $file) {
            @unlink((string) $file);
        }
        @rmdir($this->tmpDir);
    }

    public function testCacheSetAndGetRoundTrip(): void
    {
        $settings = ['enabled' => true, 'directory' => $this->tmpDir];

        self::assertTrue(cache_set('demo_key', ['ok' => true], 60, $settings));
        self::assertSame(['ok' => true], cache_get('demo_key', null, $settings));
    }

    public function testCacheRememberUsesCachedValueAfterFirstResolve(): void
    {
        $settings = ['enabled' => true, 'directory' => $this->tmpDir];
        $counter = 0;

        $first = cache_remember('counter_key', 60, static function () use (&$counter): int {
            $counter++;

            return $counter;
        }, $settings);

        $second = cache_remember('counter_key', 60, static function () use (&$counter): int {
            $counter++;

            return $counter;
        }, $settings);

        self::assertSame(1, $first);
        self::assertSame(1, $second);
        self::assertSame(1, $counter);
    }

    public function testCacheKeyNormalizationRemovesUnsafeCharacters(): void
    {
        self::assertSame('my_key_with_spaces', cache_key_normalize('my key with spaces'));
    }
}
