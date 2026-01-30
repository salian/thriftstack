<?php

declare(strict_types=1);

require __DIR__ . '/../app/Database/DB.php';
require __DIR__ . '/../app/Settings/AppSettingsService.php';
require __DIR__ . '/../app/Mail/Mailer.php';
require __DIR__ . '/../app/Notifications/NotificationDispatcher.php';
require __DIR__ . '/../app/Notifications/NotificationService.php';

$config = $GLOBALS['config'] ?? require __DIR__ . '/../config.php';
$pdo = DB::connect($config);
$appSettings = new AppSettingsService($pdo);
$notificationService = new NotificationService($pdo, $config);

$enabled = $appSettings->get('analytics.velocity.enabled', '0') === '1';
if (!$enabled) {
    echo 'Velocity alerts disabled';
    return;
}

$threshold = (int)$appSettings->get('analytics.velocity.threshold_percent', '50');
if ($threshold < 10) {
    $threshold = 10;
}

$today = new DateTimeImmutable('today');
$lastAlertDate = (string)$appSettings->get('analytics.velocity.last_alert_date', '');
if ($lastAlertDate === $today->format('Y-m-d')) {
    echo 'Velocity alerts already sent';
    return;
}

$currentStart = $today->modify('-6 days')->format('Y-m-d 00:00:00');
$currentEnd = $today->format('Y-m-d 23:59:59');
$previousStart = $today->modify('-13 days')->format('Y-m-d 00:00:00');
$previousEnd = $today->modify('-7 days')->format('Y-m-d 23:59:59');

$currentTotals = totalsByWorkspace($pdo, $currentStart, $currentEnd);
$previousTotals = totalsByWorkspace($pdo, $previousStart, $previousEnd);

$spikes = [];
foreach ($currentTotals as $workspaceId => $currentCredits) {
    $prevCredits = $previousTotals[$workspaceId] ?? 0;
    $delta = $currentCredits - $prevCredits;
    $pct = $prevCredits > 0 ? ($delta / $prevCredits) * 100 : ($currentCredits > 0 ? 100.0 : 0.0);
    if ($pct >= $threshold) {
        $spikes[$workspaceId] = [
            'current' => $currentCredits,
            'previous' => $prevCredits,
            'pct' => round($pct, 2),
        ];
    }
}

if (empty($spikes)) {
    echo 'Velocity alerts sent: 0';
    return;
}

$workspaceNames = workspaceNames($pdo, array_keys($spikes));
$messageLines = ['Weekly credit usage velocity alerts:'];
foreach ($spikes as $workspaceId => $data) {
    $name = $workspaceNames[$workspaceId] ?? ('Workspace #' . $workspaceId);
    $messageLines[] = sprintf(
        '- %s: %d credits vs %d (%.2f%%)',
        $name,
        $data['current'],
        $data['previous'],
        $data['pct']
    );
}

$subject = 'Credit spending velocity alert';
$body = implode("\n", $messageLines);

$admins = $pdo->query('SELECT id FROM users WHERE is_system_admin = 1 AND status = "active"');
$adminIds = $admins ? $admins->fetchAll(PDO::FETCH_COLUMN) : [];
$sent = 0;
foreach ($adminIds as $adminId) {
    $notificationService->createInApp((int)$adminId, $subject, $body);
    $notificationService->sendImmediateEmail((int)$adminId, $subject, $body);
    $sent++;
}

$appSettings->set('analytics.velocity.last_alert_date', $today->format('Y-m-d'));

echo 'Velocity alerts sent: ' . $sent;

function totalsByWorkspace(PDO $pdo, string $start, string $end): array
{
    $stmt = $pdo->prepare(
        'SELECT workspace_id, SUM(-credits) AS credits
         FROM workspace_credit_ledger
         WHERE change_type = "consume" AND created_at >= ? AND created_at <= ?
         GROUP BY workspace_id'
    );
    $stmt->execute([$start, $end]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $totals = [];
    foreach ($rows as $row) {
        $totals[(int)$row['workspace_id']] = (int)$row['credits'];
    }

    return $totals;
}

function workspaceNames(PDO $pdo, array $ids): array
{
    if (empty($ids)) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare('SELECT id, name FROM workspaces WHERE id IN (' . $placeholders . ')');
    $stmt->execute($ids);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $names = [];
    foreach ($rows as $row) {
        $names[(int)$row['id']] = (string)$row['name'];
    }
    return $names;
}
