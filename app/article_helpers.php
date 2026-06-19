<?php
declare(strict_types=1);

/**
 * @param array<string,mixed> $row
 * @param list<string> $publicStatuses
 * @return array<string,mixed>
 */
function localized_translation_row(array $row, string $translationTable, string $sourceIdColumn, int $sourceId, array $publicStatuses): array
{
    $locale = current_locale();
    if (
        $locale === 'fr'
        || $sourceId <= 0
        || $publicStatuses === []
        || !preg_match('/^[a-z_]+$/', $translationTable)
        || !preg_match('/^[a-z_]+$/', $sourceIdColumn)
        || !table_exists($translationTable)
    ) {
        return $row;
    }

    try {
        $statusPlaceholders = implode(',', array_fill(0, count($publicStatuses), '?'));
        $stmt = db()->prepare('SELECT title, excerpt, content FROM ' . $translationTable . ' WHERE ' . $sourceIdColumn . ' = ? AND locale = ? AND status IN (' . $statusPlaceholders . ') ORDER BY CASE status WHEN "reviewed" THEN 0 ELSE 1 END, updated_at DESC LIMIT 1');
        $stmt->execute(array_merge([$sourceId, $locale], $publicStatuses));
        $translation = $stmt->fetch();
        if (is_array($translation)) {
            foreach (['title', 'excerpt', 'content'] as $field) {
                $value = trim((string) ($translation[$field] ?? ''));
                if ($value !== '') {
                    $row[$field] = $value;
                }
            }
        }
    } catch (Throwable) {
        // Keep the source row when translations are unavailable.
    }

    return $row;
}

/**
 * @param array<string,mixed> $row
 * @return array<string,mixed>
 */
function localized_article_row(array $row): array
{
    $row = localized_translation_row($row, 'article_translations', 'article_id', (int) ($row['id'] ?? 0), article_translation_public_statuses());
    $row['title_localized'] = (string) ($row['title'] ?? '');
    $row['excerpt_localized'] = (string) ($row['excerpt'] ?? '');
    $row['content_localized'] = (string) ($row['content'] ?? '');

    return $row;
}

function article_translation_source_hash(string $title, string $excerpt, string $content): string
{
    return substr(hash('sha256', trim($title) . "\n---excerpt---\n" . trim($excerpt) . "\n---content---\n" . trim($content)), 0, 40);
}

/**
 * @return list<string>
 */
function article_translation_public_statuses(): array
{
    return ['reviewed', 'auto'];
}

/**
 * @param array<string,mixed> $row
 */
function article_publication_datetime(array $row): ?string
{
    foreach (['published_at', 'created_at', 'updated_at'] as $field) {
        $value = trim((string) ($row[$field] ?? ''));
        if ($value !== '' && strtotime($value) !== false) {
            return $value;
        }
    }

    return null;
}

function article_publication_sort_expression(): string
{
    return article_publication_sort_expression_for_alias(null);
}

function article_publication_sort_expression_for_alias(?string $alias): string
{
    $prefix = '';
    if ($alias !== null) {
        $alias = trim($alias);
        if ($alias !== '' && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $alias) === 1) {
            $prefix = $alias . '.';
        }
    }

    return 'COALESCE(' . $prefix . 'published_at, ' . $prefix . 'created_at, ' . $prefix . 'updated_at)';
}

function article_is_duplicate_slug_error(Throwable $throwable): bool
{
    if (!$throwable instanceof PDOException) {
        return false;
    }

    $errorInfo = $throwable->errorInfo ?? [];
    $sqlState = (string) ($errorInfo[0] ?? $throwable->getCode());
    $driverCode = (string) ($errorInfo[1] ?? '');
    $message = strtolower($throwable->getMessage());

    return $sqlState === '23000'
        && (
            $driverCode === '1062'
            || str_contains($message, 'duplicate')
            || str_contains($message, 'unique')
            || str_contains($message, 'slug')
        );
}

function article_category_code(string $value): string
{
    return content_proposal_category_code($value, 120, 'autres');
}

