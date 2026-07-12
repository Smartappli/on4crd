<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class MobileExperienceContractTest extends TestCase
{
    public function testMobileMenuUsesMeasuredHeaderAndKeyboardFocusManagement(): void
    {
        $styles = $this->source('assets/css/app.css');
        $script = $this->source('assets/js/app.js');

        self::assertStringContainsString('--mobile-header-height', $styles);
        self::assertStringContainsString('100dvh', $styles);
        self::assertStringContainsString('ResizeObserver', $script);
        self::assertStringContainsString('focusBeforeMenu', $script);
        self::assertStringContainsString("event.key === 'Tab'", $script);
    }

    public function testMobileFormsAndTablesAvoidCommonTouchProblems(): void
    {
        $styles = $this->source('assets/css/app.css');

        self::assertStringContainsString('font-size: 16px', $styles);
        self::assertStringContainsString('overscroll-behavior-x: contain', $styles);
        self::assertStringContainsString('touch-action: pan-x pan-y', $styles);
    }

    public function testCalendarsPreferAReadableListOnSmallScreens(): void
    {
        $events = $this->source('assets/js/modules/events.js');
        $adminEvents = $this->source('assets/js/modules/admin_events.js');

        self::assertStringContainsString("max-width: 760px", $events);
        self::assertStringContainsString("'listMonth'", $events);
        self::assertStringContainsString("'listMonth'", $adminEvents);
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents(__DIR__ . '/../' . $relativePath);
        self::assertIsString($source);

        return $source;
    }
}
