<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class MaintenanceModeTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $_GET = [];
    }

    public function testMaintenanceDisabledDoesNotBlockRoute(): void
    {
        $settings = ['enabled' => false];

        self::assertFalse(maintenance_should_block_route('home', $settings));
    }

    public function testMaintenanceBlocksNonAllowedRoute(): void
    {
        $settings = [
            'enabled' => true,
            'allowed_routes' => ['login', 'robots.txt'],
            'secret' => '',
        ];

        self::assertTrue(maintenance_should_block_route('home', $settings));
        self::assertFalse(maintenance_should_block_route('login', $settings));
    }

    public function testValidBypassTokenGrantsSessionBypass(): void
    {
        $settings = [
            'enabled' => true,
            'allowed_routes' => ['login'],
            'secret' => 'club-secret',
        ];

        $_GET['maintenance_bypass'] = 'club-secret';

        self::assertFalse(maintenance_should_block_route('home', $settings));
        self::assertTrue(maintenance_has_bypass());

        $_GET = [];
        self::assertFalse(maintenance_should_block_route('admin', $settings));
    }

    public function testInvalidBypassTokenDoesNotGrantAccess(): void
    {
        $settings = [
            'enabled' => true,
            'allowed_routes' => ['login'],
            'secret' => 'club-secret',
        ];

        $_GET['maintenance_bypass'] = 'wrong';

        self::assertTrue(maintenance_should_block_route('home', $settings));
        self::assertFalse(maintenance_has_bypass());
    }
}
