<?php
declare(strict_types=1);

if (!function_exists('album_ensure_schema_columns_and_indexes')) {
function album_ensure_schema_columns_and_indexes(bool $normalizeVisibilityDefault = false): bool
{
    if (!table_exists('albums')) {
        return false;
    }

    $columns = [
        'member_id' => 'ALTER TABLE albums ADD COLUMN member_id INT DEFAULT NULL AFTER id',
        'category' => 'ALTER TABLE albums ADD COLUMN category VARCHAR(120) NOT NULL DEFAULT "general" AFTER member_id',
        'subcategory' => 'ALTER TABLE albums ADD COLUMN subcategory VARCHAR(120) NOT NULL DEFAULT "" AFTER category',
        'title' => 'ALTER TABLE albums ADD COLUMN title VARCHAR(190) NOT NULL DEFAULT "Album" AFTER subcategory',
        'description' => 'ALTER TABLE albums ADD COLUMN description TEXT DEFAULT NULL AFTER title',
        'is_public' => 'ALTER TABLE albums ADD COLUMN is_public TINYINT(1) NOT NULL DEFAULT 0 AFTER description',
        'source_proposal_id' => 'ALTER TABLE albums ADD COLUMN source_proposal_id INT NULL AFTER is_public',
        'publish_requested' => 'ALTER TABLE albums ADD COLUMN publish_requested TINYINT(1) NOT NULL DEFAULT 0 AFTER source_proposal_id',
        'facebook_album_id' => 'ALTER TABLE albums ADD COLUMN facebook_album_id VARCHAR(80) DEFAULT NULL AFTER publish_requested',
        'facebook_post_id' => 'ALTER TABLE albums ADD COLUMN facebook_post_id VARCHAR(80) DEFAULT NULL AFTER facebook_album_id',
        'instagram_media_id' => 'ALTER TABLE albums ADD COLUMN instagram_media_id VARCHAR(80) DEFAULT NULL AFTER facebook_post_id',
        'social_published_at' => 'ALTER TABLE albums ADD COLUMN social_published_at DATETIME DEFAULT NULL AFTER instagram_media_id',
        'social_publish_error' => 'ALTER TABLE albums ADD COLUMN social_publish_error TEXT DEFAULT NULL AFTER social_published_at',
        'created_at' => 'ALTER TABLE albums ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER social_publish_error',
    ];
    foreach ($columns as $column => $sql) {
        if (!table_has_column('albums', (string) $column)) {
            db()->exec($sql);
        }
    }

    $indexes = [
        'idx_albums_member' => 'ALTER TABLE albums ADD INDEX idx_albums_member (member_id)',
        'idx_albums_category' => 'ALTER TABLE albums ADD INDEX idx_albums_category (category)',
        'idx_albums_subcategory' => 'ALTER TABLE albums ADD INDEX idx_albums_subcategory (category, subcategory)',
        'idx_albums_source_proposal' => 'ALTER TABLE albums ADD INDEX idx_albums_source_proposal (source_proposal_id)',
    ];
    foreach ($indexes as $index => $sql) {
        if (!table_has_index('albums', (string) $index)) {
            db()->exec($sql);
        }
    }

    if ($normalizeVisibilityDefault) {
        db()->exec('ALTER TABLE albums MODIFY COLUMN is_public TINYINT(1) NOT NULL DEFAULT 0');
    }

    return true;
}
}
