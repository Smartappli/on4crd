<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ModuleTaxonomyHelpersTest extends TestCase
{
    public function testSharedDocumentTaxonomyReferencesAreNormalized(): void
    {
        self::assertSame('general:cours', member_document_subcategory_ref('', 'Cours'));
        self::assertSame(
            ['category' => 'formation', 'subcategory' => 'examens'],
            member_document_subcategory_ref_parts('Formation:Examens')
        );
        self::assertSame('Favoris', member_document_favorites_label(['favorite' => 'Favori'], 'fr'));
        self::assertSame('Favorites', member_document_favorites_label([], 'en'));
    }

    public function testAlbumTaxonomyReferencesAreNormalized(): void
    {
        self::assertSame('general:archives', album_subcategory_ref('', 'Archives'));
        self::assertSame(
            ['category' => 'activites', 'subcategory' => 'fieldday'],
            album_subcategory_ref_parts('Activites:Fieldday')
        );
        self::assertArrayHasKey('general', album_default_categories());
    }

    public function testWikiTaxonomyReferencesAreNormalized(): void
    {
        self::assertSame('general:procedures', wiki_subcategory_ref('', 'Procédures'));
        self::assertSame(
            ['category' => 'technique', 'subcategory' => 'antennes'],
            wiki_subcategory_ref_parts('Technique:Antennes')
        );
        self::assertSame('Favoris', wiki_favorites_label(['favorite' => 'Favori'], 'fr'));
        self::assertSame('Favorites', wiki_favorites_label([], 'en'));
    }
}
