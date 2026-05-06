<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ChatbotRagRetrievalTest extends TestCase
{
    public function testRagTokensDeduplicatesAndFiltersShortTokens(): void
    {
        $tokens = rag_tokens('  QSL qsl / 73 ANTENNA de  ');

        self::assertContains('qsl', $tokens);
        self::assertContains('antenna', $tokens);
        self::assertNotContains('de', $tokens);
    }

    public function testRagOverlapScoreRewardsMatches(): void
    {
        $queryTokens = rag_tokens('qsl antenna');

        $scoreWithMatches = rag_overlap_score($queryTokens, 'This article explains qsl cards and antenna tuning.');
        $scoreWithoutMatches = rag_overlap_score($queryTokens, 'Propagation and weather only.');

        self::assertGreaterThan($scoreWithoutMatches, $scoreWithMatches);
        self::assertGreaterThan(0.0, $scoreWithMatches);
    }

    public function testRagWeightedScorePrioritizesWholeWordAndPhraseMatches(): void
    {
        $queryTokens = rag_tokens('qsl antenna');

        $exactPhraseScore = rag_weighted_score($queryTokens, 'This guide covers qsl antenna setup and tuning basics.');
        $partialOnlyScore = rag_weighted_score($queryTokens, 'Antennae and qsl-like examples are listed here.');

        self::assertGreaterThan($exactPhraseScore, $partialOnlyScore);
        self::assertGreaterThan(0.0, $exactPhraseScore);
    }
}
