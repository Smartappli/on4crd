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

/**
 * Validates, optionally stores and converts an uploaded article source file.
 *
 * @param array<string,mixed> $file
 * @param callable(string):string $message
 * @return array{excerpt:string,content:string}
 */
if (!function_exists('article_import_uploaded_document')) {
function article_import_uploaded_document(array $file, bool $persist, bool $includeSourceLabel, string $targetDirectory, callable $message): array
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return ['excerpt' => '', 'content' => ''];
    }
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException($message('upload_failed'));
    }

    $originalName = trim((string) ($file['name'] ?? 'document'));
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['pdf', 'docx', 'txt', 'md', 'html', 'htm'];
    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException($message('allowed_formats'));
    }
    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > 25 * 1024 * 1024) {
        throw new RuntimeException($message('upload_failed'));
    }

    $temporaryPath = (string) ($file['tmp_name'] ?? '');
    if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
        throw new RuntimeException($message('invalid_doc'));
    }

    $allowedMimesByExtension = [
        'pdf' => ['application/pdf', 'application/x-pdf'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
        'txt' => ['text/plain', 'application/octet-stream'],
        'md' => ['text/plain', 'text/markdown', 'application/octet-stream'],
        'html' => ['text/html', 'text/plain', 'application/octet-stream'],
        'htm' => ['text/html', 'text/plain', 'application/octet-stream'],
    ];
    if (!in_array(detect_uploaded_mime_type($temporaryPath), $allowedMimesByExtension[$extension] ?? [], true)) {
        throw new RuntimeException($message('invalid_doc'));
    }
    if ($extension === 'pdf' || $extension === 'docx') {
        assert_upload_file_is_valid_signature($temporaryPath, [$extension]);
    }
    if ($extension === 'docx' && !article_docx_document_is_valid($temporaryPath)) {
        throw new RuntimeException($message('invalid_doc'));
    }

    $path = $temporaryPath;
    if ($persist) {
        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0755, true) && !is_dir($targetDirectory)) {
            throw new RuntimeException($message('create_dir'));
        }
        $basename = slugify(pathinfo($originalName, PATHINFO_FILENAME));
        $filename = ($basename !== '' ? $basename : 'article') . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $path = rtrim($targetDirectory, '/\\') . '/' . $filename;
        if (!move_uploaded_file($temporaryPath, $path)) {
            throw new RuntimeException($message('save_doc'));
        }
    }

    if (in_array($extension, ['txt', 'md'], true)) {
        $content = article_import_text_to_html((string) file_get_contents($path));
    } elseif (in_array($extension, ['html', 'htm'], true)) {
        $content = article_sanitize_content((string) file_get_contents($path));
    } elseif ($extension === 'docx') {
        $content = article_extract_docx_html($path);
        if ($content === '') {
            $content = '<div class="article-document"><p>' . e($message('docx_extraction_unavailable')) . '</p></div>';
        }
    } else {
        $content = article_import_text_to_html(article_extract_pdf_text($path));
        if ($content === '') {
            $content = '<div class="article-document"><p>' . e($message('pdf_extraction_unavailable')) . '</p></div>';
        }
    }

    $content = trim($content);
    if ($includeSourceLabel && $content !== '') {
        $content .= "\n" . '<p class="help article-source-document">' . e($message('source_file')) . ': ' . e($originalName) . '</p>';
    }

    return ['excerpt' => '', 'content' => article_sanitize_content($content)];
}
}

if (!function_exists('article_extract_docx_html')) {
function article_extract_docx_html(string $path): string
{
    if (!is_file($path)) {
        return '';
    }

    return (new ArticleDocxImporter())->extractHtml($path);
}
}

if (!function_exists('article_docx_document_is_valid')) {
function article_docx_document_is_valid(string $path): bool
{
    return (new ArticleDocxImporter())->isValidDocument($path);
}
}

