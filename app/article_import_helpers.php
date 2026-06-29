<?php
declare(strict_types=1);

if (!function_exists('article_import_text_to_html')) {
function article_import_text_to_html(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", trim($text));
    $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
    if ($text === '') {
        return '';
    }

    $lines = explode("\n", $text);
    $html = [];
    $paragraph = [];
    $listType = null;

    $flushParagraph = static function () use (&$html, &$paragraph): void {
        $content = trim(implode(' ', $paragraph));
        $paragraph = [];
        if ($content !== '') {
            $html[] = '<p>' . e($content) . '</p>';
        }
    };
    $closeList = static function () use (&$html, &$listType): void {
        if ($listType !== null) {
            $html[] = '</' . $listType . '>';
            $listType = null;
        }
    };

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            $flushParagraph();
            $closeList();
            continue;
        }

        if (preg_match('/^#{1,6}\s+(.+)$/u', $line, $matches)) {
            $flushParagraph();
            $closeList();
            $level = min(4, max(2, strspn($line, '#') + 1));
            $html[] = '<h' . $level . '>' . e(trim($matches[1])) . '</h' . $level . '>';
            continue;
        }

        if (preg_match('/^(?:[-*•])\s+(.+)$/u', $line, $matches)) {
            $flushParagraph();
            if ($listType !== 'ul') {
                $closeList();
                $listType = 'ul';
                $html[] = '<ul>';
            }
            $html[] = '<li>' . e(trim($matches[1])) . '</li>';
            continue;
        }

        if (preg_match('/^\d+[\.)]\s+(.+)$/u', $line, $matches)) {
            $flushParagraph();
            if ($listType !== 'ol') {
                $closeList();
                $listType = 'ol';
                $html[] = '<ol>';
            }
            $html[] = '<li>' . e(trim($matches[1])) . '</li>';
            continue;
        }

        $closeList();
        $paragraph[] = $line;
    }

    $flushParagraph();
    $closeList();

    return sanitize_rich_html(implode("\n", $html));
}
}

if (!function_exists('article_extract_docx_html')) {
function article_extract_docx_html(string $path): string
{
    if (!is_file($path)) {
        return '';
    }

    $xml = article_docx_part_contents($path, 'word/document.xml');
    if ($xml === '') {
        return '';
    }

    $dom = new DOMDocument();
    $previousUseInternalErrors = libxml_use_internal_errors(true);
    $loaded = $dom->loadXML($xml);
    libxml_clear_errors();
    libxml_use_internal_errors($previousUseInternalErrors);
    if (!$loaded) {
        return article_import_text_to_html(strip_tags($xml));
    }

    $xpath = new DOMXPath($dom);
    $relationshipsXml = article_docx_part_contents($path, 'word/_rels/document.xml.rels');
    $relationships = article_docx_relationship_targets($relationshipsXml);
    $imageDataUris = article_docx_image_data_uris($path, $relationshipsXml);
    $numberingFormats = article_docx_numbering_formats(article_docx_part_contents($path, 'word/numbering.xml'));
    $altChunkHtmlByRelationshipId = article_docx_alt_chunk_html_by_relationship_id($path, $relationshipsXml);
    $noteBodies = [
        'footnote' => article_docx_note_bodies($path, 'footnote', $numberingFormats),
        'endnote' => article_docx_note_bodies($path, 'endnote', $numberingFormats),
    ];
    $referencedNotes = [];
    $headerFooterHtml = article_docx_header_footer_html($path, $relationshipsXml, $numberingFormats, $noteBodies, $referencedNotes);
    $bodyNodes = $xpath->query('/*[local-name()="document"]/*[local-name()="body"]/*');
    if (!$bodyNodes instanceof DOMNodeList) {
        return '';
    }

    $html = [];
    foreach ($headerFooterHtml['headers'] as $headerHtml) {
        $html[] = $headerHtml;
    }

    $openListTag = null;
    foreach ($bodyNodes as $block) {
        if (!$block instanceof DOMElement) {
            continue;
        }

        article_docx_append_block_html($block, $xpath, $relationships, $imageDataUris, $numberingFormats, $html, $openListTag, $noteBodies, $referencedNotes, $altChunkHtmlByRelationshipId);
    }

    if ($openListTag !== null) {
        $html[] = '</' . $openListTag . '>';
    }
    foreach ($headerFooterHtml['footers'] as $footerHtml) {
        $html[] = '<hr>' . $footerHtml;
    }

    $notesHtml = article_docx_referenced_notes_html($noteBodies, $referencedNotes);
    if ($notesHtml !== '') {
        $html[] = $notesHtml;
    }

    return sanitize_rich_html(implode("\n", $html));
}
}

if (!function_exists('article_docx_close_open_list')) {
/**
 * @param list<string> $html
 */
function article_docx_close_open_list(array &$html, ?string &$openListTag): void
{
    if ($openListTag === null) {
        return;
    }

    $html[] = '</' . $openListTag . '>';
    $openListTag = null;
}
}

