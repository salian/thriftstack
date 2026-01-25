<?php

declare(strict_types=1);

require __DIR__ . '/../app/Mail/Mailer.php';
require __DIR__ . '/../app/Notifications/NotificationDispatcher.php';
require __DIR__ . '/../app/Notifications/NotificationService.php';

final class NotificationServiceTest extends TestCase
{
    public function run(): void
    {
        $_SESSION = [];

        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA foreign_keys = ON');

        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT)');
        $pdo->exec('CREATE TABLE notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            channel TEXT NOT NULL,
            subject TEXT NOT NULL,
            body TEXT NOT NULL,
            status TEXT NOT NULL,
            is_read INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL,
            sent_at TEXT NULL
        )');
        $pdo->exec('CREATE TABLE notification_batches (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            scheduled_for TEXT NOT NULL,
            status TEXT NOT NULL,
            created_at TEXT NOT NULL
        )');

        $pdo->exec("INSERT INTO users (email) VALUES ('user@example.com')");

        $sent = [];
        $config = ['app' => ['name' => 'ThriftStack']];
        $dispatcher = new NotificationDispatcher($config, null, function ($to, $subject, $body) use (&$sent) {
            $sent[] = ['to' => $to, 'subject' => $subject, 'body' => $body];
            return true;
        });
        $service = new NotificationService($pdo, $config, $dispatcher);

        $service->createInApp(1, 'Welcome', 'Hello');
        $list = $service->listForUser(1);
        $this->assertEquals(1, count($list), 'In-app notification missing');
        $this->assertEquals('stored', $list[0]['status'], 'In-app status incorrect');

        $service->sendImmediateEmail(1, 'Immediate', 'Now');
        $emailRow = $pdo->query('SELECT status, sent_at FROM notifications WHERE channel = "email" AND status = "sent"')->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($emailRow, 'Immediate email not sent');

        $service->queueBatchedEmail(1, 'Digest', 'Later');
        $pdo->exec('UPDATE notification_batches SET scheduled_for = "2000-01-01 00:00:00" WHERE status = "pending"');
        $processed = $service->dispatchDueBatches();
        $this->assertEquals(1, $processed, 'Batch not processed');
        $batchRow = $pdo->query('SELECT status FROM notification_batches')->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('sent', $batchRow['status'], 'Batch status incorrect');
        $this->assertTrue(count($sent) > 0, 'Digest email not sent');
    }
}