function article_subcategory_code(string $value): string
{
    return content_taxonomy_code($value, 120, '', true);
}

function article_subcategory_ref(string $categoryCode, string $subcategoryCode): string
{
    $categoryCode = article_category_code($categoryCode !== '' ? $categoryCode : 'autres');
    $subcategoryCode = article_subcategory_code($subcategoryCode);

    return $subcategoryCode !== '' ? ($categoryCode . ':' . $subcategoryCode) : '';
}

/**
 * @return array{category:string,subcategory:string}
 */
function article_subcategory_ref_parts(string $value): array
{
    $value = trim($value);
    if ($value === '') {
        return ['category' => '', 'subcategory' => ''];
    }

    $parts = explode(':', $value, 2);
    if (count($parts) === 2) {
        return [
            'category' => article_category_code($parts[0] !== '' ? $parts[0] : 'autres'),
            'subcategory' => article_subcategory_code($parts[1]),
        ];
    }

    return ['category' => '', 'subcategory' => article_subcategory_code($value)];
}

function article_category_label_from_code(string $code): string
{
    $label = trim(str_replace('-', ' ', article_category_code($code)));
    return $label !== '' ? mb_convert_case($label, MB_CASE_TITLE, 'UTF-8') : 'Autres';
}

/**
 * @param array<string,mixed> $messages
 * @return array<string,string>
 */
function article_default_categories(array $messages = []): array
{
    return [
        'antennes' => (string) ($messages['theme_antennes'] ?? 'Antennes'),
        'trafic' => (string) ($messages['theme_trafic'] ?? 'Trafic & DX'),
        'numerique' => (string) ($messages['theme_numerique'] ?? 'Modes numeriques'),
        'materiel' => (string) ($messages['theme_materiel'] ?? 'Matériel & station'),
        'formation' => (string) ($messages['theme_formation'] ?? 'Formation'),
        'autres' => (string) ($messages['theme_autres'] ?? 'Autres'),
    ];
}

function article_ensure_categories_table(array $messages = []): bool
{
    try {
        db()->exec('CREATE TABLE IF NOT EXISTS article_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(120) NOT NULL UNIQUE,
            label VARCHAR(160) NOT NULL,
            sort_order INT NOT NULL DEFAULT 100,
            deleted_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_article_category_deleted (deleted_at)
        )');
        if (!table_has_column('article_categories', 'deleted_at')) {
            db()->exec('ALTER TABLE article_categories ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER sort_order');
        }
        if (!table_has_index('article_categories', 'idx_article_category_deleted')) {
            db()->exec('ALTER TABLE article_categories ADD INDEX idx_article_category_deleted (deleted_at)');
        }

        $insert = db()->prepare('INSERT IGNORE INTO article_categories (code, label, sort_order) VALUES (?, ?, ?)');
        $order = 10;
        foreach (article_default_categories($messages) as $code => $label) {
            $categoryCode = article_category_code((string) $code);
            $categoryLabel = content_proposal_clean_single_line((string) $label, 160);
            if ($categoryCode === '' || $categoryLabel === '') {
                continue;
            }
            $insert->execute([$categoryCode, $categoryLabel, $order]);
            $order += 10;
        }

        return table_exists('article_categories');
    } catch (Throwable) {
        return false;
    }
}

function article_ensure_subcategories_table(): bool
{
    try {
        db()->exec('CREATE TABLE IF NOT EXISTS article_subcategories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_code VARCHAR(120) NOT NULL,
            code VARCHAR(120) NOT NULL,
            label VARCHAR(160) NOT NULL,
            sort_order INT NOT NULL DEFAULT 100,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_article_subcategory (category_code, code),
            INDEX idx_article_subcategory_category (category_code)
        )');

        return table_exists('article_subcategories');
    } catch (Throwable) {
        return false;
    }
}

