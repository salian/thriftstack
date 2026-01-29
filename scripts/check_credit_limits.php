<?php

declare(strict_types=1);

require __DIR__ . '/../app/Database/DB.php';
require __DIR__ . '/../app/Mail/Mailer.php';
require __DIR__ . '/../app/Notifications/NotificationDispatcher.php';
require __DIR__ . '/../app/Notifications/NotificationService.php';
require __DIR__ . '/../app/AI/RateLimiter.php';

$config = require __DIR__ . '/../config.php';
$pdo = DB::connect($config);

$notificationService = new NotificationService($pdo, $config);
$rateLimiter = new RateLimiter($pdo);

$stmt = $pdo->query(
    'SELECT w.id, w.name, l.daily_limit, l.monthly_limit, l.alert_threshold_percent, l.last_alert_sent_at
     FROM workspace_credit_limits l
     JOIN workspaces w ON w.id = l.workspace_id'
);
$limits = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

$now = new DateTimeImmutable('now');
$dayStart = $now->setTime(0, 0, 0);
$monthStart = $now->modify('first day of this month')->setTime(0, 0, 0);

$sent = 0;
foreach ($limits as $row) {
    $workspaceId = (int)$row['id'];
    $dailyLimit = (int)($row['daily_limit'] ?? 0);
    $monthlyLimit = (int)($row['monthly_limit'] ?? 0);
    $threshold = (int)($row['alert_threshold_percent'] ?? 80);
    if ($threshold <= 0) {
        $threshold = 80;
    }

    $dailyUsage = $dailyLimit > 0 ? $rateLimiter->usageForPeriod($workspaceId, 'daily') : 0;
    $monthlyUsage = $monthlyLimit > 0 ? $rateLimiter->usageForPeriod($workspaceId, 'monthly') : 0;

    $shouldAlertDaily = $dailyLimit > 0 && $dailyUsage >= (int)ceil($dailyLimit * ($threshold / 100));
    $shouldAlertMonthly = $monthlyLimit > 0 && $monthlyUsage >= (int)ceil($monthlyLimit * ($threshold / 100));

    if (!$shouldAlertDaily && !$shouldAlertMonthly) {
        continue;
    }

    $lastAlert = $row['last_alert_sent_at'] ? new DateTimeImmutable((string)$row['last_alert_sent_at']) : null;
    $limitStart = $shouldAlertDaily ? $dayStart : $monthStart;
    if ($lastAlert && $lastAlert >= $limitStart) {
        continue;
    }

    $owners = $pdo->prepare(
        'SELECT user_id FROM workspace_memberships WHERE workspace_id = ? AND workspace_role = ?'
    );
    $owners->execute([$workspaceId, 'Workspace Owner']);
    $ownerIds = $owners->fetchAll(PDO::FETCH_COLUMN) ?: [];

    if (empty($ownerIds)) {
        continue;
    }

    $workspaceName = (string)($row['name'] ?? 'Workspace');
    $subject = $workspaceName . ' credit usage alert';
    $message = 'Your workspace has reached ' . $threshold . '% of its credit limit.';

    foreach ($ownerIds as $ownerId) {
        $notificationService->createInApp((int)$ownerId, $subject, $message);
        $notificationService->sendImmediateEmail((int)$ownerId, $subject, $message);
    }

    $update = $pdo->prepare('UPDATE workspace_credit_limits SET last_alert_sent_at = ? WHERE workspace_id = ?');
    $update->execute([date('Y-m-d H:i:s'), $workspaceId]);
    $sent++;
}

echo 'Credit limit alerts sent: ' . $sent . PHP_EOL;
