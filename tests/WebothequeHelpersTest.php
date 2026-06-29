<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class WebothequeHelpersTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $_SERVER = [];
        $_POST = [];
        $_GET = [];
    }

    public function testUrlNormalizationKeepsOnlyHttpUrls(): void
    {
        self::assertSame('https://example.org/path?q=1', webotheque_normalize_url(' example.org/path?q=1 '));
        self::assertSame('http://example.org', webotheque_normalize_url('http://example.org'));
        self::assertSame('', webotheque_normalize_url('ftp://example.org/file.zip'));
        self::assertSame('', webotheque_normalize_url('javascript:alert(1)'));
    }

    public function testDomainExtractionNormalizesWwwPrefix(): void
    {
        self::assertSame('example.org', webotheque_domain_from_url('https://www.Example.org/path'));
        self::assertSame('sub.example.org', webotheque_domain_from_url('https://sub.example.org/path'));
        self::assertSame('', webotheque_domain_from_url('not-a-url'));
    }

    public function testTagsAreSplitCleanedAndDeduplicated(): void
    {
        self::assertSame(
            [
                'ft8' => 'FT8',
                'antennas' => 'antennas',
                'propagation' => 'Propagation',
            ],
            webotheque_tags_from_text(' FT8, antennas; #FT8 ; Propagation ')
        );
    }

    public function testCategoryInputAcceptsKnownCodesOnly(): void
    {
        $categories = [
            'general' => 'General',
            'modes-numeriques' => 'Modes numeriques',
        ];

        self::assertSame('general', webotheque_category_from_input('', $categories));
        self::assertSame('modes-numeriques', webotheque_category_from_input('Modes numeriques', $categories));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('err_category');
        webotheque_category_from_input('Unknown topic', $categories);
    }

    public function testSubcategoryReferencesAreNormalizedAndParsed(): void
    {
        self::assertSame('general:references', webotheque_subcategory_ref('', 'Références'));
        self::assertSame('modes-numeriques:ft8:guides', webotheque_subsubcategory_ref('Modes numériques', 'FT8', 'Guides'));
        self::assertSame(
            ['category' => 'modes-numeriques', 'subcategory' => 'ft8'],
            webotheque_subcategory_ref_parts('Modes numériques:FT8')
        );
        self::assertSame(
            ['category' => 'modes-numeriques', 'subcategory' => 'ft8', 'subsubcategory' => 'guides'],
            webotheque_subsubcategory_ref_parts('Modes numériques:FT8:Guides')
        );
        self::assertSame(['category' => '', 'subcategory' => 'logiciels'], webotheque_subcategory_ref_parts('Logiciels'));
    }

    public function testTaxonomyInputRejectsSubcategoryFromAnotherWebothequeCategory(): void
    {
        $categories = [
            'general' => 'General',
            'modes' => 'Modes',
            'antennes' => 'Antennes',
        ];

        self::assertSame(['modes', '', ''], webotheque_taxonomy_from_input('modes', '', $categories));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('err_subcategory_category_mismatch');
        webotheque_taxonomy_from_input('modes', 'antennes:yagi', $categories);
    }

    public function testCardsEscapeContentAndExposeSafeExternalLinks(): void
    {
        $html = render_webotheque_cards([
            [
                'title' => '<b>Useful link</b>',
                'url' => 'https://www.example.org/path',
                'description' => '<script>alert(1)</script>Readable',
                'tags' => 'FT8',
                'category' => 'general',
            ],
        ], [
            'link' => 'Link',
            'open' => 'Open',
            'tags' => 'Tags',
            'domain_field' => 'Topic',
        ], [
            'general' => 'General',
        ]);

        self::assertStringContainsString('&lt;b&gt;Useful link&lt;/b&gt;', $html);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;Readable', $html);
        self::assertStringContainsString('href="https://www.example.org/path"', $html);
        self::assertStringContainsString('target="_blank" rel="noopener noreferrer"', $html);
        self::assertStringContainsString('example.org', $html);
        self::assertStringNotContainsString('<script>', $html);
    }

    public function testManagedCardsKeepEditActionWithoutFavoriteForm(): void
    {
        $html = render_webotheque_cards([
            [
                'id' => 42,
                'member_id' => 7,
                'title' => 'Managed link',
                'url' => 'https://admin.example.org',
                'description' => 'Editable from admin',
                'tags' => 'admin',
                'category' => 'general',
            ],
        ], [
            'link' => 'Link',
            'open' => 'Open',
            'tags' => 'Tags',
            'domain_field' => 'Topic',
            'subcategory_field' => 'Subtopic',
            'subsubcategory_field' => 'Sub-subtopic',
            'no_subcategory' => 'No subtopic',
            'no_subsubcategory' => 'No sub-subtopic',
            'edit_link' => 'Edit',
            'edit_link_title' => 'Edit link',
            'title_field' => 'Title',
            'url_field' => 'URL',
            'description_field' => 'Description',
            'tags_field' => 'Keywords',
            'save' => 'Save',
            'cancel' => 'Cancel',
            'delete_link_warning' => 'Delete warning',
            'delete_link' => 'Delete link',
        ], [
            'general' => 'General',
        ], null, true);

        self::assertStringContainsString('data-webotheque-modal-open="webotheque-edit-dialog-42"', $html);
        self::assertStringContainsString('name="action" value="update_link"', $html);
        self::assertStringContainsString('name="action" value="delete_link"', $html);
        self::assertStringNotContainsString('toggle_favorite_link', $html);
    }

    public function testLinkFieldsRenderCategorySelectKeywordsAndOptionalContact(): void
    {
        $labels = [
            'title_field' => 'Title',
            'url_field' => 'URL',
            'domain_field' => 'Topic',
            'description_field' => 'Description',
            'tags_field' => 'Keywords',
            'contact_field' => 'Contact',
        ];
        $categories = [
            'general' => 'General',
            'modes' => 'Modes',
        ];

        $memberHtml = render_webotheque_link_fields($labels, $categories, 'member@example.test');
        $adminHtml = render_webotheque_link_fields($labels, $categories);

        self::assertStringContainsString('<select name="category">', $memberHtml);
        self::assertStringContainsString('name="subcategory_ref"', $memberHtml);
        self::assertStringContainsString('name="subsubcategory_ref"', $memberHtml);
        self::assertStringContainsString('<option value="modes">Modes</option>', $memberHtml);
        self::assertStringContainsString('name="tags"', $memberHtml);
        self::assertStringContainsString('name="proposal_contact"', $memberHtml);
        self::assertStringNotContainsString('name="proposal_contact"', $adminHtml);
    }
}