function article_ensure_taxonomy_schema(array $messages = []): bool
{
    try {
        if (table_exists('articles')) {
            if (!table_has_column('articles', 'subcategory')) {
                db()->exec('ALTER TABLE articles ADD COLUMN subcategory VARCHAR(120) NOT NULL DEFAULT "" AFTER category');
            }
            if (!table_has_index('articles', 'idx_articles_category')) {
                db()->exec('ALTER TABLE articles ADD INDEX idx_articles_category (category)');
            }
            if (!table_has_index('articles', 'idx_articles_subcategory')) {
                db()->exec('ALTER TABLE articles ADD INDEX idx_articles_subcategory (category, subcategory)');
            }
            db()->exec('UPDATE articles SET subcategory = "" WHERE subcategory IS NULL');
        }

        if (table_exists('article_revisions')) {
            if (!table_has_column('article_revisions', 'subcategory')) {
                db()->exec('ALTER TABLE article_revisions ADD COLUMN subcategory VARCHAR(120) NOT NULL DEFAULT "" AFTER category');
            }
            db()->exec('UPDATE article_revisions SET subcategory = "" WHERE subcategory IS NULL');
        }

        article_ensure_categories_table($messages);
        article_ensure_subcategories_table();

        return true;
    } catch (Throwable) {
        return false;
    }
}

/**
 * @param array<string,mixed> $messages
 * @return array<string,string>
 */
function article_categories(array $messages = []): array
{
    $categories = [];
    $deletedCategories = [];
    $categoryTableAvailable = article_ensure_categories_table($messages);
    if ($categoryTableAvailable) {
        try {
            foreach (db()->query('SELECT code FROM article_categories WHERE deleted_at IS NOT NULL')->fetchAll() ?: [] as $row) {
                $code = article_category_code((string) ($row['code'] ?? ''));
                if ($code !== '') {
                    $deletedCategories[$code] = true;
                }
            }
        } catch (Throwable) {
            $deletedCategories = [];
        }
    }

    foreach (article_default_categories($messages) as $code => $label) {
        $categoryCode = article_category_code((string) $code);
        if ($categoryCode !== '' && !isset($deletedCategories[$categoryCode])) {
            $categories[$categoryCode] = (string) $label;
        }
    }

    if ($categoryTableAvailable) {
        try {
            foreach (db()->query('SELECT code, label FROM article_categories WHERE deleted_at IS NULL ORDER BY sort_order ASC, label ASC')->fetchAll() ?: [] as $row) {
                $code = article_category_code((string) ($row['code'] ?? ''));
                $label = content_proposal_clean_single_line((string) ($row['label'] ?? $code), 160);
                if ($code !== '' && $label !== '') {
                    $categories[$code] = $label;
                }
            }
        } catch (Throwable) {
        }
    }

    foreach (content_proposal_accepted_categories('articles', 120) as $code => $label) {
        if ($code !== '' && !isset($deletedCategories[$code]) && !isset($categories[$code])) {
            $categories[$code] = $label;
        }
    }

    try {
        if (table_exists('articles') && table_has_column('articles', 'category')) {
            foreach (db()->query('SELECT category FROM articles WHERE category IS NOT NULL AND category <> "" GROUP BY category ORDER BY category ASC')->fetchAll() ?: [] as $row) {
                $code = article_category_code((string) ($row['category'] ?? ''));
                if ($code !== '' && !isset($deletedCategories[$code]) && !isset($categories[$code])) {
                    $categories[$code] = article_category_label_from_code($code);
                }
            }
        }
    } catch (Throwable) {
    }

    return $categories;
}

/**
 * @param array<string,string> $categories
 */
function article_category_from_input(string $value, array $categories): string
{
    $code = article_category_code($value);
    if ($code === '') {
        $code = 'autres';
    }
    if (!isset($categories[$code])) {
        throw new RuntimeException('Invalid article category.');
    }

    return $code;
}

/**
 * @return list<array{category_code:string,code:string,label:string}>
 */