if (!function_exists('article_docx_close_open_list')) {
/**
 * @param list<string> $html
 */
function article_docx_close_open_list(array &$html, array &$openLists): void
{
    while ($openLists !== []) {
        $list = array_pop($openLists);
        $html[] = '</li></' . $list['tag'] . '>';
    }
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
function article_docx_append_block_html(DOMElement $block, DOMXPath $xpath, array $relationships, array $imageDataUris, array $numberingFormats, array &$html, array &$openLists, array $noteBodies = [], ?array &$referencedNotes = null, array $altChunkHtmlByRelationshipId = []): void
{
    $localName = $block->localName;
    if ($localName === 'altChunk') {
        article_docx_close_open_list($html, $openLists);
        $relationshipId = article_docx_attribute($block, 'id');
        if ($relationshipId !== '' && isset($altChunkHtmlByRelationshipId[$relationshipId])) {
            $html[] = $altChunkHtmlByRelationshipId[$relationshipId];
        }
        return;
    }

    if ($localName === 'tbl') {
        article_docx_close_open_list($html, $openLists);
        $table = article_docx_table_html($block, $xpath, $relationships, $imageDataUris, $noteBodies, $referencedNotes);
        if ($table !== '') {
            $html[] = $table;
        }
        return;
    }

    if ($localName === 'p') {
        $inlineHtml = article_docx_inline_html($block, $xpath, $relationships, $imageDataUris, $noteBodies, $referencedNotes);
        if (article_docx_html_is_empty($inlineHtml)) {
            article_docx_close_open_list($html, $openLists);
            // Empty paragraphs are intentional in Word: they carry visual
            // spacing and, in some templates, an explicit page break. Keeping
            // them avoids silently collapsing sections during import.
            $html[] = article_docx_paragraph_has_page_break($block, $xpath) ? '<hr>' : '<p><br></p>';
            return;
        }

        $style = article_docx_paragraph_style($block, $xpath);
        $listInfo = article_docx_paragraph_list_info($block, $xpath, $numberingFormats);
        if ($listInfo !== null) {
            article_docx_append_list_item($html, $openLists, $listInfo, $inlineHtml);
            return;
        }

        article_docx_close_open_list($html, $openLists);
        $attributes = article_docx_paragraph_attributes($block, $xpath);
        $attributeHtml = article_docx_html_attributes($attributes);
        if (article_docx_paragraph_has_page_break($block, $xpath)) {
            $html[] = '<hr>';
        }
        if (str_contains($style, 'heading') || str_contains($style, 'titre')) {
            $level = preg_match('/([1-6])/', $style, $matches) ? (int) $matches[1] + 1 : 2;
            $level = min(4, max(2, $level));
            $html[] = '<h' . $level . $attributeHtml . '>' . $inlineHtml . '</h' . $level . '>';
        } else {
            $html[] = '<p' . $attributeHtml . '>' . $inlineHtml . '</p>';
        }
        return;
    }

    if ($localName === 'AlternateContent') {
        foreach (article_docx_alternate_content_candidates($block) as $candidateNode) {
            $candidateHtml = $html;
            $candidateOpenLists = $openLists;
            $candidateReferencedNotes = $referencedNotes;
            foreach ($candidateNode->childNodes as $child) {
                if ($child instanceof DOMElement) {
                    article_docx_append_block_html($child, $xpath, $relationships, $imageDataUris, $numberingFormats, $candidateHtml, $candidateOpenLists, $noteBodies, $candidateReferencedNotes, $altChunkHtmlByRelationshipId);
                }
            }
            $addedHtml = implode('', array_slice($candidateHtml, count($html)));
            if (!article_docx_html_is_empty($addedHtml)) {
                $html = $candidateHtml;
                $openLists = $candidateOpenLists;
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
        article_docx_append_block_html($child, $xpath, $relationships, $imageDataUris, $numberingFormats, $html, $openLists, $noteBodies, $referencedNotes, $altChunkHtmlByRelationshipId);
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
    $baseDirectory = article_docx_part_base_directory($partName);
    $altChunkHtmlByRelationshipId = article_docx_alt_chunk_html_by_relationship_id($path, $relationshipsXml, $baseDirectory);
    $xpath = new DOMXPath($dom);
    $imageDataUris = article_docx_image_data_uris($path, $relationshipsXml, $baseDirectory, article_docx_image_dimensions_by_relationship_id($xpath));
    $blockNodes = $xpath->query('/*/*');
    if (!$blockNodes instanceof DOMNodeList) {
        return '';
    }

    $html = [];
    $openLists = [];
    foreach ($blockNodes as $block) {
        if ($block instanceof DOMElement) {
            article_docx_append_block_html($block, $xpath, $relationships, $imageDataUris, $numberingFormats, $html, $openLists, $noteBodies, $referencedNotes, $altChunkHtmlByRelationshipId);
        }
    }
    article_docx_close_open_list($html, $openLists);

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
    $xpath = new DOMXPath($dom);
    $imageDataUris = article_docx_image_data_uris($path, $relationshipsXml, 'word', article_docx_image_dimensions_by_relationship_id($xpath));
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
        $openLists = [];
        foreach ($noteNode->childNodes as $child) {
            if ($child instanceof DOMElement) {
                article_docx_append_block_html($child, $xpath, $relationships, $imageDataUris, $numberingFormats, $html, $openLists);
            }
        }
        article_docx_close_open_list($html, $openLists);

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
    $info = article_docx_paragraph_list_info($paragraph, $xpath, $numberingFormats);
    return $info['tag'] ?? '';
}
}

if (!function_exists('article_docx_paragraph_list_info')) {
/**
 * @param array<string,array<string,string>> $numberingFormats
 * @return array{tag:string,id:string,level:int}|null
 */
function article_docx_paragraph_list_info(DOMElement $paragraph, DOMXPath $xpath, array $numberingFormats): ?array
{
    $numPrNodes = $xpath->query('./*[local-name()="pPr"]/*[local-name()="numPr"]', $paragraph);
    $numPr = $numPrNodes instanceof DOMNodeList ? $numPrNodes->item(0) : null;
    if (!$numPr instanceof DOMElement) {
        return null;
    }

    $numIdNodes = $xpath->query('./*[local-name()="numId"]', $numPr);
    $numIdNode = $numIdNodes instanceof DOMNodeList ? $numIdNodes->item(0) : null;
    $numId = $numIdNode instanceof DOMElement ? article_docx_attribute($numIdNode, 'val') : '';
    $numId = $numId === '' ? 'implicit' : $numId;

    $levelNodes = $xpath->query('./*[local-name()="ilvl"]', $numPr);
    $levelNode = $levelNodes instanceof DOMNodeList ? $levelNodes->item(0) : null;
    $level = $levelNode instanceof DOMElement ? article_docx_attribute($levelNode, 'val') : '0';
    $format = $numberingFormats[$numId][$level] ?? $numberingFormats[$numId]['0'] ?? '';

    return ['tag' => article_docx_numbering_format_tag($format), 'id' => $numId, 'level' => max(0, min(8, (int) $level))];
}
}

if (!function_exists('article_docx_append_list_item')) {
/**
 * @param list<string> $html
 * @param list<array{tag:string,id:string,level:int}> $openLists
 * @param array{tag:string,id:string,level:int} $listInfo
 */
function article_docx_append_list_item(array &$html, array &$openLists, array $listInfo, string $inlineHtml): void
{
    $targetDepth = $listInfo['level'] + 1;
    if ($openLists === []) {
        $targetDepth = 1;
    }

    while (count($openLists) > $targetDepth) {
        $list = array_pop($openLists);
        $html[] = '</li></' . $list['tag'] . '>';
    }

    $current = $openLists === [] ? null : $openLists[array_key_last($openLists)];
    if ($current !== null && count($openLists) === $targetDepth && ($current['tag'] !== $listInfo['tag'] || $current['id'] !== $listInfo['id'])) {
        $html[] = '</li></' . $current['tag'] . '>';
        array_pop($openLists);
    } elseif ($current !== null && count($openLists) === $targetDepth) {
        $html[] = '</li>';
    }

    while (count($openLists) < $targetDepth) {
        $html[] = '<' . $listInfo['tag'] . '>';
        $openLists[] = $listInfo;
    }

    $html[] = '<li>' . $inlineHtml;
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
 * @param array<string,array{width:int,height:int}> $displayDimensionsByRelationshipId
 * @return array<string,string>
 */
function article_docx_image_data_uris(string $path, string $relationshipsXml, string $baseDirectory = 'word', array $displayDimensionsByRelationshipId = []): array
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

        $partName = article_docx_relationship_part_name($baseDirectory, $target);
        $mimeType = article_docx_image_mime_type($partName);
        if ($partName === '' || $mimeType === '') {
            continue;
        }

        $bytes = article_docx_part_contents($path, $partName);
        $displayDimensions = $displayDimensionsByRelationshipId[$id] ?? [];
        if ($bytes !== '' && $displayDimensions !== []) {
            $resizedBytes = article_docx_resize_image_bytes(
                $bytes,
                $mimeType,
                (int) ($displayDimensions['width'] ?? 0),
                (int) ($displayDimensions['height'] ?? 0)
            );
            if ($resizedBytes !== '') {
                $bytes = $resizedBytes;
            }
        }

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

if (!function_exists('article_docx_resize_image_bytes')) {
function article_docx_resize_image_bytes(string $bytes, string $mimeType, int $targetWidth, int $targetHeight): string
{
    if (
        $targetWidth <= 0
        || $targetHeight <= 0
        || !function_exists('imagecreatefromstring')
        || !function_exists('imagecreatetruecolor')
        || !function_exists('imagecopyresampled')
    ) {
        return '';
    }

    $size = @getimagesizefromstring($bytes);
    if (!is_array($size) || (int) ($size[0] ?? 0) <= 0 || (int) ($size[1] ?? 0) <= 0) {
        return '';
    }

    $sourceWidth = (int) $size[0];
    $sourceHeight = (int) $size[1];
    if ($targetWidth >= $sourceWidth && $targetHeight >= $sourceHeight) {
        return '';
    }

    $targetWidth = min($targetWidth, $sourceWidth);
    $targetHeight = min($targetHeight, $sourceHeight);
    if ($targetWidth < 1 || $targetHeight < 1) {
        return '';
    }

    $source = @imagecreatefromstring($bytes);
    if (!$source instanceof GdImage) {
        return '';
    }

    $target = imagecreatetruecolor($targetWidth, $targetHeight);
    if (!$target instanceof GdImage) {
        imagedestroy($source);
        return '';
    }

    if (in_array($mimeType, ['image/png', 'image/webp'], true)) {
        imagealphablending($target, false);
        imagesavealpha($target, true);
        $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
        if ($transparent !== false) {
            imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);
        }
    }

    imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

    ob_start();
    $written = match ($mimeType) {
        'image/jpeg' => function_exists('imagejpeg') ? imagejpeg($target, null, 85) : false,
        'image/png' => function_exists('imagepng') ? imagepng($target, null, 6) : false,
        'image/webp' => function_exists('imagewebp') ? imagewebp($target, null, 82) : false,
        default => false,
    };
    $resized = (string) ob_get_clean();

    imagedestroy($target);
    imagedestroy($source);

    if (!$written || $resized === '') {
        return '';
    }

    return strlen($resized) < strlen($bytes) || strlen($bytes) > article_docx_max_inline_image_bytes() ? $resized : '';
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

if (!function_exists('article_docx_html_attributes')) {
/**
 * @param array<string,string> $attributes
 */
function article_docx_html_attributes(array $attributes): string
{
    $html = '';
    foreach ($attributes as $name => $value) {
        if ($value !== '') {
            $html .= ' ' . $name . '="' . e($value) . '"';
        }
    }

    return $html;
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

if (!function_exists('article_docx_paragraph_attributes')) {
/**
 * @return array<string,string>
 */
function article_docx_paragraph_attributes(DOMElement $paragraph, DOMXPath $xpath): array
{
    $alignment = article_docx_paragraph_alignment($paragraph, $xpath);
    return $alignment === '' ? [] : ['align' => $alignment];
}
}

if (!function_exists('article_docx_paragraph_alignment')) {
function article_docx_paragraph_alignment(DOMElement $paragraph, DOMXPath $xpath): string
{
    $nodes = $xpath->query('./*[local-name()="pPr"]/*[local-name()="jc"]', $paragraph);
    $node = $nodes instanceof DOMNodeList ? $nodes->item(0) : null;
    if (!$node instanceof DOMElement) {
        return '';
    }

    return article_docx_normalized_alignment(article_docx_attribute($node, 'val'));
}
}

if (!function_exists('article_docx_normalized_alignment')) {
function article_docx_normalized_alignment(string $value): string
{
    return match (strtolower(trim($value))) {
        'left', 'start' => 'left',
        'right', 'end' => 'right',
        'center', 'centre' => 'center',
        'both', 'distribute', 'mediumkashida', 'highkashida', 'lowkashida', 'thaiDistribute' => 'justify',
        default => '',
    };
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
        if ($localName === 'fldSimple') {
            $inner = article_docx_inline_html($child, $xpath, $relationships, $imageDataUris, $noteBodies, $referencedNotes);
            $href = article_docx_field_hyperlink_href(article_docx_attribute($child, 'instr'));
            $html .= $href !== '' && !article_docx_html_is_empty($inner) ? '<a href="' . e($href) . '">' . $inner . '</a>' : $inner;
            continue;
        }
        if ($localName === 'del') {
            $inner = article_docx_inline_html($child, $xpath, $relationships, $imageDataUris, $noteBodies, $referencedNotes);
            $html .= article_docx_html_is_empty($inner) ? '' : '<s>' . $inner . '</s>';
            continue;
        }
        if ($localName === 'AlternateContent') {
            $html .= article_docx_alternate_content_inline_html($child, $xpath, $relationships, $imageDataUris, $noteBodies, $referencedNotes);
            continue;
        }
        if ($localName === 'drawing' || $localName === 'pict') {
            $html .= article_docx_run_image_html($child, $xpath, $imageDataUris);
            $html .= article_docx_textbox_inline_html($child, $xpath, $relationships, $imageDataUris, $noteBodies, $referencedNotes);
            $html .= article_docx_drawing_text_html($child, $xpath);
            continue;
        }
        if ($localName === 'tab') {
            $html .= '<span title="Tabulation">&emsp;</span>';
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
            $html .= '<span title="Tabulation">&emsp;</span>';
            continue;
        }
        if ($localName === 'br' || $localName === 'cr') {
            $breakType = strtolower(article_docx_attribute($child, 'type'));
            $html .= $breakType === 'page' ? '<hr>' : '<br>';
            continue;
        }
        if ($localName === 'lastRenderedPageBreak') {
            $html .= '<hr>';
            continue;
        }
        if ($localName === 'sym') {
            $symbol = strtoupper(trim(article_docx_attribute($child, 'char')));
            if (preg_match('/^[0-9A-F]{2,6}$/', $symbol) === 1) {
                $html .= '<span title="Symbole Word">&#x' . $symbol . ';</span>';
            }
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
            $html .= article_docx_drawing_text_html($child, $xpath);
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

if (!function_exists('article_docx_drawing_text_html')) {
function article_docx_drawing_text_html(DOMElement $container, DOMXPath $xpath): string
{
    $paragraphNodes = $xpath->query('.//*[local-name()="p" and namespace-uri()="http://schemas.openxmlformats.org/drawingml/2006/main"]', $container);
    if (!$paragraphNodes instanceof DOMNodeList || $paragraphNodes->length === 0) {
        return '';
    }

    $parts = [];
    foreach ($paragraphNodes as $paragraphNode) {
        if (!$paragraphNode instanceof DOMElement) {
            continue;
        }

        $textNodes = $xpath->query('.//*[local-name()="t" and namespace-uri()="http://schemas.openxmlformats.org/drawingml/2006/main"]', $paragraphNode);
        if (!$textNodes instanceof DOMNodeList || $textNodes->length === 0) {
            continue;
        }

        $text = '';
        foreach ($textNodes as $textNode) {
            $text .= $textNode->textContent;
        }
        $text = trim($text);
        if ($text !== '') {
            $parts[] = e($text);
        }
    }

    return $parts === [] ? '' : implode('<br>', $parts);
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
        $alignment = article_docx_image_alignment($container, $xpath);
        if ($alignment !== '') {
            $attributes['align'] = $alignment;
        }

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

if (!function_exists('article_docx_image_alignment')) {
function article_docx_image_alignment(DOMElement $container, DOMXPath $xpath): string
{
    $nodes = $xpath->query('.//*[local-name()="positionH"]/*[local-name()="align"]', $container);
    $node = $nodes instanceof DOMNodeList ? $nodes->item(0) : null;
    if (!$node instanceof DOMElement) {
        return '';
    }

    $alignment = article_docx_normalized_alignment($node->textContent);
    return in_array($alignment, ['left', 'right', 'center'], true) ? $alignment : '';
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

if (!function_exists('article_docx_image_dimensions_by_relationship_id')) {
/**
 * @return array<string,array{width:int,height:int}>
 */
function article_docx_image_dimensions_by_relationship_id(DOMXPath $xpath): array
{
    $imageNodes = $xpath->query('//*[local-name()="blip" or local-name()="imagedata"]');
    if (!$imageNodes instanceof DOMNodeList) {
        return [];
    }

    $dimensionsById = [];
    foreach ($imageNodes as $imageNode) {
        if (!$imageNode instanceof DOMElement) {
            continue;
        }

        $relationshipId = article_docx_attribute($imageNode, 'embed');
        if ($relationshipId === '') {
            $relationshipId = article_docx_attribute($imageNode, 'id');
        }
        if ($relationshipId === '') {
            continue;
        }

        $container = article_docx_image_container($imageNode);
        if (!$container instanceof DOMElement) {
            continue;
        }

        $dimensions = article_docx_image_dimensions($container, $xpath);
        $width = (int) ($dimensions['width'] ?? 0);
        $height = (int) ($dimensions['height'] ?? 0);
        if ($width <= 0 || $height <= 0) {
            continue;
        }

        $current = $dimensionsById[$relationshipId] ?? ['width' => 0, 'height' => 0];
        if ($width * $height > $current['width'] * $current['height']) {
            $dimensionsById[$relationshipId] = ['width' => $width, 'height' => $height];
        }
    }

    return $dimensionsById;
}
}

if (!function_exists('article_docx_image_container')) {
function article_docx_image_container(DOMElement $imageNode): ?DOMElement
{
    $node = $imageNode;
    while ($node->parentNode instanceof DOMElement) {
        $node = $node->parentNode;
        if (in_array($node->localName, ['drawing', 'pict'], true)) {
            return $node;
        }
    }

    return null;
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

if (!function_exists('article_docx_field_hyperlink_href')) {
function article_docx_field_hyperlink_href(string $instruction): string
{
    if (preg_match('/\bHYPERLINK\s+(?:"([^"]+)"|([^\s\\]+))/i', $instruction, $matches) !== 1) {
        return '';
    }

    $quotedTarget = (string) ($matches[1] ?? '');
    $unquotedTarget = (string) ($matches[2] ?? '');
    return article_docx_safe_href($quotedTarget !== '' ? $quotedTarget : $unquotedTarget);
}
}

if (!function_exists('article_docx_paragraph_has_page_break')) {
function article_docx_paragraph_has_page_break(DOMElement $paragraph, DOMXPath $xpath): bool
{
    $nodes = $xpath->query(
        './/*[local-name()="lastRenderedPageBreak"] | ./*[local-name()="pPr"]/*[local-name()="pageBreakBefore"] | .//*[local-name()="br" and translate(@*[local-name()="type"], "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="page"]',
        $paragraph,
    );

    return $nodes instanceof DOMNodeList && $nodes->length > 0;
}
}

require_once __DIR__ . '/article_docx_importer.php';
