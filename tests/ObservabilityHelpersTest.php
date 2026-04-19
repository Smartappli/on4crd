<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ObservabilityHelpersTest extends TestCase
{
    public function testBuildSafeErrorMessageWithoutDetails(): void
    {
        $message = observability_build_safe_error_message(new RuntimeException('Sensitive detail'), 'req-123', false);

        self::assertStringContainsString('Une erreur interne est survenue.', $message);
        self::assertStringContainsString('Référence: req-123.', $message);
        self::assertStringNotContainsString('Sensitive detail', $message);
    }

    public function testBuildSafeErrorMessageWithDetails(): void
    {
        $message = observability_build_safe_error_message(new RuntimeException('Sensitive detail'), 'req-123', true);

        self::assertStringContainsString('Sensitive detail', $message);
    }
}
