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
        self::assertSame(
            'storage/uploads/albums/abcdef123456.jpg',
            album_photo_public_path_or_null('/var/www/on4crd/storage/app/public/uploads/albums/abcdef123456.jpg')
        );

        self::assertNull(album_photo_public_path_or_null('../private/secret.jpg'));
        self::assertNull(album_photo_public_path_or_null('storage/uploads/library/doc.pdf'));
    }

    public function testAlbumUploadBatchFromFilesNormalizesMultipleFiles(): void
    {
        $batch = album_upload_batch_from_files([
            'name' => ['one.jpg', 'ignored.png', 'two.webp'],
            'type' => ['image/jpeg', 'image/png', 'image/webp'],
            'tmp_name' => ['tmp1', '', 'tmp2'],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE, UPLOAD_ERR_OK],
            'size' => [123, 0, 456],
        ]);

        self::assertCount(2, $batch);
        self::assertSame('one.jpg', $batch[0]['name']);
        self::assertSame('two.webp', $batch[1]['name']);
        self::assertSame([], album_upload_batch_from_files(['error' => UPLOAD_ERR_NO_FILE]));
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

    public function testTaxonomyInputRejectsSubcategoryFromAnotherAlbumCategory(): void
    {
        $categories = [
            'general' => 'General',
            'radio' => 'Radio',
            'events' => 'Events',
        ];

        self::assertSame(['radio', ''], album_taxonomy_from_input('radio', '', $categories));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('La sous-thématique sélectionnée ne correspond pas à la thématique choisie.');
        album_taxonomy_from_input('radio', 'events:field-day', $categories);
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

    public function testAlbumListingSectionsHideFeaturedSectionWhenNoAlbumIsPinned(): void
    {
        $sections = album_listing_sections(
            [],
            [
                ['id' => 10, 'title' => 'Regular album'],
            ],
            'Album à la une',
            'Autres albums'
        );

        self::assertCount(1, $sections);
        self::assertFalse($sections[0]['featured']);
        self::assertSame('', $sections[0]['title']);
        self::assertSame(10, $sections[0]['rows'][0]['id']);
    }

    public function testAlbumListingSectionsPutPinnedAlbumsBeforeRegularAlbums(): void
    {
        $sections = album_listing_sections(
            [
                ['id' => 7, 'title' => 'Featured album'],
            ],
            [
                ['id' => 3, 'title' => 'Regular album'],
            ],
            'Album à la une',
            'Autres albums'
        );

        self::assertCount(2, $sections);
        self::assertTrue($sections[0]['featured']);
        self::assertSame('Album à la une', $sections[0]['title']);
        self::assertSame(7, $sections[0]['rows'][0]['id']);
        self::assertFalse($sections[1]['featured']);
        self::assertSame('Autres albums', $sections[1]['title']);
        self::assertSame(3, $sections[1]['rows'][0]['id']);
    }

    public function testAlbumListingSectionsReturnNoSectionForEmptyLists(): void
    {
        self::assertSame([], album_listing_sections([], [], 'Album à la une', 'Autres albums'));
    }

    public function testAlbumProposalSummaryExtractsDescriptionMetadataAndActions(): void
    {
        self::assertSame(
            "Club fieldday\n\nThématique: radio\nMots clés: ft8",
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