if (!function_exists('article_docx_append_block_html')) {
/**
 * @param array<string,string> $relationships
 * @param array<string,string> $imageDataUris
 * @param array<string,array<string,string>> $numberingFormats
 * @param list<string> $html
 * @param array<string,array<string,string>> $noteBodies
 * @param array<string,array{type:string,id:string}>|null $referencedNotes
 * @param array<string,string> $altChunkHtmlByRelationshipId
 */
function article_docx_append_block_html(DOMElement $block, DOMXPath $xpath, array $relationships, array $imageDataUris, array $numberingFormats, array &$html, ?string &$openListTag, array $noteBodies = [], ?array &$referencedNotes = null, array $altChunkHtmlByRelationshipId = []): void
{
    $localName = $block->localName;
    if ($localName === 'altChunk') {
        article_docx_close_open_list($html, $openListTag);
        $relationshipId = article_docx_attribute($block, 'id');
        if ($relationshipId !== '' && isset($altChunkHtmlByRelationshipId[$relationshipId])) {
            $html[] = $altChunkHtmlByRelationshipId[$relationshipId];
        }
        return;
    }

    if ($localName === 'tbl') {
        article_docx_close_open_list($html, $openListTag);
        $table = article_docx_table_html($block, $xpath, $relationships, $imageDataUris, $noteBodies, $referencedNotes);
        if ($table !== '') {
            $html[] = $table;
        }
        return;
    }

    if ($localName === 'p') {
        $inlineHtml = article_docx_inline_html($block, $xpath, $relationships, $imageDataUris, $noteBodies, $referencedNotes);
        if (article_docx_html_is_empty($inlineHtml)) {
            article_docx_close_open_list($html, $openListTag);
            return;
        }

        $style = article_docx_paragraph_style($block, $xpath);
        $listTag = article_docx_paragraph_list_tag($block, $xpath, $numberingFormats);
        if ($listTag !== '') {
            if ($openListTag !== $listTag) {
                article_docx_close_open_list($html, $openListTag);
                $html[] = '<' . $listTag . '>';
                $openListTag = $listTag;
            }
            $html[] = '<li>' . $inlineHtml . '</li>';
            return;
        }

        article_docx_close_open_list($html, $openListTag);
        if (str_contains($style, 'heading') || str_contains($style, 'titre')) {
            $level = preg_match('/([1-6])/', $style, $matches) ? (int) $matches[1] + 1 : 2;
            $level = min(4, max(2, $level));
            $html[] = '<h' . $level . '>' . $inlineHtml . '</h' . $level . '>';
        } else {
            $html[] = '<p>' . $inlineHtml . '</p>';
        }
        return;
    }

    if ($localName === 'AlternateContent') {
        foreach (article_docx_alternate_content_candidates($block) as $candidateNode) {
            $candidateHtml = $html;
            $candidateOpenListTag = $openListTag;
            $candidateReferencedNotes = $referencedNotes;
            foreach ($candidateNode->childNodes as $child) {
                if ($child instanceof DOMElement) {
                    article_docx_append_block_html($child, $xpath, $relationships, $imageDataUris, $numberingFormats, $candidateHtml, $candidateOpenListTag, $noteBodies, $candidateReferencedNotes, $altChunkHtmlByRelationshipId);
                }
            }
            $addedHtml = implode('', array_slice($candidateHtml, count($html)));
            if (!article_docx_html_is_empty($addedHtml)) {
                $html = $candidateHtml;
                $openListTag = $candidateOpenListTag;
                $referencedNotes = $candidateReferencedNotes;
                return;
            }
        }
        return;
    }

    foreach ($block->childNodes as $child) {
        if (!$child instanceof DOMElement || str_ends_with($child->localName, 'Pr')) {
            continue;
        }
        article_docx_append_block_html($child, $xpath, $relationships, $imageDataUris, $numberingFormats, $html, $openListTag, $noteBodies, $referencedNotes, $altChunkHtmlByRelationshipId);
    }
}
}

if (!function_exists('article_docx_alternate_content_candidates')) {
/**
 * @return list<DOMElement>
 */
function article_docx_alternate_content_candidates(DOMElement $alternateContent): array
{
    $candidates = [];
    foreach (['Choice', 'Fallback'] as $candidateName) {
        foreach ($alternateContent->childNodes as $candidateNode) {
            if ($candidateNode instanceof DOMElement && $candidateNode->localName === $candidateName) {
                $candidates[] = $candidateNode;
            }
        }
    }

    return $candidates;
}
}

if (!function_exists('article_docx_alt_chunk_html_by_relationship_id')) {
/**
 * @return array<string,string>
 */
function article_docx_alt_chunk_html_by_relationship_id(string $path, string $relationshipsXml, string $baseDirectory = 'word'): array
{
    if ($relationshipsXml === '' || !is_file($path)) {
        return [];
    }

    $relationships = article_docx_relationship_targets($relationshipsXml);
    $chunks = [];
    foreach ($relationships as $relationshipId => $target) {
        $partName = article_docx_relationship_part_name($baseDirectory, $target);
        if ($partName === '') {
            continue;
        }

        $extension = strtolower(pathinfo($partName, PATHINFO_EXTENSION));
        if (!in_array($extension, ['html', 'htm', 'xhtml', 'txt', 'md'], true)) {
            continue;
        }

        $content = article_docx_part_contents($path, $partName);
        if ($content === '' || strlen($content) > 2 * 1024 * 1024) {
            continue;
        }

        $html = in_array($extension, ['txt', 'md'], true)
            ? article_import_text_to_html($content)
            : sanitize_rich_html($content);
        if (!article_docx_html_is_empty($html)) {
            $chunks[$relationshipId] = $html;
        }
    }

    return $chunks;
}
}

if (!function_exists('article_docx_header_footer_html')) {
/**
 * @param array<string,array<string,string>> $numberingFormats
 * @param array<string,array<string,string>> $noteBodies
 * @param array<string,array{type:string,id:string}> $referencedNotes
 * @return array{headers:list<string>,footers:list<string>}
 */
function article_docx_header_footer_html(string $path, string $relationshipsXml, array $numberingFormats, array $noteBodies, array &$referencedNotes): array
{
    $relationships = article_docx_relationship_targets($relationshipsXml);
    $parts = ['headers' => [], 'footers' => []];
    $seenPartNames = [];
    $seenHtml = [];

    foreach ($relationships as $target) {
        $partName = article_docx_relationship_part_name('word', $target);
        if ($partName === '' || isset($seenPartNames[$partName])) {
            continue;
        }

        if (preg_match('#^word/header\d*\.xml$#i', $partName) === 1) {
            $bucket = 'headers';
        } elseif (preg_match('#^word/footer\d*\.xml$#i', $partName) === 1) {
            $bucket = 'footers';
        } else {
            continue;
        }

        $seenPartNames[$partName] = true;
        $html = article_docx_wordprocessing_part_html($path, $partName, $numberingFormats, $noteBodies, $referencedNotes);
        if (article_docx_html_is_empty($html)) {
            continue;
        }

        $fingerprint = sha1($html);
        if (isset($seenHtml[$bucket][$fingerprint])) {
            continue;
        }
        $seenHtml[$bucket][$fingerprint] = true;
        $parts[$bucket][] = $html;
    }

    return $parts;
}
}

if (!function_exists('article_docx_wordprocessing_part_html')) {
/**
 * @param array<string,array<string,string>> $numberingFormats
 * @param array<string,array<string,string>> $noteBodies
 * @param array<string,array{type:string,id:string}> $referencedNotes
 */
function article_docx_wordprocessing_part_html(string $path, string $partName, array $numberingFormats, array $noteBodies, array &$referencedNotes): string
{
    $xml = article_docx_part_contents($path, $partName);
    if ($xml === '') {
        return '';
    }

    $dom = new DOMDocument();
    $previousUseInternalErrors = libxml_use_internal_errors(true);
    $loaded = $dom->loadXML($xml);
    libxml_clear_errors();
    libxml_use_internal_errors($previousUseInternalErrors);
    if (!$loaded) {
        return article_import_text_to_html(strip_tags($xml));
    }

    $relationshipsXml = article_docx_part_contents($path, article_docx_relationships_part_name($partName));
    $relationships = article_docx_relationship_targets($relationshipsXml);
    $imageDataUris = article_docx_image_data_uris($path, $relationshipsXml);
    $baseDirectory = article_docx_part_base_directory($partName);
    $altChunkHtmlByRelationshipId = article_docx_alt_chunk_html_by_relationship_id($path, $relationshipsXml, $baseDirectory);
    $xpath = new DOMXPath($dom);
    $blockNodes = $xpath->query('/*/*');
    if (!$blockNodes instanceof DOMNodeList) {
        return '';
    }

    $html = [];
    $openListTag = null;
    foreach ($blockNodes as $block) {
        if ($block instanceof DOMElement) {
            article_docx_append_block_html($block, $xpath, $relationships, $imageDataUris, $numberingFormats, $html, $openListTag, $noteBodies, $referencedNotes, $altChunkHtmlByRelationshipId);
        }
    }
    article_docx_close_open_list($html, $openListTag);

    return trim(implode("\n", $html));
}
}

