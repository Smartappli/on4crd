<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class UserExperienceContractTest extends TestCase
{
    public function testSearchExposesItsStateAndActiveSourceToAssistiveTechnology(): void
    {
        $search = $this->source('pages/search.php');

        self::assertStringContainsString('class="site-search-box" role="search"', $search);
        self::assertStringContainsString('role="status" aria-live="polite" aria-atomic="true"', $search);
        self::assertStringContainsString("' aria-current=\"page\"'", $search);
    }

    public function testSharedStylesRespectMotionPreferencesAndThemeContrast(): void
    {
        $styles = $this->source('assets/css/app.css');

        self::assertStringContainsString('@media (prefers-reduced-motion: reduce)', $styles);
        self::assertStringContainsString('.site-search-result-card:focus-within', $styles);
        self::assertStringContainsString('.site-search-box label span {\n  color: var(--muted);', $styles);
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents(__DIR__ . '/../' . $relativePath);
        self::assertIsString($source);

        return $source;
    }
}
