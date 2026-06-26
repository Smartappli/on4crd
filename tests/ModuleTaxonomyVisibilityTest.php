<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ModuleTaxonomyVisibilityTest extends TestCase
{
    public function testWebothequeVisibleTaxonomyKeepsOnlyNonEmptyBranches(): void
    {
        self::assertSame(
            ['radio' => 'Radio'],
            webotheque_visible_categories(
                ['general' => 'General', 'radio' => 'Radio', 'empty' => 'Empty'],
                ['general' => 0, 'radio' => 2, 'empty' => 0]
            )
        );

        self::assertSame(
            [
                'radio' => [
                    ['category_code' => 'radio', 'code' => 'hf-band', 'label' => 'HF Band', 'total' => 3],
                ],
            ],
            webotheque_visible_subcategories_by_category(
                [
                    'radio' => [
                        ['category_code' => 'radio', 'code' => 'hf-band', 'label' => 'HF Band'],
                        ['category_code' => 'radio', 'code' => 'empty', 'label' => 'Empty'],
                        ['category_code' => 'radio', 'code' => '', 'label' => 'Blank'],
                    ],
                    'empty' => [
                        ['category_code' => 'empty', 'code' => 'archive', 'label' => 'Archive'],
                    ],
                ],
                ['radio:hf-band' => 3, 'radio:empty' => 0, 'empty:archive' => 0]
            )
        );

        self::assertSame(
            [
                'radio:hf-band' => [
                    ['category_code' => 'radio', 'subcategory_code' => 'hf-band', 'code' => 'guides', 'label' => 'Guides', 'total' => 2],
                ],
            ],
            webotheque_visible_subsubcategories_by_parent(
                [
                    'radio:hf-band' => [
                        ['category_code' => 'radio', 'subcategory_code' => 'hf-band', 'code' => 'guides', 'label' => 'Guides'],
                        ['category_code' => 'radio', 'subcategory_code' => 'hf-band', 'code' => 'empty', 'label' => 'Empty'],
                    ],
                ],
                ['radio:hf-band:guides' => 2, 'radio:hf-band:empty' => 0]
            )
        );

        self::assertSame(
            [
                'technique:antennes' => [
                    ['category_code' => 'technique', 'subcategory_code' => 'antennes', 'code' => 'yagi', 'label' => 'Yagi', 'total' => 4],
                ],
            ],
            wiki_visible_subsubcategories_by_parent(
                [
                    'technique:antennes' => [
                        ['category_code' => 'technique', 'subcategory_code' => 'antennes', 'code' => 'yagi', 'label' => 'Yagi'],
                        ['category_code' => 'technique', 'subcategory_code' => 'antennes', 'code' => 'empty', 'label' => 'Empty'],
                    ],
                ],
                ['technique:antennes:yagi' => 4, 'technique:antennes:empty' => 0]
            )
        );
    }

    public function testAlbumsVisibleTaxonomyKeepsOnlyNonEmptyBranches(): void
    {
        self::assertSame(
            ['fieldday' => 'Fieldday'],
            album_visible_categories(
                ['general' => 'General', 'fieldday' => 'Fieldday', 'empty' => 'Empty'],
                ['general' => 0, 'fieldday' => 4, 'empty' => 0]
            )
        );

        self::assertSame(
            [
                'fieldday' => [
                    ['category_code' => 'fieldday', 'code' => '2026', 'label' => '2026', 'total' => 5],
                ],
            ],
            album_visible_subcategories_by_category(
                [
                    'fieldday' => [
                        ['category_code' => 'fieldday', 'code' => '2026', 'label' => '2026'],
                        ['category_code' => 'fieldday', 'code' => 'empty', 'label' => 'Empty'],
                    ],
                    'empty' => [
                        ['category_code' => 'empty', 'code' => 'archive', 'label' => 'Archive'],
                    ],
                ],
                ['fieldday:2026' => 5, 'fieldday:empty' => 0, 'empty:archive' => 0]
            )
        );
    }

    public function testWikiVisibleTaxonomyKeepsOnlyNonEmptyBranches(): void
    {
        self::assertSame(
            ['technique' => 'Technique'],
            wiki_visible_categories(
                ['general' => 'General', 'technique' => 'Technique', 'empty' => 'Empty'],
                ['general' => 0, 'technique' => 6, 'empty' => 0]
            )
        );

        self::assertSame(
            [
                'technique' => [
                    ['category_code' => 'technique', 'code' => 'antennes', 'label' => 'Antennes', 'total' => 2],
                ],
            ],
            wiki_visible_subcategories_by_category(
                [
                    'technique' => [
                        ['category_code' => 'technique', 'code' => 'antennes', 'label' => 'Antennes'],
                        ['category_code' => 'technique', 'code' => 'empty', 'label' => 'Empty'],
                    ],
                    'empty' => [
                        ['category_code' => 'empty', 'code' => 'archive', 'label' => 'Archive'],
                    ],
                ],
                ['technique:antennes' => 2, 'technique:empty' => 0, 'empty:archive' => 0]
            )
        );
    }

    public function testArticleVisibleTaxonomyKeepsOnlyNonEmptyBranches(): void
    {
        self::assertSame(
            ['antennes' => 'Antennes'],
            article_visible_categories(
                ['autres' => 'Autres', 'antennes' => 'Antennes', 'empty' => 'Empty'],
                ['autres' => 0, 'antennes' => 3, 'empty' => 0]
            )
        );

        self::assertSame(
            [
                'antennes' => [
                    ['category_code' => 'antennes', 'code' => 'yagi', 'label' => 'Yagi', 'total' => 2],
                ],
            ],
            article_visible_subcategories_by_category(
                [
                    'antennes' => [
                        ['category_code' => 'antennes', 'code' => 'yagi', 'label' => 'Yagi'],
                        ['category_code' => 'antennes', 'code' => 'empty', 'label' => 'Empty'],
                    ],
                    'empty' => [
                        ['category_code' => 'empty', 'code' => 'archive', 'label' => 'Archive'],
                    ],
                ],
                ['antennes:yagi' => 2, 'antennes:empty' => 0, 'empty:archive' => 0]
            )
        );
    }

    public function testSharedDocumentVisibleTaxonomyKeepsOnlyNonEmptyBranches(): void
    {
        self::assertSame(
            ['formation' => 'Formation'],
            member_document_visible_categories(
                ['general' => 'General', 'formation' => 'Formation', 'empty' => 'Empty'],
                ['general' => 0, 'formation' => 7, 'empty' => 0]
            )
        );

        self::assertSame(
            [
                'formation' => [
                    ['category_code' => 'formation', 'code' => 'cours', 'label' => 'Cours', 'total' => 4],
                ],
            ],
            member_document_visible_subcategories_by_category(
                [
                    'formation' => [
                        ['category_code' => 'formation', 'code' => 'cours', 'label' => 'Cours'],
                        ['category_code' => 'formation', 'code' => 'empty', 'label' => 'Empty'],
                    ],
                    'empty' => [
                        ['category_code' => 'empty', 'code' => 'archive', 'label' => 'Archive'],
                    ],
                ],
                ['formation:cours' => 4, 'formation:empty' => 0, 'empty:archive' => 0]
            )
        );
    }
}