if (!function_exists('article_docx_relationships_part_name')) {
function article_docx_relationships_part_name(string $partName): string
{
    $partName = trim(str_replace('\\', '/', $partName), '/');
    if ($partName === '' || str_contains($partName, '..')) {
        return '';
    }

    $directory = dirname($partName);
    $baseName = basename($partName);
    return ($directory === '.' ? '_rels' : $directory . '/_rels') . '/' . $baseName . '.rels';
}
}

if (!function_exists('article_docx_part_base_directory')) {
function article_docx_part_base_directory(string $partName): string
{
    $directory = trim(str_replace('\\', '/', dirname($partName)), '/');
    return $directory === '' || $directory === '.' ? 'word' : $directory;
}
}

if (!function_exists('article_docx_note_bodies')) {
/**
 * @param array<string,array<string,string>> $numberingFormats
 * @return array<string,string>
 */
function article_docx_note_bodies(string $path, string $noteType, array $numberingFormats = []): array
{
    $partBaseName = $noteType === 'endnote' ? 'endnotes' : 'footnotes';
    $nodeName = $noteType === 'endnote' ? 'endnote' : 'footnote';
    $xml = article_docx_part_contents($path, 'word/' . $partBaseName . '.xml');
    if ($xml === '') {
        return [];
    }

    $dom = new DOMDocument();
    $previousUseInternalErrors = libxml_use_internal_errors(true);
    $loaded = $dom->loadXML($xml);
    libxml_clear_errors();
    libxml_use_internal_errors($previousUseInternalErrors);
    if (!$loaded) {
        return [];
    }

    $relationshipsXml = article_docx_part_contents($path, 'word/_rels/' . $partBaseName . '.xml.rels');
    $relationships = article_docx_relationship_targets($relationshipsXml);
    $imageDataUris = article_docx_image_data_uris($path, $relationshipsXml);
    $xpath = new DOMXPath($dom);
    $noteNodes = $xpath->query('/*[local-name()="' . $partBaseName . '"]/*[local-name()="' . $nodeName . '"]');
    if (!$noteNodes instanceof DOMNodeList) {
        return [];
    }

    $notes = [];
    foreach ($noteNodes as $noteNode) {
        if (!$noteNode instanceof DOMElement) {
            continue;
        }
        $noteId = article_docx_attribute($noteNode, 'id');
        if (article_docx_note_reference_label($noteType, $noteId) === '') {
            continue;
        }

        $html = [];
        $openListTag = null;
        foreach ($noteNode->childNodes as $child) {
            if ($child instanceof DOMElement) {
                article_docx_append_block_html($child, $xpath, $relationships, $imageDataUris, $numberingFormats, $html, $openListTag);
            }
        }
        article_docx_close_open_list($html, $openListTag);

        $noteHtml = trim(implode('', $html));
        if (!article_docx_html_is_empty($noteHtml)) {
            $notes[$noteId] = $noteHtml;
        }
    }

    return $notes;
}
}

if (!function_exists('article_docx_referenced_notes_html')) {
/**
 * @param array<string,array<string,string>> $noteBodies
 * @param array<string,array{type:string,id:string}> $referencedNotes
 */
function article_docx_referenced_notes_html(array $noteBodies, array $referencedNotes): string
{
    $items = [];
    foreach ($referencedNotes as $noteReference) {
        $noteType = $noteReference['type'];
        $noteId = $noteReference['id'];
        $noteHtml = $noteBodies[$noteType][$noteId] ?? '';
        if ($noteHtml === '') {
            continue;
        }
        $referenceHtml = article_docx_note_reference_html($noteType, $noteId);
        if ($referenceHtml === '') {
            continue;
        }
        $items[] = '<li>' . $referenceHtml . ' ' . $noteHtml . '</li>';
    }

    return $items === [] ? '' : '<hr><h3>Notes</h3><ul>' . implode('', $items) . '</ul>';
}
}

if (!function_exists('article_docx_note_reference_html')) {
function article_docx_note_reference_html(string $noteType, string $noteId): string
{
    $label = article_docx_note_reference_label($noteType, $noteId);
    return $label === '' ? '' : '<sup>' . e($label) . '</sup>';
}
}

if (!function_exists('article_docx_note_reference_label')) {
function article_docx_note_reference_label(string $noteType, string $noteId): string
{
    if (!preg_match('/^-?\d+$/', $noteId)) {
        return '';
    }

    $number = (int) $noteId;
    if ($number < 1) {
        return '';
    }

    return $noteType === 'endnote' ? 'e' . $number : (string) $number;
}
}

if (!function_exists('article_docx_numbering_formats')) {
/**
 * @return array<string,array<string,string>>
 */
function article_docx_numbering_formats(string $xml): array
{
    if ($xml === '') {
        return [];
    }

    $dom = new DOMDocument();
    $previousUseInternalErrors = libxml_use_internal_errors(true);
    $loaded = $dom->loadXML($xml);
    libxml_clear_errors();
    libxml_use_internal_errors($previousUseInternalErrors);
    if (!$loaded) {
        return [];
    }

    $xpath = new DOMXPath($dom);
    $abstractFormats = [];
    $abstractNodes = $xpath->query('//*[local-name()="abstractNum"]');
    if ($abstractNodes instanceof DOMNodeList) {
        foreach ($abstractNodes as $abstractNode) {
            if (!$abstractNode instanceof DOMElement) {
                continue;
            }

            $abstractId = article_docx_attribute($abstractNode, 'abstractNumId');
            if ($abstractId === '') {
                continue;
            }

            $lvlNodes = $xpath->query('./*[local-name()="lvl"]', $abstractNode);
            if (!$lvlNodes instanceof DOMNodeList) {
                continue;
            }

            foreach ($lvlNodes as $lvlNode) {
                if (!$lvlNode instanceof DOMElement) {
                    continue;
                }

                $level = article_docx_attribute($lvlNode, 'ilvl');
                $formatNodes = $xpath->query('./*[local-name()="numFmt"]', $lvlNode);
                $formatNode = $formatNodes instanceof DOMNodeList ? $formatNodes->item(0) : null;
                if ($level !== '' && $formatNode instanceof DOMElement) {
                    $abstractFormats[$abstractId][$level] = strtolower(article_docx_attribute($formatNode, 'val'));
                }
            }
        }
    }

    $formats = [];
    $numNodes = $xpath->query('//*[local-name()="num"]');
    if ($numNodes instanceof DOMNodeList) {
        foreach ($numNodes as $numNode) {
            if (!$numNode instanceof DOMElement) {
                continue;
            }

            $numId = article_docx_attribute($numNode, 'numId');
            $abstractIdNodes = $xpath->query('./*[local-name()="abstractNumId"]', $numNode);
            $abstractIdNode = $abstractIdNodes instanceof DOMNodeList ? $abstractIdNodes->item(0) : null;
            $abstractId = $abstractIdNode instanceof DOMElement ? article_docx_attribute($abstractIdNode, 'val') : '';
            if ($numId !== '' && $abstractId !== '' && isset($abstractFormats[$abstractId])) {
                $formats[$numId] = $abstractFormats[$abstractId];
            }
        }
    }

    return $formats;
}
}

