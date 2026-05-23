<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class WaveHardeningHelpersTest extends TestCase
{
    public function testLibraryControlledVocabularyContainsCoreTerms(): void
    {
        $vocabulary = library_controlled_vocabulary_list();
        self::assertContains('formation', $vocabulary);
        self::assertContains('securite', $vocabulary);
        self::assertContains('technique', $vocabulary);
    }

    public function testLibraryIngestionTemplatesMapContainsExpectedTemplates(): void
    {
        $templates = library_ingestion_templates_map();
        self::assertArrayHasKey('training', $templates);
        self::assertArrayHasKey('safety', $templates);
        self::assertArrayHasKey('technical', $templates);
        self::assertArrayHasKey('legal', $templates);
    }

    public function testLibraryFilterControlledTagsKeepsOnlyAllowedTags(): void
    {
        $filtered = library_filter_controlled_tags(['formation', ' unknown ', 'securite', '', 'technique']);
        self::assertSame(['formation', 'securite', 'technique'], $filtered);
    }

    public function testEditorialBlockedReasonsFromArticleDetectsMissingFields(): void
    {
        $reasons = editorial_blocked_reasons_from_article([
            'title' => '',
            'content' => '',
            'status' => 'scheduled',
            'scheduled_at' => '',
        ]);

        self::assertContains('missing_title', $reasons);
        self::assertContains('missing_content', $reasons);
        self::assertContains('missing_schedule_date', $reasons);
    }

    public function testEditorialBlockedReasonsFromArticleDetectsPastSchedule(): void
    {
        $reasons = editorial_blocked_reasons_from_article([
            'title' => 'Valid',
            'content' => '<p>Valid content</p>',
            'status' => 'scheduled',
            'scheduled_at' => date('Y-m-d H:i:s', time() - 3600),
        ]);

        self::assertContains('stuck_in_past_schedule', $reasons);
    }
}