function article_subcategory_options(): array
{
    if (!article_ensure_subcategories_table()) {
        return [];
    }

    try {
        $rows = db()->query('SELECT category_code, code, label FROM article_subcategories ORDER BY category_code ASC, sort_order ASC, label ASC')->fetchAll() ?: [];
    } catch (Throwable) {
        $rows = [];
    }

    $options = [];
    foreach ($rows as $row) {
        $categoryCode = article_category_code((string) ($row['category_code'] ?? 'autres'));
        $code = article_subcategory_code((string) ($row['code'] ?? ''));
        $label = content_proposal_clean_single_line((string) ($row['label'] ?? $code), 160);
        if ($categoryCode !== '' && $code !== '' && $label !== '') {
            $options[] = ['category_code' => $categoryCode, 'code' => $code, 'label' => $label];
        }
    }

    return $options;
}

/**
 * @return array<string,list<array{category_code:string,code:string,label:string,total?:int}>>
 */
function article_subcategories_by_category(): array
{
    $byCategory = [];
    foreach (article_subcategory_options() as $subcategory) {
        $byCategory[$subcategory['category_code']][] = $subcategory;
    }

    return $byCategory;
}

/**
 * @param array<string,string> $categories
 * @param array<string,int> $countsByCategory
 * @return array<string,string>
 */
function article_visible_categories(array $categories, array $countsByCategory): array
{
    $visible = [];
    foreach ($categories as $code => $label) {
        if ((int) ($countsByCategory[(string) $code] ?? 0) <= 0) {
            continue;
        }
        $visible[(string) $code] = (string) $label;
    }

    return $visible;
}

/**
 * @param array<string,list<array<string,mixed>>> $subcategoriesByCategory
 * @param array<string,int> $countsBySubcategory
 * @return array<string,list<array<string,mixed>>>
 */
function article_visible_subcategories_by_category(array $subcategoriesByCategory, array $countsBySubcategory): array
{
    $visible = [];
    foreach ($subcategoriesByCategory as $categoryCode => $subcategories) {
        foreach ($subcategories as $subcategory) {
            $code = article_subcategory_code((string) $subcategory['code']);
            $count = (int) ($countsBySubcategory[(string) $categoryCode . ':' . $code] ?? 0);
            if ($code === '' || $count <= 0) {
                continue;
            }
            $subcategory['total'] = $count;
            $visible[(string) $categoryCode][] = $subcategory;
        }
    }

    return $visible;
}

/**
 * @param array<string,mixed> $messages
 */
function article_favorites_label(array $messages, string $locale = ''): string
{
    $label = trim((string) ($messages['favorites'] ?? ''));
    if ($label !== '') {
        return $label;
    }
    if ($locale === 'fr') {
        return 'Favoris';
    }

    $favorite = trim((string) ($messages['favorite_label'] ?? $messages['favorite'] ?? ''));
    return $favorite !== '' && $favorite !== 'Favori' ? $favorite : 'Favorites';
}

/**
 * @return list<int>
 */
function article_favorite_article_ids(int $memberId): array
{
    if (
        $memberId <= 0
        || !function_exists('ensure_member_favorites_table')
        || !ensure_member_favorites_table()
        || !table_exists('articles')
    ) {
        return [];
    }

    try {
        $stmt = db()->prepare('SELECT a.id FROM member_favorites f INNER JOIN articles a ON a.id = f.target_id WHERE f.member_id = ? AND f.target_type = ? AND a.status = "published" ORDER BY f.created_at DESC, f.id DESC');
        $stmt->execute([$memberId, 'article']);

        return array_values(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []));
    } catch (Throwable) {
        return [];
    }
}

/**
 * @param array<string,string> $categories
 * @param array<string,mixed> $labels
 */