if (!function_exists('article_docx_paragraph_list_tag')) {
/**
 * @param array<string,array<string,string>> $numberingFormats
 */
function article_docx_paragraph_list_tag(DOMElement $paragraph, DOMXPath $xpath, array $numberingFormats): string
{
    $numPrNodes = $xpath->query('./*[local-name()="pPr"]/*[local-name()="numPr"]', $paragraph);
    $numPr = $numPrNodes instanceof DOMNodeList ? $numPrNodes->item(0) : null;
    if (!$numPr instanceof DOMElement) {
        return '';
    }

    $numIdNodes = $xpath->query('./*[local-name()="numId"]', $numPr);
    $numIdNode = $numIdNodes instanceof DOMNodeList ? $numIdNodes->item(0) : null;
    $numId = $numIdNode instanceof DOMElement ? article_docx_attribute($numIdNode, 'val') : '';
    if ($numId === '') {
        return 'ul';
    }

    $levelNodes = $xpath->query('./*[local-name()="ilvl"]', $numPr);
    $levelNode = $levelNodes instanceof DOMNodeList ? $levelNodes->item(0) : null;
    $level = $levelNode instanceof DOMElement ? article_docx_attribute($levelNode, 'val') : '0';
    $format = $numberingFormats[$numId][$level] ?? $numberingFormats[$numId]['0'] ?? '';

    return article_docx_numbering_format_tag($format);
}
}

if (!function_exists('article_docx_numbering_format_tag')) {
function article_docx_numbering_format_tag(string $format): string
{
    $format = strtolower(trim($format));
    if ($format === '' || in_array($format, ['bullet', 'none'], true)) {
        return 'ul';
    }

    return 'ol';
}
}

if (!function_exists('article_docx_part_contents')) {
function article_docx_part_contents(string $path, string $partName): string
{
    $partName = ltrim(str_replace('\\', '/', $partName), '/');
    if ($partName === '' || !is_file($path)) {
        return '';
    }

    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($path) === true) {
            $contents = $zip->getFromName($partName);
            $zip->close();
            if (is_string($contents)) {
                return $contents;
            }
        }
    }

    $contents = article_zip_entry_contents($path, $partName);
    if ($contents !== '') {
        return $contents;
    }

    $unzip = article_find_binary('unzip');
    if ($unzip === '') {
        return '';
    }

    $output = @shell_exec(escapeshellarg($unzip) . ' -p ' . escapeshellarg($path) . ' ' . escapeshellarg($partName));
    return is_string($output) ? $output : '';
}
}

if (!function_exists('article_zip_entry_contents')) {
function article_zip_entry_contents(string $path, string $entryName): string
{
    $entryName = ltrim(str_replace('\\', '/', $entryName), '/');
    $zip = is_file($path) ? file_get_contents($path) : false;
    if (!is_string($zip) || $zip === '' || $entryName === '') {
        return '';
    }

    $length = strlen($zip);
    $tailLength = min($length, 22 + 0xffff);
    $tail = substr($zip, $length - $tailLength);
    $eocdTailOffset = strrpos($tail, "PK\x05\x06");
    if ($eocdTailOffset === false) {
        return '';
    }

    $eocdOffset = $length - $tailLength + $eocdTailOffset;
    if ($eocdOffset + 22 > $length) {
        return '';
    }

    $centralDirectorySize = article_unpack_uint32(substr($zip, $eocdOffset + 12, 4));
    $centralDirectoryOffset = article_unpack_uint32(substr($zip, $eocdOffset + 16, 4));
    if ($centralDirectoryOffset < 0 || $centralDirectorySize < 0 || $centralDirectoryOffset + $centralDirectorySize > $length) {
        return '';
    }

    $position = $centralDirectoryOffset;
    $end = $centralDirectoryOffset + $centralDirectorySize;
    while ($position + 46 <= $end && substr($zip, $position, 4) === "PK\x01\x02") {
        $method = article_unpack_uint16(substr($zip, $position + 10, 2));
        $compressedSize = article_unpack_uint32(substr($zip, $position + 20, 4));
        $fileNameLength = article_unpack_uint16(substr($zip, $position + 28, 2));
        $extraLength = article_unpack_uint16(substr($zip, $position + 30, 2));
        $commentLength = article_unpack_uint16(substr($zip, $position + 32, 2));
        $localHeaderOffset = article_unpack_uint32(substr($zip, $position + 42, 4));
        $fileName = substr($zip, $position + 46, $fileNameLength);

        if ($fileName === $entryName) {
            if ($localHeaderOffset + 30 > $length || substr($zip, $localHeaderOffset, 4) !== "PK\x03\x04") {
                return '';
            }

            $localNameLength = article_unpack_uint16(substr($zip, $localHeaderOffset + 26, 2));
            $localExtraLength = article_unpack_uint16(substr($zip, $localHeaderOffset + 28, 2));
            $dataOffset = $localHeaderOffset + 30 + $localNameLength + $localExtraLength;
            if ($dataOffset < 0 || $dataOffset + $compressedSize > $length) {
                return '';
            }

            $compressed = substr($zip, $dataOffset, $compressedSize);
            if ($method === 0) {
                return $compressed;
            }
            if ($method === 8) {
                $inflated = @gzinflate($compressed);
                return is_string($inflated) ? $inflated : '';
            }

            return '';
        }

        $position += 46 + $fileNameLength + $extraLength + $commentLength;
    }

    return '';
}
}

if (!function_exists('article_unpack_uint16')) {
function article_unpack_uint16(string $bytes): int
{
    $value = unpack('v', $bytes);
    return is_array($value) ? (int) $value[1] : 0;
}
}

if (!function_exists('article_unpack_uint32')) {
function article_unpack_uint32(string $bytes): int
{
    $value = unpack('V', $bytes);
    return is_array($value) ? (int) $value[1] : 0;
}
}

