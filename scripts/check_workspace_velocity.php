<?php

declare(strict_types=1);

require __DIR__ . '/../app/Database/DB.php';
require __DIR__ . '/../app/Settings/WorkspaceSettingsService.php';
require __DIR__ . '/../app/Mail/Mailer.php';
require __DIR__ . '/../app/Notifications/NotificationDispatcher.php';
require __DIR__ . '/../app/Notifications/NotificationService.php';

$config = $GLOBALS['config'] ?? require __DIR__ . '/../config.php';
$pdo = DB::connect($config);
$settingsService = new WorkspaceSettingsService($pdo);
$notificationService = new NotificationService($pdo, $config);

$workspaces = $pdo->query('SELECT id, name FROM workspaces');
$rows = $workspaces ? $workspaces->fetchAll(PDO::FETCH_ASSOC) : [];

$today = new DateTimeImmutable('today');
$currentStart = $today->modify('-6 days')->format('Y-m-d 00:00:00');
$currentEnd = $today->format('Y-m-d 23:59:59');
$previousStart = $today->modify('-13 days')->format('Y-m-d 00:00:00');
$previousEnd = $today->modify('-7 days')->format('Y-m-d 23:59:59');

$sent = 0;
foreach ($rows as $workspace) {
    $workspaceId = (int)$workspace['id'];
    $settings = $settingsService->getSettings($workspaceId);
    $velocity = $settings['analytics']['velocity'] ?? ['enabled' => false, 'threshold_percent' => 50];
    if (empty($velocity['enabled'])) {
        continue;
    }

    $threshold = (int)($velocity['threshold_percent'] ?? 50);
    if ($threshold < 10) {
        $threshold = 10;
    }

    $current = usageForWorkspace($pdo, $workspaceId, $currentStart, $currentEnd);
    $previous = usageForWorkspace($pdo, $workspaceId, $previousStart, $previousEnd);
    $delta = $current - $previous;
    $pct = $previous > 0 ? ($delta / $previous) * 100 : ($current > 0 ? 100.0 : 0.0);
    if ($pct < $threshold) {
        continue;
    }

    $lastAlert = $settings['analytics']['velocity']['last_alert_date'] ?? '';
    if ($lastAlert === $today->format('Y-m-d')) {
        continue;
    }

    $subject = $workspace['name'] . ' credit usage spike';
    $message = sprintf(
        'Weekly credits increased to %d from %d (%.2f%%).',
        $current,
        $previous,
        $pct
    );

    $recipients = $pdo->prepare(
        'SELECT user_id FROM workspace_memberships
         WHERE workspace_id = ? AND workspace_role IN (?, ?)'
    );
    $recipients->execute([$workspaceId, 'Workspace Owner', 'Workspace Admin']);
    $userIds = $recipients->fetchAll(PDO::FETCH_COLUMN) ?: [];

    foreach ($userIds as $userId) {
        $notificationService->createInApp((int)$userId, $subject, $message);
        $notificationService->sendImmediateEmail((int)$userId, $subject, $message);
        $sent++;
    }

    $settings['analytics']['velocity']['last_alert_date'] = $today->format('Y-m-d');
    $settingsService->saveSettings($workspaceId, $settings);
}

echo 'Workspace velocity alerts sent: ' . $sent;

function usageForWorkspace(PDO $pdo, int $workspaceId, string $start, string $end): int
{
    $stmt = $pdo->prepare(
        'SELECT COALESCE(SUM(-credits), 0) AS credits
         FROM workspace_credit_ledger
         WHERE workspace_id = ? AND change_type = "consume" AND created_at >= ? AND created_at <= ?'
    );
    $stmt->execute([$workspaceId, $start, $end]);
    return (int)$stmt->fetchColumn();
}