function render_article_taxonomy_fields(array $categories, array $labels = [], string $selectedCategory = 'autres', string $selectedSubcategory = ''): string
{
    $selectedCategory = article_category_code($selectedCategory !== '' ? $selectedCategory : 'autres');
    $selectedSubcategory = article_subcategory_code($selectedSubcategory);
    $subcategoriesByCategory = article_subcategories_by_category();
    $categoryLabel = (string) ($labels['category_label'] ?? $labels['category'] ?? 'Catégorie');
    $subcategoryLabel = (string) ($labels['subcategory_field'] ?? $labels['subcategory'] ?? 'Sous-thématique');
    $noSubcategory = (string) ($labels['no_subcategory'] ?? 'Sans sous-thématique');

    $html = '<label><span>' . e($categoryLabel) . '</span><select name="category">';
    foreach ($categories as $code => $label) {
        $html .= '<option value="' . e((string) $code) . '"' . ($selectedCategory === (string) $code ? ' selected' : '') . '>' . e((string) $label) . '</option>';
    }
    $html .= '</select></label>'
        . '<label><span>' . e($subcategoryLabel) . '</span><select name="subcategory_ref">'
        . '<option value="">' . e($noSubcategory) . '</option>';
    foreach ($subcategoriesByCategory as $parentCode => $subcategories) {
        $html .= '<optgroup label="' . e((string) ($categories[(string) $parentCode] ?? article_category_label_from_code((string) $parentCode))) . '">';
        foreach ($subcategories as $subcategory) {
            $code = article_subcategory_code((string) $subcategory['code']);
            if ($code === '') {
                continue;
            }
            $html .= '<option value="' . e(article_subcategory_ref((string) $parentCode, $code)) . '"'
                . ($selectedCategory === (string) $parentCode && $selectedSubcategory === $code ? ' selected' : '')
                . '>' . e((string) $subcategory['label']) . '</option>';
        }
        $html .= '</optgroup>';
    }

    return $html . '</select></label>';
}

function article_slug_base(string $value, int $maxLength = 190): string
{
    $maxLength = max(1, $maxLength);
    $base = slugify($value);
    if ($base === '' || $base === 'n-a') {
        $base = 'article';
    }
    if (strlen($base) > $maxLength) {
        $base = substr($base, 0, $maxLength);
    }

    $base = trim($base, '-');
    return $base !== '' ? $base : 'article';
}

function article_slug_candidate(string $base, int $suffix = 0, int $maxLength = 190): string
{
    $maxLength = max(1, $maxLength);
    $base = article_slug_base($base, $maxLength);
    if ($suffix <= 1) {
        return $base;
    }

    $suffixText = '-' . $suffix;
    $prefixLength = max(1, $maxLength - strlen($suffixText));
    $prefix = rtrim(substr($base, 0, $prefixLength), '-');
    if ($prefix === '') {
        $prefix = substr('article', 0, $prefixLength);
    }

    return $prefix . $suffixText;
}

function article_unique_slug(string $value, int $ignoreId = 0, int $maxLength = 190): string
{
    $base = article_slug_base($value, $maxLength);
    $suffix = 1;
    do {
        $candidate = article_slug_candidate($base, $suffix, $maxLength);
        $stmt = db()->prepare('SELECT id FROM articles WHERE slug = ? AND id <> ? LIMIT 1');
        $stmt->execute([$candidate, max(0, $ignoreId)]);
        if (!$stmt->fetchColumn()) {
            return $candidate;
        }
        $suffix++;
    } while ($suffix < 10000);

    throw new RuntimeException('Impossible de générer un slug article unique.');
}

/**
 * @param array<string,mixed> $existing
 * @param array{title:string,excerpt:string,content:string} $source
 */
function article_translation_pending_row_is_source_fallback(array $existing, array $source): bool
{
    if ((string) ($existing['status'] ?? '') !== 'needs_review') {
        return false;
    }

    foreach (['title', 'excerpt', 'content'] as $field) {
        if (trim((string) ($existing[$field] ?? '')) !== trim((string) $source[$field])) {
            return false;
        }
    }

    return true;
}

/**
 * @return list<string>
 */
function article_translation_target_locales(): array
{
    return array_values(array_filter(
        supported_locales(),
        static fn(string $locale): bool => $locale !== 'fr'
    ));
}