if (!function_exists('article_docx_relationship_targets')) {
/**
 * @return array<string,string>
 */
function article_docx_relationship_targets(string $xml): array
{
    if ($xml === '') {
        return [];
    }

    $dom = new DOMDocument();
    $previousUseInternalErrors = libxml_use_internal_errors(true);
    $loaded = $dom->loadXML($xml);
    libxml_clear_errors();
    libxml_use_internal_errors($previousUseInternalErrors);
    if (!$loaded) {
        return [];
    }

    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query('//*[local-name()="Relationship"]');
    if (!$nodes instanceof DOMNodeList) {
        return [];
    }

    $targets = [];
    foreach ($nodes as $node) {
        if (!$node instanceof DOMElement) {
            continue;
        }
        $id = trim($node->getAttribute('Id'));
        $target = trim($node->getAttribute('Target'));
        if ($id !== '' && $target !== '') {
            $targets[$id] = $target;
        }
    }

    return $targets;
}
}

if (!function_exists('article_docx_max_inline_image_bytes')) {
function article_docx_max_inline_image_bytes(): int
{
    return 512 * 1024;
}
}

if (!function_exists('article_docx_max_inline_image_total_bytes')) {
function article_docx_max_inline_image_total_bytes(): int
{
    return 1024 * 1024;
}
}

if (!function_exists('article_docx_image_data_uris')) {
/**
 * @return array<string,string>
 */
function article_docx_image_data_uris(string $path, string $relationshipsXml): array
{
    if ($relationshipsXml === '' || !is_file($path)) {
        return [];
    }

    $dom = new DOMDocument();
    $previousUseInternalErrors = libxml_use_internal_errors(true);
    $loaded = $dom->loadXML($relationshipsXml);
    libxml_clear_errors();
    libxml_use_internal_errors($previousUseInternalErrors);
    if (!$loaded) {
        return [];
    }

    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query('//*[local-name()="Relationship"]');
    if (!$nodes instanceof DOMNodeList) {
        return [];
    }

    $images = [];
    $totalImageBytes = 0;
    foreach ($nodes as $node) {
        if (!$node instanceof DOMElement) {
            continue;
        }

        $id = trim($node->getAttribute('Id'));
        $type = trim($node->getAttribute('Type'));
        $target = trim($node->getAttribute('Target'));
        $targetMode = strtolower(trim($node->getAttribute('TargetMode')));
        if ($id === '' || $target === '' || $targetMode === 'external' || !str_ends_with($type, '/image')) {
            continue;
        }

        $partName = article_docx_relationship_part_name('word', $target);
        $mimeType = article_docx_image_mime_type($partName);
        if ($partName === '' || $mimeType === '') {
            continue;
        }

        $bytes = article_docx_part_contents($path, $partName);
        $byteLength = strlen($bytes);
        if (
            $bytes === ''
            || $byteLength > article_docx_max_inline_image_bytes()
            || $totalImageBytes + $byteLength > article_docx_max_inline_image_total_bytes()
        ) {
            continue;
        }

        $totalImageBytes += $byteLength;
        $images[$id] = 'data:' . $mimeType . ';base64,' . base64_encode($bytes);
    }

    return $images;
}
}

if (!function_exists('article_docx_relationship_part_name')) {
function article_docx_relationship_part_name(string $baseDirectory, string $target): string
{
    $target = trim(str_replace('\\', '/', $target));
    if ($target === '' || preg_match('/^[a-z][a-z0-9+.-]*:/i', $target) === 1) {
        return '';
    }

    $path = str_starts_with($target, '/') ? ltrim($target, '/') : trim($baseDirectory, '/') . '/' . $target;
    $parts = [];
    foreach (explode('/', $path) as $segment) {
        if ($segment === '' || $segment === '.') {
            continue;
        }
        if ($segment === '..') {
            array_pop($parts);
            continue;
        }
        $parts[] = $segment;
    }

    return implode('/', $parts);
}
}

if (!function_exists('article_docx_image_mime_type')) {
function article_docx_image_mime_type(string $partName): string
{
    return match (strtolower(pathinfo($partName, PATHINFO_EXTENSION))) {
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        default => '',
    };
}
}

if (!function_exists('article_docx_attribute')) {
function article_docx_attribute(DOMElement $element, string $localName): string
{
    foreach ($element->attributes as $attribute) {
        if ($attribute->localName === $localName || $attribute->name === $localName) {
            return (string) $attribute->value;
        }
    }

    return '';
}
}

if (!function_exists('article_docx_paragraph_style')) {
function article_docx_paragraph_style(DOMElement $paragraph, DOMXPath $xpath): string
{
    $styleNodes = $xpath->query('./*[local-name()="pPr"]/*[local-name()="pStyle"]', $paragraph);
    $styleNode = $styleNodes instanceof DOMNodeList ? $styleNodes->item(0) : null;
    if (!$styleNode instanceof DOMElement) {
        return '';
    }

    return strtolower(article_docx_attribute($styleNode, 'val'));
}
}

if (!function_exists('article_docx_inline_html')) {
/**
 * @param array<string,string> $relationships
 * @param array<string,string> $imageDataUris
 * @param array<string,array<string,string>> $noteBodies
 * @param array<string,array{type:string,id:string}>|null $referencedNotes
 */
function article_docx_inline_html(DOMNode $container, DOMXPath $xpath, array $relationships, array $imageDataUris = [], array $noteBodies = [], ?array &$referencedNotes = null): string
{
    $html = '';
    foreach ($container->childNodes as $child) {
        if ($child instanceof DOMText) {
            if (trim($child->textContent) === '') {
                continue;
            }
            $html .= e($child->textContent);
            continue;
        }
        if (!$child instanceof DOMElement) {
            continue;
        }

        $localName = $child->localName;
        if ($localName === 'pPr' || $localName === 'rPr') {
            continue;
        }
        if ($localName === 'r') {
            $html .= article_docx_run_html($child, $xpath, $relationships, $imageDataUris, $noteBodies, $referencedNotes);
            continue;
        }
        if ($localName === 'hyperlink') {
            $inner = article_docx_inline_html($child, $xpath, $relationships, $imageDataUris, $noteBodies, $referencedNotes);
            if (article_docx_html_is_empty($inner)) {
                continue;
            }

            $href = '';
            $relationshipId = article_docx_attribute($child, 'id');
            if ($relationshipId !== '' && isset($relationships[$relationshipId])) {
                $href = article_docx_safe_href($relationships[$relationshipId]);
            } else {
                $anchor = preg_replace('/[^a-z0-9_.:-]+/i', '-', article_docx_attribute($child, 'anchor')) ?? '';
                $href = $anchor !== '' ? article_docx_safe_href('#' . trim($anchor, '-')) : '';
            }

            $html .= $href !== '' ? '<a href="' . e($href) . '">' . $inner . '</a>' : $inner;
            continue;
        }
        if ($localName === 'AlternateContent') {
            $html .= article_docx_alternate_content_inline_html($child, $xpath, $relationships, $imageDataUris, $noteBodies, $referencedNotes);
            continue;
        }
        if ($localName === 'drawing' || $localName === 'pict') {
            $html .= article_docx_run_image_html($child, $xpath, $imageDataUris);
            $html .= article_docx_textbox_inline_html($child, $xpath, $relationships, $imageDataUris, $noteBodies, $referencedNotes);
            continue;
        }
        if ($localName === 'tab') {
            $html .= ' ';
            continue;
        }
        if ($localName === 'br') {
            $html .= '<br>';
            continue;
        }

        $html .= article_docx_inline_html($child, $xpath, $relationships, $imageDataUris, $noteBodies, $referencedNotes);
    }

    return trim((string) preg_replace('/[ \t\r\n]+/u', ' ', $html));
}
}

