<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AlbumHelpersTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $_SERVER = [];
        $_POST = [];
        $_GET = [];
    }

    public function testThumbnailPathUsesHashedImageBasenameAndJpgExtension(): void
    {
        self::assertSame(
            'storage/uploads/albums/thumbs/abcdef123456.jpg',
            album_thumbnail_public_path('storage/uploads/albums/abcdef123456.webp')
        );
        self::assertSame(
            'storage/uploads/albums/thumbs/photo.jpg',
            album_thumbnail_public_path('/storage/uploads/albums/photo.png')
        );
    }

    public function testAlbumPhotoPublicPathAcceptsStoredPathVariants(): void
    {
        self::assertSame(
            'storage/uploads/albums/abcdef123456.jpg',
            album_photo_public_path_or_null('storage/uploads/albums/abcdef123456.jpg')
        );
        self::assertSame(
            'storage/uploads/albums/abcdef123456.jpg',
            album_photo_public_path_or_null('uploads/albums/abcdef123456.jpg')
        );
        self::assertSame(
            'storage/uploads/albums/abcdef123456.jpg',
            album_photo_public_path_or_null('abcdef123456.jpg')
        );
        self::assertSame(
            'storage/uploads/albums/abcdef123456.jpg',
            album_photo_public_path_or_null('https://on4crdsite.smartappli.eu/storage/uploads/albums/abcdef123456.jpg')
        );
        self::assertSame(
            'storage/uploads/albums/abcdef123456.jpg',
            album_photo_public_path_or_null('/var/www/on4crd/storage/uploads/albums/abcdef123456.jpg')
        );

        self::assertNull(album_photo_public_path_or_null('../private/secret.jpg'));
        self::assertNull(album_photo_public_path_or_null('storage/uploads/library/doc.pdf'));
    }

    public function testSubcategoryReferencesNormalizeAndParseParentCategory(): void
    {
        self::assertSame('general:field-day', album_subcategory_ref('', 'Field Day'));
        self::assertSame('radio:vhf', album_subcategory_ref('Radio', 'VHF'));
        self::assertSame('', album_subcategory_ref('Radio', ''));

        self::assertSame(
            ['category' => 'radio', 'subcategory' => 'field-day'],
            album_subcategory_ref_parts('Radio:Field Day')
        );
        self::assertSame(
            ['category' => '', 'subcategory' => 'field-day'],
            album_subcategory_ref_parts('Field Day')
        );
    }

    public function testCategoryInputRejectsUnknownAlbumCategories(): void
    {
        $categories = [
            'general' => 'General',
            'radio' => 'Radio',
        ];

        self::assertSame('general', album_category_from_input('', $categories));
        self::assertSame('radio', album_category_from_input('Radio', $categories));

        $this->expectException(RuntimeException::class);
        album_category_from_input('Unknown', $categories);
    }

    public function testVisibleTaxonomyHidesEmptyCategoriesAndSubcategories(): void
    {
        self::assertSame(
            ['radio' => 'Radio'],
            album_visible_categories(
                ['general' => 'General', 'radio' => 'Radio', 'events' => 'Events'],
                ['radio' => 2, 'events' => 0]
            )
        );

        $visible = album_visible_subcategories_by_category(
            [
                'radio' => [
                    ['code' => 'vhf', 'label' => 'VHF'],
                    ['code' => 'hf', 'label' => 'HF'],
                ],
                'events' => [
                    ['code' => 'meeting', 'label' => 'Meeting'],
                ],
            ],
            ['radio:vhf' => 3, 'radio:hf' => 0]
        );

        self::assertArrayHasKey('radio', $visible);
        self::assertCount(1, $visible['radio']);
        self::assertSame('vhf', $visible['radio'][0]['code']);
        self::assertSame(3, $visible['radio'][0]['total']);
        self::assertArrayNotHasKey('events', $visible);
    }

    public function testAlbumProposalSummaryExtractsDescriptionMetadataAndActions(): void
    {
        self::assertSame(
            "Club fieldday\n\nThematique: radio\nMots cles: ft8",
            album_proposal_description_from_summary("Thematique: radio\nMots cles: ft8\nDescription: Club fieldday")
        );
        self::assertNull(album_proposal_description_from_summary(''));
        self::assertSame('update_album', album_proposal_action("Action: update_album\nAlbum ID: 12"));
        self::assertSame('delete_album', album_proposal_action("Action: delete_album\nAlbum ID: 12"));
        self::assertSame('', album_proposal_action('Action: publish_album'));
        self::assertSame(12, album_proposal_album_id("Action: update_album\nAlbum ID: 12"));
    }

    public function testTaxonomyFieldRendererEscapesLabelsAndKeepsCurrentSelection(): void
    {
        $html = render_album_taxonomy_fields(
            [
                'general' => 'General',
                'radio' => '<Radio>',
            ],
            [
                'category_field' => 'Topic',
                'subcategory_field' => 'Subtopic',
                'no_subcategory' => 'No subtopic',
            ],
            'radio'
        );

        self::assertStringContainsString('<select name="category">', $html);
        self::assertStringContainsString('<select name="subcategory_ref">', $html);
        self::assertStringContainsString('<option value="radio" selected>&lt;Radio&gt;</option>', $html);
        self::assertStringContainsString('<option value="">No subtopic</option>', $html);
        self::assertStringNotContainsString('<Radio>', $html);
    }
}
