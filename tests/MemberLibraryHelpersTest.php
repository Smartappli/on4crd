<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class MemberLibraryHelpersTest extends TestCase
{
    public function testVisibleTaxonomyKeepsOnlyNonEmptyCategoriesAndSubcategories(): void
    {
        $categories = [
            ['category' => 'general', 'label' => 'General', 'total' => 0],
            ['category' => 'formation', 'label' => 'Formation', 'total' => 3],
            ['category' => 'technique', 'label' => 'Technique', 'total' => 1],
        ];
        $subcategories = [
            'formation' => [
                ['code' => 'licence', 'label' => 'Licence', 'total' => 2],
                ['code' => 'empty', 'label' => 'Empty', 'total' => 0],
            ],
            'technique' => [
                ['code' => 'antennes', 'label' => 'Antennes', 'total' => 0],
            ],
        ];

        self::assertSame(
            [
                ['category' => 'formation', 'label' => 'Formation', 'total' => 3],
                ['category' => 'technique', 'label' => 'Technique', 'total' => 1],
            ],
            member_library_visible_categories($categories)
        );
        self::assertSame(
            [
                'formation' => [
                    ['code' => 'licence', 'label' => 'Licence', 'total' => 2],
                ],
            ],
            member_library_visible_subcategories_by_category($subcategories)
        );
    }

    public function testSubcategoryReferencesNormalizeAndRoundTrip(): void
    {
        self::assertSame('formation:hf-antennas', member_library_subcategory_ref('Formation', 'HF Antennas'));
        self::assertSame(
            ['category' => 'formation', 'subcategory' => 'hf-antennas'],
            member_library_subcategory_ref_parts(' Formation : HF Antennas ')
        );
        self::assertSame(
            ['category' => '', 'subcategory' => 'hf-antennas'],
            member_library_subcategory_ref_parts('HF Antennas')
        );
    }

    public function testProposalSummaryExtractsTopicSubtopicTagsAndDescription(): void
    {
        $summary = "Category: technique\nSubcategory: HF Antennas\nTags: technique,unknown,antenne\nDescription: First line\nSecond line";

        self::assertSame('technique', member_library_proposal_category_from_summary($summary));
        self::assertSame('hf-antennas', member_library_proposal_subcategory_from_summary($summary));
        self::assertSame('technique,antenne', member_library_proposal_tags_from_summary($summary));
        self::assertSame("First line\nSecond line", member_library_proposal_description_from_summary($summary));
    }

    public function testDocumentProposalActionsAndIdsAreParsedStrictly(): void
    {
        self::assertSame('update_document', member_library_document_proposal_action("Action: update_document\nDocument ID: 42"));
        self::assertSame('delete_document', member_library_document_proposal_action("Action: delete_document\nDocument ID: 42"));
        self::assertSame('', member_library_document_proposal_action('Action: publish_document'));
        self::assertSame(42, member_library_document_proposal_document_id("Action: update_document\nDocument ID: 42"));
        self::assertSame(0, member_library_document_proposal_document_id("Action: update_document\nDocument ID: abc"));
    }

    public function testCleanTagsKeepsControlledVocabularyDeduplicatedAndLimited(): void
    {
        self::assertSame('formation,securite,technique', member_library_clean_tags(' formation,unknown,securite,formation,technique '));
    }
}