if (!function_exists('article_docx_alternate_content_inline_html')) {
/**
 * @param array<string,string> $relationships
 * @param array<string,string> $imageDataUris
 * @param array<string,array<string,string>> $noteBodies
 * @param array<string,array{type:string,id:string}>|null $referencedNotes
 */
function article_docx_alternate_content_inline_html(DOMElement $alternateContent, DOMXPath $xpath, array $relationships, array $imageDataUris = [], array $noteBodies = [], ?array &$referencedNotes = null): string
{
    foreach (article_docx_alternate_content_candidates($alternateContent) as $candidateNode) {
        $candidateReferencedNotes = $referencedNotes;
        $html = article_docx_inline_html($candidateNode, $xpath, $relationships, $imageDataUris, $noteBodies, $candidateReferencedNotes);
        if (!article_docx_html_is_empty($html)) {
            $referencedNotes = $candidateReferencedNotes;
            return $html;
        }
    }

    return '';
}
}

if (!function_exists('article_docx_run_html')) {
/**
 * @param array<string,string> $relationships
 * @param array<string,string> $imageDataUris
 * @param array<string,array<string,string>> $noteBodies
 * @param array<string,array{type:string,id:string}>|null $referencedNotes
 */
function article_docx_run_html(DOMElement $run, DOMXPath $xpath, array $relationships = [], array $imageDataUris = [], array $noteBodies = [], ?array &$referencedNotes = null): string
{
    $html = '';
    foreach ($run->childNodes as $child) {
        if (!$child instanceof DOMElement) {
            continue;
        }

        $localName = $child->localName;
        if ($localName === 'rPr') {
            continue;
        }
        if ($localName === 't' || $localName === 'delText') {
            $html .= e($child->textContent);
            continue;
        }
        if ($localName === 'tab') {
            $html .= ' ';
            continue;
        }
        if ($localName === 'br' || $localName === 'cr') {
            $html .= '<br>';
            continue;
        }
        if ($localName === 'noBreakHyphen') {
            $html .= '-';
            continue;
        }
        if ($localName === 'softHyphen') {
            $html .= '&shy;';
            continue;
        }
        if ($localName === 'footnoteReference' || $localName === 'endnoteReference') {
            $noteType = $localName === 'footnoteReference' ? 'footnote' : 'endnote';
            $noteId = article_docx_attribute($child, 'id');
            $referenceHtml = article_docx_note_reference_html($noteType, $noteId);
            if ($referenceHtml !== '') {
                $html .= $referenceHtml;
                if ($referencedNotes !== null && isset($noteBodies[$noteType][$noteId])) {
                    $noteKey = $noteType . ':' . $noteId;
                    $referencedNotes[$noteKey] = ['type' => $noteType, 'id' => $noteId];
                }
            }
            continue;
        }
        if ($localName === 'AlternateContent') {
            $html .= article_docx_alternate_content_inline_html($child, $xpath, $relationships, $imageDataUris, $noteBodies, $referencedNotes);
            continue;
        }
        if ($localName === 'drawing' || $localName === 'pict') {
            $html .= article_docx_run_image_html($child, $xpath, $imageDataUris);
            $html .= article_docx_textbox_inline_html($child, $xpath, $relationships, $imageDataUris, $noteBodies, $referencedNotes);
            continue;
        }
    }

    if ($html === '') {
        return '';
    }

    $verticalAlignment = article_docx_run_vertical_alignment($run, $xpath);
    if ($verticalAlignment === 'superscript') {
        $html = '<sup>' . $html . '</sup>';
    } elseif ($verticalAlignment === 'subscript') {
        $html = '<sub>' . $html . '</sub>';
    }
    if (article_docx_run_property_enabled($run, $xpath, 'strike') || article_docx_run_property_enabled($run, $xpath, 'dstrike')) {
        $html = '<s>' . $html . '</s>';
    }
    if (article_docx_run_property_enabled($run, $xpath, 'u')) {
        $html = '<u>' . $html . '</u>';
    }
    if (article_docx_run_property_enabled($run, $xpath, 'i')) {
        $html = '<em>' . $html . '</em>';
    }
    if (article_docx_run_property_enabled($run, $xpath, 'b')) {
        $html = '<strong>' . $html . '</strong>';
    }

    return $html;
}
}

if (!function_exists('article_docx_textbox_inline_html')) {
/**
 * @param array<string,string> $relationships
 * @param array<string,string> $imageDataUris
 * @param array<string,array<string,string>> $noteBodies
 * @param array<string,array{type:string,id:string}>|null $referencedNotes
 */
function article_docx_textbox_inline_html(DOMElement $container, DOMXPath $xpath, array $relationships, array $imageDataUris = [], array $noteBodies = [], ?array &$referencedNotes = null): string
{
    $contentNodes = $xpath->query('.//*[local-name()="txbxContent"]', $container);
    if (!$contentNodes instanceof DOMNodeList || $contentNodes->length === 0) {
        return '';
    }

    $parts = [];
    foreach ($contentNodes as $contentNode) {
        if (!$contentNode instanceof DOMElement) {
            continue;
        }
        $paragraphNodes = $xpath->query('.//*[local-name()="p"]', $contentNode);
        if (!$paragraphNodes instanceof DOMNodeList) {
            continue;
        }
        foreach ($paragraphNodes as $paragraphNode) {
            if (!$paragraphNode instanceof DOMElement) {
                continue;
            }
            $inline = article_docx_inline_html($paragraphNode, $xpath, $relationships, $imageDataUris, $noteBodies, $referencedNotes);
            if (!article_docx_html_is_empty($inline)) {
                $parts[] = $inline;
            }
        }
    }

    return implode('<br>', $parts);
}
}

if (!function_exists('article_docx_run_vertical_alignment')) {
function article_docx_run_vertical_alignment(DOMElement $run, DOMXPath $xpath): string
{
    $nodes = $xpath->query('./*[local-name()="rPr"]/*[local-name()="vertAlign"]', $run);
    $node = $nodes instanceof DOMNodeList ? $nodes->item(0) : null;
    if (!$node instanceof DOMElement) {
        return '';
    }

    $value = strtolower(article_docx_attribute($node, 'val'));
    return in_array($value, ['superscript', 'subscript'], true) ? $value : '';
}
}

