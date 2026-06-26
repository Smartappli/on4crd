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

    public function testWebothequeTaxonomyReferencesAreNormalized(): void
    {
        self::assertSame('general:references', webotheque_subcategory_ref('', 'References'));
        self::assertSame(
            ['category' => 'modes-numeriques', 'subcategory' => 'ft8'],
            webotheque_subcategory_ref_parts('Modes numeriques:FT8')
        );
        self::assertSame('Mes favoris', webotheque_favorites_label(['favorites' => 'Mes favoris'], 'fr'));
        self::assertSame('Favorites', webotheque_favorites_label([], 'en'));
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

    public function testArticleTaxonomyReferencesAreNormalized(): void
    {
        self::assertSame('autres:reglementation', article_subcategory_ref('', 'Reglementation'));
        self::assertSame(
            ['category' => 'antennes', 'subcategory' => 'yagi'],
            article_subcategory_ref_parts('Antennes:Yagi')
        );
        self::assertSame('Mes favoris', article_favorites_label(['favorites' => 'Mes favoris'], 'fr'));
        self::assertSame('Favorites', article_favorites_label([], 'en'));
        self::assertArrayHasKey('autres', article_default_categories());
    }

    public function testWikiTaxonomyReferencesAreNormalized(): void
    {
        self::assertSame('general:procedures', wiki_subcategory_ref('', 'Procédures'));
        self::assertSame('general:procedures:antennes', wiki_subsubcategory_ref('', 'Procédures', 'Antennes'));
        self::assertSame(
            ['category' => 'technique', 'subcategory' => 'antennes'],
            wiki_subcategory_ref_parts('Technique:Antennes')
        );
        self::assertSame(
            ['category' => 'technique', 'subcategory' => 'antennes', 'subsubcategory' => 'yagi'],
            wiki_subsubcategory_ref_parts('Technique:Antennes:Yagi')
        );
        self::assertSame('Favoris', wiki_favorites_label(['favorite' => 'Favori'], 'fr'));
        self::assertSame('Favorites', wiki_favorites_label([], 'en'));
    }

    public function testContentTaxonomyCodeTransliteratesAccentsAndSupportsEmptyCodes(): void
    {
        self::assertSame('sous-thematique', content_taxonomy_code('Sous thématique'));
        self::assertSame('', content_taxonomy_code('', 120, 'fallback', true));
        self::assertSame('fallback', content_taxonomy_code('', 120, 'fallback'));
        self::assertSame('long', content_taxonomy_code('long-code', 4));
    }
}