function article_sanitize_content(string $html): string
{
    $html = trim(sanitize_rich_html($html));
    if ($html === '') {
        return '';
    }

    $allowedTags = array_fill_keys([
        'p',
        'br',
        'strong',
        'b',
        'em',
        'i',
        'u',
        'ul',
        'ol',
        'li',
        'h2',
        'h3',
        'h4',
        'blockquote',
        'pre',
        'code',
        'a',
        'img',
        'figure',
        'figcaption',
        'table',
        'thead',
        'tbody',
        'tr',
        'th',
        'td',
        'hr',
    ], true);
    $allowedAttributes = [
        'a' => ['href' => true, 'title' => true, 'target' => true, 'rel' => true],
        'img' => ['src' => true, 'alt' => true, 'title' => true, 'width' => true, 'height' => true, 'loading' => true],
        'th' => ['colspan' => true, 'rowspan' => true],
        'td' => ['colspan' => true, 'rowspan' => true],
    ];

    $dom = new DOMDocument();
    $previousUseInternalErrors = libxml_use_internal_errors(true);
    $dom->loadHTML('<!doctype html><html><body>' . $html . '</body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previousUseInternalErrors);

    $allNodes = $dom->getElementsByTagName('*');
    for ($i = $allNodes->length - 1; $i >= 0; $i--) {
        $node = $allNodes->item($i);
        if (!$node instanceof DOMElement) {
            continue;
        }

        $tag = strtolower($node->tagName);
        if ($tag === 'html' || $tag === 'body') {
            continue;
        }

        if (!isset($allowedTags[$tag])) {
            $parent = $node->parentNode;
            if ($parent === null) {
                continue;
            }
            while ($node->firstChild !== null) {
                $parent->insertBefore($node->firstChild, $node);
            }
            $parent->removeChild($node);
            continue;
        }

        $allowedForTag = $allowedAttributes[$tag] ?? [];
        if ($node->hasAttributes()) {
            $toRemove = [];
            foreach ($node->attributes as $attribute) {
                $name = strtolower($attribute->name);
                if (!isset($allowedForTag[$name])) {
                    $toRemove[] = $attribute->name;
                }
            }
            foreach ($toRemove as $attrName) {
                $node->removeAttribute($attrName);
            }
        }

        if ($tag === 'a') {
            if ($node->hasAttribute('href')) {
                $safeHref = sanitize_href_attribute($node->getAttribute('href'));
                if ($safeHref === null) {
                    $node->removeAttribute('href');
                } else {
                    $node->setAttribute('href', $safeHref);
                }
            }
            $target = strtolower(trim($node->getAttribute('target')));
            if ($target !== '' && !in_array($target, ['_blank', '_self'], true)) {
                $node->removeAttribute('target');
                $target = '';
            }
            if ($target === '_blank') {
                $node->setAttribute('rel', 'noopener noreferrer');
            } elseif ($node->hasAttribute('rel')) {
                $rel = trim((string) preg_replace('/[^a-z0-9 _-]+/i', '', $node->getAttribute('rel')));
                if ($rel === '') {
                    $node->removeAttribute('rel');
                } else {
                    $node->setAttribute('rel', $rel);
                }
            }
        }

        if ($tag === 'img') {
            $safeSrc = $node->hasAttribute('src') ? sanitize_image_src_attribute($node->getAttribute('src')) : null;
            if ($safeSrc === null) {
                if ($node->parentNode !== null) {
                    $node->parentNode->removeChild($node);
                }
                continue;
            }
            $node->setAttribute('src', $safeSrc);
            $node->setAttribute('loading', 'lazy');
            foreach (['width', 'height'] as $dimension) {
                if (!$node->hasAttribute($dimension)) {
                    continue;
                }
                $value = (int) $node->getAttribute($dimension);
                if ($value <= 0 || $value > 2000 || !preg_match('/^\d+$/', $node->getAttribute($dimension))) {
                    $node->removeAttribute($dimension);
                } else {
                    $node->setAttribute($dimension, (string) $value);
                }
            }
        }

        if ($tag === 'td' || $tag === 'th') {
            foreach (['colspan', 'rowspan'] as $span) {
                if (!$node->hasAttribute($span)) {
                    continue;
                }
                $value = (int) $node->getAttribute($span);
                if ($value <= 0 || $value > 20 || !preg_match('/^\d+$/', $node->getAttribute($span))) {
                    $node->removeAttribute($span);
                } else {
                    $node->setAttribute($span, (string) $value);
                }
            }
        }
    }

    $body = $dom->getElementsByTagName('body')->item(0);
    if (!$body) {
        return '';
    }

    $result = '';
    foreach ($body->childNodes as $child) {
        $result .= $dom->saveHTML($child);
    }

    return trim($result);
}

function article_translation_deepl_target(string $locale): ?string
{
    $locale = strtolower(trim($locale));
    $targets = [
        'en' => 'EN',
        'de' => 'DE',
        'nl' => 'NL',
        'it' => 'IT',
        'es' => 'ES',
        'pt' => 'PT-PT',
        'bg' => 'BG',
        'hr' => 'HR',
        'cs' => 'CS',
        'da' => 'DA',
        'et' => 'ET',
        'fi' => 'FI',
        'el' => 'EL',
        'hu' => 'HU',
        'ga' => 'GA',
        'lv' => 'LV',
        'lt' => 'LT',
        'mt' => 'MT',
        'pl' => 'PL',
        'ro' => 'RO',
        'sk' => 'SK',
        'sl' => 'SL',
        'sv' => 'SV',
        'ar' => 'AR',
        'hi' => 'HI',
        'ja' => 'JA',
        'zh' => 'ZH-HANS',
        'bn' => 'BN',
        'ru' => 'RU',
        'id' => 'ID',
    ];

    return $targets[$locale] ?? null;
}

/**
 * @param list<string> $texts
 * @return list<string>|null
 */
function article_translation_deepl_translate(array $texts, string $locale): ?array
{
    static $deeplUnavailable = false;

    if ($deeplUnavailable) {
        return null;
    }

    $texts = array_map(static fn(string $text): string => trim($text), $texts);
    if ($texts === [] || implode('', $texts) === '') {
        return null;
    }

    if (strtolower((string) config('translation.provider', 'none')) !== 'deepl') {
        return null;
    }

    $apiKey = trim((string) config('translation.deepl_api_key', ''));
    $targetLang = article_translation_deepl_target($locale);
    if ($apiKey === '' || $targetLang === null) {
        return null;
    }

    $configuredEndpoint = trim((string) config('translation.deepl_api_url', ''));
    $endpoint = $configuredEndpoint !== ''
        ? $configuredEndpoint
        : (str_ends_with($apiKey, ':fx') ? 'https://api-free.deepl.com/v2/translate' : 'https://api.deepl.com/v2/translate');

    $body = http_build_query([
        'auth_key' => $apiKey,
        'source_lang' => 'FR',
        'target_lang' => $targetLang,
        'tag_handling' => 'html',
        'preserve_formatting' => '1',
    ], '', '&', PHP_QUERY_RFC3986);
    foreach ($texts as $text) {
        $body .= '&text=' . rawurlencode($text);
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $body,
            'timeout' => 6,
            'ignore_errors' => true,
        ],
    ]);

    try {
        $response = @file_get_contents($endpoint, false, $context);
    } catch (Throwable) {
        $deeplUnavailable = true;
        return null;
    }
    if (!is_string($response) || trim($response) === '') {
        $deeplUnavailable = true;
        return null;
    }

    $payload = json_decode($response, true);
    if (!is_array($payload) || !isset($payload['translations']) || !is_array($payload['translations'])) {
        $deeplUnavailable = true;
        return null;
    }

    $translated = [];
    foreach ($payload['translations'] as $translation) {
        if (!is_array($translation)) {
            $deeplUnavailable = true;
            return null;
        }
        $translated[] = (string) ($translation['text'] ?? '');
    }

    if (count($translated) !== count($texts)) {
        $deeplUnavailable = true;
        return null;
    }

    return $translated;
}