if (!function_exists('article_docx_run_image_html')) {
/**
 * @param array<string,string> $imageDataUris
 */
function article_docx_run_image_html(DOMElement $container, DOMXPath $xpath, array $imageDataUris): string
{
    if ($imageDataUris === []) {
        return '';
    }

    $imageNodes = $xpath->query('.//*[local-name()="blip" or local-name()="imagedata"]', $container);
    if (!$imageNodes instanceof DOMNodeList || $imageNodes->length === 0) {
        return '';
    }

    $html = [];
    foreach ($imageNodes as $imageNode) {
        if (!$imageNode instanceof DOMElement) {
            continue;
        }

        $relationshipId = article_docx_attribute($imageNode, 'embed');
        if ($relationshipId === '') {
            $relationshipId = article_docx_attribute($imageNode, 'id');
        }
        if ($relationshipId === '' || !isset($imageDataUris[$relationshipId])) {
            continue;
        }

        $attributes = [
            'src' => $imageDataUris[$relationshipId],
            'alt' => article_docx_image_alt_text($container, $xpath),
            'loading' => 'lazy',
        ];
        $dimensions = article_docx_image_dimensions($container, $xpath);
        $attributes = array_merge($attributes, $dimensions);

        $imageHtml = '<img';
        foreach ($attributes as $name => $value) {
            $imageHtml .= ' ' . $name . '="' . e((string) $value) . '"';
        }
        $imageHtml .= '>';
        $html[] = $imageHtml;
    }

    return implode('', $html);
}
}

if (!function_exists('article_docx_image_alt_text')) {
function article_docx_image_alt_text(DOMElement $container, DOMXPath $xpath): string
{
    $propertyNodes = $xpath->query('.//*[local-name()="docPr" or local-name()="cNvPr"]', $container);
    if (!$propertyNodes instanceof DOMNodeList) {
        return '';
    }

    foreach ($propertyNodes as $propertyNode) {
        if (!$propertyNode instanceof DOMElement) {
            continue;
        }

        foreach (['descr', 'title', 'name'] as $attributeName) {
            $value = trim(article_docx_attribute($propertyNode, $attributeName));
            if ($value !== '') {
                return $value;
            }
        }
    }

    return '';
}
}

if (!function_exists('article_docx_image_dimensions')) {
/**
 * @return array{width?:string,height?:string}
 */
function article_docx_image_dimensions(DOMElement $container, DOMXPath $xpath): array
{
    $extentNodes = $xpath->query('.//*[local-name()="extent"]', $container);
    $extentNode = $extentNodes instanceof DOMNodeList ? $extentNodes->item(0) : null;
    if (!$extentNode instanceof DOMElement) {
        return [];
    }

    $width = article_docx_emu_to_pixels(article_docx_attribute($extentNode, 'cx'));
    $height = article_docx_emu_to_pixels(article_docx_attribute($extentNode, 'cy'));
    $dimensions = [];
    if ($width > 0 && $width <= 2000) {
        $dimensions['width'] = (string) $width;
    }
    if ($height > 0 && $height <= 2000) {
        $dimensions['height'] = (string) $height;
    }

    return $dimensions;
}
}

if (!function_exists('article_docx_emu_to_pixels')) {
function article_docx_emu_to_pixels(string $value): int
{
    if (!preg_match('/^\d+$/', $value)) {
        return 0;
    }

    return (int) round(((int) $value) / 9525);
}
}

if (!function_exists('article_docx_run_property_enabled')) {
function article_docx_run_property_enabled(DOMElement $run, DOMXPath $xpath, string $property): bool
{
    $nodes = $xpath->query('./*[local-name()="rPr"]/*[local-name()="' . $property . '"]', $run);
    $node = $nodes instanceof DOMNodeList ? $nodes->item(0) : null;
    if (!$node instanceof DOMElement) {
        return false;
    }

    $value = strtolower(article_docx_attribute($node, 'val'));
    return !in_array($value, ['0', 'false', 'none', 'off'], true);
}
}

if (!function_exists('article_docx_table_html')) {
/**
 * @param array<string,string> $relationships
 * @param array<string,string> $imageDataUris
 * @param array<string,array<string,string>> $noteBodies
 * @param array<string,array{type:string,id:string}>|null $referencedNotes
 */
function article_docx_table_html(DOMElement $table, DOMXPath $xpath, array $relationships, array $imageDataUris = [], array $noteBodies = [], ?array &$referencedNotes = null): string
{
    $headRows = [];
    $bodyRows = [];
    $verticalMergeColumns = [];
    $rowNodes = $xpath->query('./*[local-name()="tr"]', $table);
    if (!$rowNodes instanceof DOMNodeList) {
        return '';
    }

    foreach ($rowNodes as $rowNode) {
        if (!$rowNode instanceof DOMElement) {
            continue;
        }

        $isHeaderRow = article_docx_table_row_is_header($rowNode, $xpath);
        $cellTag = $isHeaderRow ? 'th' : 'td';
        $cells = [];
        $columnIndex = 0;
        $cellNodes = $xpath->query('./*[local-name()="tc"]', $rowNode);
        if (!$cellNodes instanceof DOMNodeList) {
            continue;
        }

        foreach ($cellNodes as $cellNode) {
            if (!$cellNode instanceof DOMElement) {
                continue;
            }

            $gridSpan = article_docx_table_cell_grid_span($cellNode, $xpath);
            $mergeState = article_docx_table_cell_vertical_merge_state($cellNode, $xpath);
            if ($mergeState === 'continue') {
                if (isset($verticalMergeColumns[$columnIndex])) {
                    $verticalMergeColumns[$columnIndex]['cell']['rowspan']++;
                }
                $columnIndex += $gridSpan;
                continue;
            }

            $paragraphs = article_docx_table_cell_content_parts($cellNode, $xpath, $relationships, $imageDataUris, $noteBodies, $referencedNotes);

            $cell = [
                'tag' => $cellTag,
                'content' => implode('<br>', $paragraphs),
                'colspan' => $gridSpan,
                'rowspan' => 1,
                'header' => $isHeaderRow,
            ];
            $cells[] = &$cell;

            if ($mergeState === 'restart') {
                for ($i = 0; $i < $gridSpan; $i++) {
                    $verticalMergeColumns[$columnIndex + $i] = ['cell' => &$cell];
                }
            } else {
                for ($i = 0; $i < $gridSpan; $i++) {
                    unset($verticalMergeColumns[$columnIndex + $i]);
                }
            }
            unset($cell);
            $columnIndex += $gridSpan;
        }

        if ($cells !== []) {
            if ($isHeaderRow) {
                $headRows[] = $cells;
            } else {
                $bodyRows[] = $cells;
            }
        }
    }

    if ($headRows === [] && $bodyRows === []) {
        return '';
    }

    $html = '<table>';
    if ($headRows !== []) {
        $html .= '<thead>' . article_docx_table_rows_html($headRows) . '</thead>';
    }
    if ($bodyRows !== []) {
        $html .= '<tbody>' . article_docx_table_rows_html($bodyRows) . '</tbody>';
    }

    return $html . '</table>';
}
}

