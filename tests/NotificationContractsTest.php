<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class NotificationContractsTest extends TestCase
{
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
}