/**
 * @param array{title:string,excerpt:string,content:string} $source
 * @return array{title:string,excerpt:string,content:string,status:string}
 */
function article_translation_auto_fields(array $source, string $locale): array
{
    $sourceTitle = (string) $source['title'];
    $sourceExcerpt = (string) $source['excerpt'];
    $sourceContent = (string) $source['content'];
    $translated = article_translation_deepl_translate([$sourceTitle, $sourceExcerpt, $sourceContent], $locale);
    if (is_array($translated)) {
        return [
            'title' => trim($translated[0]) !== '' ? trim($translated[0]) : $sourceTitle,
            'excerpt' => trim($translated[1]) !== '' ? trim($translated[1]) : $sourceExcerpt,
            'content' => trim($translated[2]) !== '' ? article_sanitize_content($translated[2]) : $sourceContent,
            'status' => 'auto',
        ];
    }

    return [
        'title' => $sourceTitle,
        'excerpt' => $sourceExcerpt,
        'content' => $sourceContent,
        'status' => 'needs_review',
    ];
}

function article_translation_upsert(int $articleId, string $locale, ?string $title = null, ?string $summary = null, ?string $content = null): void
{
    if ($articleId <= 0 || $locale === 'fr' || !table_exists('article_translations')) {
        return;
    }

    $sourceStmt = db()->prepare('SELECT title, excerpt, content FROM articles WHERE id = ? LIMIT 1');
    $sourceStmt->execute([$articleId]);
    $source = $sourceStmt->fetch();
    if (!is_array($source)) {
        return;
    }

    $sourceFields = [
        'title' => (string) ($source['title'] ?? ''),
        'excerpt' => (string) ($source['excerpt'] ?? ''),
        'content' => (string) ($source['content'] ?? ''),
    ];
    $sourceHash = article_translation_source_hash($sourceFields['title'], $sourceFields['excerpt'], $sourceFields['content']);

    $existingStmt = db()->prepare('SELECT status, source_hash, title, excerpt, content FROM article_translations WHERE article_id = ? AND locale = ? LIMIT 1');
    $existingStmt->execute([$articleId, $locale]);
    $existing = $existingStmt->fetch() ?: null;
    if ($title === null && $summary === null && $content === null && is_array($existing) && (string) ($existing['source_hash'] ?? '') === $sourceHash) {
        $existingStatus = (string) ($existing['status'] ?? '');
        if (in_array($existingStatus, ['reviewed', 'auto'], true)) {
            return;
        }
        if ($existingStatus === 'needs_review' && !article_translation_pending_row_is_source_fallback($existing, $sourceFields)) {
            return;
        }
    }

    if ($title === null && $summary === null && $content === null) {
        $fields = article_translation_auto_fields($sourceFields, $locale);
    } else {
        $fields = [
            'title' => trim((string) ($title ?? '')) !== '' ? trim((string) $title) : $sourceFields['title'],
            'excerpt' => trim((string) ($summary ?? '')) !== '' ? trim((string) $summary) : $sourceFields['excerpt'],
            'content' => trim((string) ($content ?? '')) !== '' ? article_sanitize_content((string) $content) : $sourceFields['content'],
            'status' => 'needs_review',
        ];
    }

    $update = db()->prepare('UPDATE article_translations SET source_hash = ?, title = ?, excerpt = ?, content = ?, status = ?, reviewed_by = NULL, reviewed_at = NULL, updated_at = NOW() WHERE article_id = ? AND locale = ?');
    $update->execute([$sourceHash, $fields['title'], $fields['excerpt'], $fields['content'], $fields['status'], $articleId, $locale]);
    if ($update->rowCount() > 0) {
        return;
    }

    db()->prepare('INSERT INTO article_translations (article_id, locale, source_hash, title, excerpt, content, status) VALUES (?, ?, ?, ?, ?, ?, ?)')
        ->execute([$articleId, $locale, $sourceHash, $fields['title'], $fields['excerpt'], $fields['content'], $fields['status']]);
}

function article_translations_sync_all(int $articleId): int
{
    $count = 0;
    foreach (article_translation_target_locales() as $locale) {
        article_translation_upsert($articleId, $locale);
        $count++;
    }

    return $count;
}