if (!function_exists('article_docx_table_cell_grid_span')) {
function article_docx_table_cell_grid_span(DOMElement $cell, DOMXPath $xpath): int
{
    $gridSpanNodes = $xpath->query('./*[local-name()="tcPr"]/*[local-name()="gridSpan"]', $cell);
    $gridSpanNode = $gridSpanNodes instanceof DOMNodeList ? $gridSpanNodes->item(0) : null;
    if (!$gridSpanNode instanceof DOMElement) {
        return 1;
    }

    $gridSpan = (int) article_docx_attribute($gridSpanNode, 'val');
    return $gridSpan > 1 && $gridSpan <= 20 ? $gridSpan : 1;
}
}

if (!function_exists('article_docx_table_cell_vertical_merge_state')) {
function article_docx_table_cell_vertical_merge_state(DOMElement $cell, DOMXPath $xpath): string
{
    $mergeNodes = $xpath->query('./*[local-name()="tcPr"]/*[local-name()="vMerge"]', $cell);
    $mergeNode = $mergeNodes instanceof DOMNodeList ? $mergeNodes->item(0) : null;
    if (!$mergeNode instanceof DOMElement) {
        return '';
    }

    $value = strtolower(article_docx_attribute($mergeNode, 'val'));
    return $value === 'restart' ? 'restart' : 'continue';
}
}

if (!function_exists('article_docx_table_cell_content_parts')) {
/**
 * @param array<string,string> $relationships
 * @param array<string,string> $imageDataUris
 * @param array<string,array<string,string>> $noteBodies
 * @param array<string,array{type:string,id:string}>|null $referencedNotes
 * @return list<string>
 */
function article_docx_table_cell_content_parts(DOMElement $cellNode, DOMXPath $xpath, array $relationships, array $imageDataUris = [], array $noteBodies = [], ?array &$referencedNotes = null): array
{
    $parts = [];
    foreach ($cellNode->childNodes as $child) {
        if (!$child instanceof DOMElement || $child->localName === 'tcPr') {
            continue;
        }
        if ($child->localName === 'p') {
            $inline = article_docx_inline_html($child, $xpath, $relationships, $imageDataUris, $noteBodies, $referencedNotes);
            if (!article_docx_html_is_empty($inline)) {
                $parts[] = $inline;
            }
            continue;
        }
        if ($child->localName === 'tbl') {
            $table = article_docx_table_html($child, $xpath, $relationships, $imageDataUris, $noteBodies, $referencedNotes);
            if ($table !== '') {
                $parts[] = $table;
            }
            continue;
        }

        $nestedNodes = $xpath->query('.//*[local-name()="p"]', $child);
        if (!$nestedNodes instanceof DOMNodeList) {
            continue;
        }
        foreach ($nestedNodes as $nestedNode) {
            if (!$nestedNode instanceof DOMElement) {
                continue;
            }
            $inline = article_docx_inline_html($nestedNode, $xpath, $relationships, $imageDataUris, $noteBodies, $referencedNotes);
            if (!article_docx_html_is_empty($inline)) {
                $parts[] = $inline;
            }
        }
    }

    return $parts;
}
}

if (!function_exists('article_docx_table_rows_html')) {
/**
 * @param list<list<array{tag:string,content:string,colspan:int,rowspan:int,header:bool}>> $rows
 */
function article_docx_table_rows_html(array $rows): string
{
    $html = '';
    foreach ($rows as $cells) {
        $html .= article_docx_table_row_html($cells);
    }

    return $html;
}
}

if (!function_exists('article_docx_table_row_html')) {
/**
 * @param list<array{tag:string,content:string,colspan:int,rowspan:int,header:bool}> $cells
 */
function article_docx_table_row_html(array $cells): string
{
    $html = '';
    foreach ($cells as $cell) {
        $attributes = '';
        if ($cell['colspan'] > 1) {
            $attributes .= ' colspan="' . $cell['colspan'] . '"';
        }
        if ($cell['rowspan'] > 1) {
            $attributes .= ' rowspan="' . $cell['rowspan'] . '"';
        }
        if ($cell['header']) {
            $attributes .= ' scope="col"';
        }
        $html .= '<' . $cell['tag'] . $attributes . '>' . $cell['content'] . '</' . $cell['tag'] . '>';
    }

    return '<tr>' . $html . '</tr>';
}
}

if (!function_exists('article_docx_table_row_is_header')) {
function article_docx_table_row_is_header(DOMElement $row, DOMXPath $xpath): bool
{
    $nodes = $xpath->query('./*[local-name()="trPr"]/*[local-name()="tblHeader"]', $row);
    $node = $nodes instanceof DOMNodeList ? $nodes->item(0) : null;
    if (!$node instanceof DOMElement) {
        return false;
    }

    $value = strtolower(article_docx_attribute($node, 'val'));
    return !in_array($value, ['0', 'false', 'none', 'off'], true);
}
}

if (!function_exists('article_docx_safe_href')) {
function article_docx_safe_href(string $href): string
{
    try {
        $safe = sanitize_href_attribute($href);
    } catch (Throwable) {
        return '';
    }

    return $safe ?? '';
}
}

if (!function_exists('article_docx_html_is_empty')) {
function article_docx_html_is_empty(string $html): bool
{
    $withoutBreaks = str_replace('<br>', '', $html);
    $text = trim(html_entity_decode(strip_tags($withoutBreaks), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    if ($text !== '') {
        return false;
    }

    return preg_match('/<img\b[^>]*\bsrc=/i', $html) !== 1;
}
}

if (!function_exists('article_find_binary')) {
function article_find_binary(string $binary): string
{
    $binary = trim($binary);
    if ($binary === '' || preg_match('/[^a-z0-9_.-]/i', $binary)) {
        return '';
    }

    $command = PHP_OS_FAMILY === 'Windows'
        ? 'where ' . escapeshellarg($binary) . ' 2>NUL'
        : 'command -v ' . escapeshellarg($binary) . ' 2>/dev/null';
    $output = @shell_exec($command);
    if (!is_string($output) || trim($output) === '') {
        return '';
    }

    $lines = preg_split('/\R/u', trim($output)) ?: [];
    foreach ($lines as $line) {
        $candidate = trim($line);
        if ($candidate !== '' && (is_file($candidate) || PHP_OS_FAMILY !== 'Windows')) {
            return $candidate;
        }
    }

    return '';
}
}

if (!function_exists('article_extract_pdf_text')) {
function article_extract_pdf_text(string $path): string
{
    if (!is_file($path)) {
        return '';
    }

    $binary = article_find_binary('pdftotext');
    if ($binary === '') {
        return '';
    }

    $command = escapeshellarg($binary) . ' -layout -enc UTF-8 ' . escapeshellarg($path) . ' -';
    $output = @shell_exec($command);
    if (!is_string($output) || trim($output) === '') {
        return '';
    }

    return trim((string) preg_replace('/[ \t]+/u', ' ', $output));
}
}
