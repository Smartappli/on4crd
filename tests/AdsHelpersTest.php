<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AdsHelpersTest extends TestCase
{
    public function testAvailableAdPlacementsExposeFormCompatibleFields(): void
    {
        $placements = available_ad_placements();

        self::assertNotEmpty($placements);
        foreach ($placements as $placement) {
            self::assertArrayHasKey('id', $placement);
            self::assertArrayHasKey('code', $placement);
            self::assertArrayHasKey('name', $placement);
            self::assertArrayHasKey('label', $placement);
            self::assertArrayHasKey('description', $placement);
            self::assertIsInt($placement['id']);
            self::assertIsString($placement['code']);
            self::assertIsString($placement['name']);
        }
    }

    public function testAdSummaryStatsReturnZeroContractForMissingAd(): void
    {
        self::assertSame([
            'impressions' => 0,
            'clicks' => 0,
            'ctr' => 0.0,
            'unique_viewers' => 0,
        ], ad_summary_stats(0));
    }

    public function testAdDailyStatsReturnEmptyContractForMissingAd(): void
    {
        self::assertSame([], ad_daily_stats(0));
    }
}
