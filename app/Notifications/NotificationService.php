<?php

declare(strict_types=1);

final class NotificationService
{
    private PDO $pdo;
    private array $config;
    private NotificationDispatcher $dispatcher;

    public function __construct(PDO $pdo, array $config, ?NotificationDispatcher $dispatcher = null)
    {
        $this->pdo = $pdo;
        $this->config = $config;
        $this->dispatcher = $dispatcher ?? new NotificationDispatcher($config);
    }

    public function createInApp(int $userId, string $subject, string $body): int
    {
        return $this->insertNotification($userId, 'in_app', $subject, $body, 'stored', null);
    }

    public function sendImmediateEmail(int $userId, string $subject, string $body): bool
    {
        $notificationId = $this->insertNotification($userId, 'email', $subject, $body, 'pending', null);
        $email = $this->fetchUserEmail($userId);
        if ($email === null) {
            $this->updateNotificationStatus($notificationId, 'failed');
            return false;
        }

        $sent = $this->dispatcher->sendEmail($email, $subject, $body);
        $this->updateNotificationStatus($notificationId, $sent ? 'sent' : 'failed', $sent ? $this->now() : null);

        return $sent;
    }

    public function queueBatchedEmail(int $userId, string $subject, string $body): bool
    {
        $this->insertNotification($userId, 'email', $subject, $body, 'batched', null);
        $this->ensureBatchScheduled($userId);
        return true;
    }

    public function listForUser(int $userId, int $limit = 100): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, channel, subject, body, status, is_read, created_at, sent_at
             FROM notifications
             WHERE user_id = ?
             ORDER BY created_at DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function markRead(int $userId, int $notificationId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$notificationId, $userId]);

        return $stmt->rowCount() > 0;
    }

    public function dispatchDueBatches(): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, user_id FROM notification_batches
             WHERE status = ? AND scheduled_for <= ?
             ORDER BY scheduled_for ASC'
        );
        $now = $this->now();
        $stmt->execute(['pending', $now]);
        $batches = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $processed = 0;
        foreach ($batches as $batch) {
            $batchId = (int)$batch['id'];
            $userId = (int)$batch['user_id'];
            $notifications = $this->pendingBatchNotifications($userId);
            if (empty($notifications)) {
                $this->updateBatchStatus($batchId, 'sent');
                $processed++;
                continue;
            }

            $email = $this->fetchUserEmail($userId);
            if ($email === null) {
                $this->updateBatchStatus($batchId, 'failed');
                $processed++;
                continue;
            }

            $appName = (string)($this->config['app']['name'] ?? 'ThriftStack');
            $subject = $appName . ' daily digest';
            $sent = $this->dispatcher->sendDigest($email, $subject, $notifications, $appName);

            if ($sent) {
                $ids = array_map(static fn(array $row): int => (int)$row['id'], $notifications);
                $this->markNotificationsSent($ids, $now);
                $this->updateBatchStatus($batchId, 'sent');
            } else {
                $this->updateBatchStatus($batchId, 'failed');
            }

            $processed++;
        }

        return $processed;
    }

    private function insertNotification(
        int $userId,
        string $channel,
        string $subject,
        string $body,
        string $status,
        ?string $sentAt
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO notifications (user_id, channel, subject, body, status, is_read, created_at, sent_at)
             VALUES (?, ?, ?, ?, ?, 0, ?, ?)'
        );
        $stmt->execute([$userId, $channel, $subject, $body, $status, $this->now(), $sentAt]);
        return (int)$this->pdo->lastInsertId();
    }

    private function updateNotificationStatus(int $notificationId, string $status, ?string $sentAt = null): void
    {
        $stmt = $this->pdo->prepare('UPDATE notifications SET status = ?, sent_at = ? WHERE id = ?');
        $stmt->execute([$status, $sentAt, $notificationId]);
    }

    private function fetchUserEmail(int $userId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT email FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $email = $stmt->fetchColumn();
        return $email ? (string)$email : null;
    }

    private function ensureBatchScheduled(int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM notification_batches
             WHERE user_id = ? AND status = ? AND scheduled_for >= ?
             LIMIT 1'
        );
        $now = $this->now();
        $stmt->execute([$userId, 'pending', $now]);
        if ($stmt->fetchColumn()) {
            return;
        }

        $scheduled = $this->nextDigestTime();
        $insert = $this->pdo->prepare(
            'INSERT INTO notification_batches (user_id, scheduled_for, status, created_at)
             VALUES (?, ?, ?, ?)'
        );
        $insert->execute([$userId, $scheduled, 'pending', $now]);
    }

    private function pendingBatchNotifications(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, subject, body, created_at
             FROM notifications
             WHERE user_id = ? AND channel = ? AND status = ?
             ORDER BY created_at ASC'
        );
        $stmt->execute([$userId, 'email', 'batched']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function markNotificationsSent(array $ids, string $sentAt): void
    {
        if (empty($ids)) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge(['sent', $sentAt], $ids);
        $sql = sprintf('UPDATE notifications SET status = ?, sent_at = ? WHERE id IN (%s)', $placeholders);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    private function updateBatchStatus(int $batchId, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE notification_batches SET status = ? WHERE id = ?');
        $stmt->execute([$status, $batchId]);
    }

    private function nextDigestTime(): string
    {
        $now = new DateTimeImmutable('now');
        $scheduled = $now->setTime(9, 0, 0);
        if ($now >= $scheduled) {
            $scheduled = $scheduled->modify('+1 day');
        }

        return $scheduled->format('Y-m-d H:i:s');
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
