<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class NotificationContractsTest extends TestCase
{
    private function requireSeleniumDatabase(): void
    {
        if (!extension_loaded('pdo_mysql')) {
            self::markTestSkipped('pdo_mysql is required for notification database integration coverage.');
        }

        $configPath = str_replace('\\', '/', (string) getenv('ON4CRD_CONFIG_FILE'));
        if ($configPath === '' || !str_ends_with($configPath, '/storage/auth/selenium-config.php')) {
            self::markTestSkipped('Notification database integration coverage requires storage/auth/selenium-config.php.');
        }

        try {
            db()->query('SELECT 1');
        } catch (Throwable $throwable) {
            self::markTestSkipped('Selenium database is unavailable: ' . $throwable->getMessage());
        }
    }

    private function source(string $path): string
    {
        $source = file_get_contents(__DIR__ . '/../' . $path);
        self::assertIsString($source);

        return $source;
    }

    public function testMemberNotificationsSchemaIsMigratedAndNotificationCreationIsNonBlocking(): void
    {
        $notifications = $this->source('app/notifications.php');
        $schema = $this->source('schema/schema.sql');

        self::assertStringContainsString('CREATE TABLE IF NOT EXISTS member_notifications', $schema);
        self::assertStringContainsString('ALTER TABLE member_notifications ADD COLUMN member_id INT NOT NULL AFTER id', $notifications);
        self::assertStringContainsString('ALTER TABLE member_notifications MODIFY COLUMN type VARCHAR(64) NOT NULL DEFAULT "info"', $notifications);
        self::assertStringContainsString('member_notifications_schema_failed', $notifications);
        self::assertStringContainsString('member_notification_create_failed', $notifications);
        self::assertMatchesRegularExpression('/function notify_member\(.*?\): void\s*\{\s*try \{/s', $notifications);
    }

    public function testMemberNotificationHelpersCoverCreateReadAndMarkLifecycleAgainstDatabase(): void
    {
        $this->requireSeleniumDatabase();
        self::assertTrue(ensure_member_notifications_table());

        $memberId = (int) (db()->query('SELECT id FROM members WHERE is_active = 1 ORDER BY id ASC LIMIT 1')->fetchColumn() ?: 0);
        if ($memberId <= 0) {
            self::markTestSkipped('No active member is available for notification integration coverage.');
        }

        $token = 'phpunit-notification-' . bin2hex(random_bytes(4));
        $longType = 'type-' . str_repeat('x', 90);
        $longUrl = '/notifications/' . str_repeat('u', 300);

        try {
            db()->prepare('DELETE FROM member_notifications WHERE member_id = ? AND title LIKE ?')->execute([$memberId, $token . '%']);
            $unreadBefore = member_notifications_unread_count($memberId);

            notify_member($memberId, $longType, $token . ' first', ' Body one ', $longUrl);
            notify_member($memberId, 'moderation', $token . ' second', null, null);
            notify_member(0, 'ignored', $token . ' ignored', null, null);

            $rowsStmt = db()->prepare('SELECT id, type, title, body, url, is_read FROM member_notifications WHERE member_id = ? AND title LIKE ? ORDER BY id ASC');
            $rowsStmt->execute([$memberId, $token . '%']);
            $rows = $rowsStmt->fetchAll();
            self::assertCount(2, $rows);
            self::assertSame(mb_safe_substr($longType, 0, 64), $rows[0]['type']);
            self::assertSame('Body one', $rows[0]['body']);
            self::assertSame(mb_safe_substr($longUrl, 0, 255), $rows[0]['url']);
            self::assertSame('moderation', $rows[1]['type']);
            self::assertNull($rows[1]['body']);
            self::assertSame(0, (int) $rows[0]['is_read']);
            self::assertSame($unreadBefore + 2, member_notifications_unread_count($memberId));

            $recentOne = member_notifications_recent($memberId, 1);
            self::assertCount(1, $recentOne);
            self::assertStringStartsWith($token, (string) $recentOne[0]['title']);
            self::assertLessThanOrEqual(100, count(member_notifications_recent($memberId, 500)));
            self::assertSame([], member_notifications_recent(0));
            self::assertSame(0, member_notifications_unread_count(0));

            member_notification_mark_read($memberId, (int) $rows[0]['id']);
            $firstReadStmt = db()->prepare('SELECT is_read, read_at FROM member_notifications WHERE id = ? LIMIT 1');
            $firstReadStmt->execute([(int) $rows[0]['id']]);
            $firstRead = $firstReadStmt->fetch();
            self::assertIsArray($firstRead);
            self::assertSame(1, (int) $firstRead['is_read']);
            self::assertNotEmpty($firstRead['read_at']);

            member_notifications_mark_all_read($memberId);
            $unreadTokenStmt = db()->prepare('SELECT COUNT(*) FROM member_notifications WHERE member_id = ? AND title LIKE ? AND is_read = 0');
            $unreadTokenStmt->execute([$memberId, $token . '%']);
            self::assertSame(0, (int) $unreadTokenStmt->fetchColumn());

            $typeColumn = member_notifications_column_metadata('type');
            self::assertSame('varchar', strtolower((string) ($typeColumn['DATA_TYPE'] ?? '')));
            self::assertGreaterThanOrEqual(64, (int) ($typeColumn['CHARACTER_MAXIMUM_LENGTH'] ?? 0));
        } finally {
            db()->prepare('DELETE FROM member_notifications WHERE member_id = ? AND title LIKE ?')->execute([$memberId, $token . '%']);
        }
    }
}
