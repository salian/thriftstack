<?php

declare(strict_types=1);

$config = $GLOBALS['config'] ?? null;
if (!class_exists('Bootstrap')) {
    require __DIR__ . '/../app/Bootstrap.php';
}
if (!class_exists('DB')) {
    require __DIR__ . '/../app/Database/DB.php';
}
if (!class_exists('AppSettingsService')) {
    require __DIR__ . '/../app/Settings/AppSettingsService.php';
}
if (!class_exists('WorkspaceSettingsService')) {
    require __DIR__ . '/../app/Settings/WorkspaceSettingsService.php';
}
if (!class_exists('NotificationDispatcher')) {
    require __DIR__ . '/../app/Notifications/NotificationDispatcher.php';
}
if (!class_exists('Mailer')) {
    require __DIR__ . '/../app/Mail/Mailer.php';
}

if (!$config) {
    $config = Bootstrap::init();
}
$pdo = DB::connect($config);
$settingsService = new WorkspaceSettingsService($pdo);
$appSettings = new AppSettingsService($pdo);
$dispatcher = new NotificationDispatcher($config);

$stmt = $pdo->query('SELECT id, name, ai_credit_balance FROM workspaces');
$workspaces = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];

$processed = 0;
foreach ($workspaces as $workspace) {
    $workspaceId = (int)$workspace['id'];
    $settings = $settingsService->getSettings($workspaceId);
    $reports = $settings['reports'] ?? [];
    $frequency = (string)($reports['digest_frequency'] ?? 'off');
    if ($frequency === 'off') {
        continue;
    }

    if (!shouldSend($frequency, (string)($reports['last_sent_at'] ?? ''))) {
        continue;
    }

    [$startDate, $endDate] = reportRange($frequency);
    $summary = buildSummary($pdo, $workspace, $startDate, $endDate, (array)($reports['include_metrics'] ?? []), $appSettings);
    $recipients = resolveRecipients($pdo, $workspaceId, (array)($reports['recipients'] ?? []));
    if (empty($recipients)) {
        continue;
    }

    $subject = sprintf('%s workspace digest (%s to %s)', $workspace['name'], $startDate, $endDate);
    $body = buildEmailBody($workspace['name'], $startDate, $endDate, $summary);
    $sentAll = true;
    foreach ($recipients as $email) {
        $sent = $dispatcher->sendEmail($email, $subject, $body);
        $sentAll = $sentAll && $sent;
    }

    if ($sentAll) {
        $reports['last_sent_at'] = date('Y-m-d H:i:s');
        $settings['reports'] = $reports;
        $settingsService->saveSettings($workspaceId, $settings);
        $processed++;
    }
}

echo 'Workspace digests sent: ' . $processed . PHP_EOL;

function shouldSend(string $frequency, string $lastSentAt): bool
{
    $today = new DateTimeImmutable('today');
    $lastSent = $lastSentAt !== '' ? new DateTimeImmutable($lastSentAt) : null;

    if ($frequency === 'weekly') {
        if ((int)$today->format('N') !== 1) {
            return false;
        }
        if ($lastSent && $lastSent->diff($today)->days < 7) {
            return false;
        }
        return true;
    }

    if ($frequency === 'monthly') {
        if ((int)$today->format('j') !== 1) {
            return false;
        }
        if ($lastSent && $lastSent->format('Y-m') === $today->format('Y-m')) {
            return false;
        }
        return true;
    }

    return false;
}

function reportRange(string $frequency): array
{
    $today = new DateTimeImmutable('today');
    if ($frequency === 'monthly') {
        $start = $today->modify('first day of last month');
        $end = $today->modify('last day of last month');
        return [$start->format('Y-m-d'), $end->format('Y-m-d')];
    }

    $end = $today;
    $start = $end->modify('-6 days');
    return [$start->format('Y-m-d'), $end->format('Y-m-d')];
}

function resolveRecipients(PDO $pdo, int $workspaceId, array $recipients): array
{
    $emails = [];
    foreach ($recipients as $email) {
        $email = trim((string)$email);
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emails[] = strtolower($email);
        }
    }

    if (!empty($emails)) {
        return array_values(array_unique($emails));
    }

    $stmt = $pdo->prepare(
        'SELECT u.email
         FROM workspace_memberships wm
         JOIN users u ON u.id = wm.user_id
         WHERE wm.workspace_id = ? AND wm.workspace_role = ? AND u.status = ?'
    );
    $stmt->execute([$workspaceId, 'Workspace Owner', 'active']);
    $ownerEmails = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $ownerEmails = array_filter(array_map('strtolower', $ownerEmails));
    return array_values(array_unique($ownerEmails));
}

function buildSummary(PDO $pdo, array $workspace, string $start, string $end, array $metrics, AppSettingsService $appSettings): array
{
    $summary = [
        'credit_usage_summary' => null,
        'depletion_forecast' => null,
        'top_categories' => null,
        'cost_breakdown' => null,
    ];

    $include = array_fill_keys($metrics, true);

    $startAt = $start . ' 00:00:00';
    $endAt = $end . ' 23:59:59';

    $stmt = $pdo->prepare(
        'SELECT COALESCE(SUM(-credits), 0) AS credits
         FROM workspace_credit_ledger
         WHERE workspace_id = ? AND change_type = "consume" AND created_at >= ? AND created_at <= ?'
    );
    $stmt->execute([(int)$workspace['id'], $startAt, $endAt]);
    $totalConsumed = (int)$stmt->fetchColumn();

    if (!empty($include['credit_usage_summary'])) {
        $summary['credit_usage_summary'] = [
            'total_consumed' => $totalConsumed,
            'current_balance' => (int)($workspace['ai_credit_balance'] ?? 0),
        ];
    }

    if (!empty($include['depletion_forecast'])) {
        $days = max(1, (new DateTimeImmutable($endAt))->diff(new DateTimeImmutable($startAt))->days + 1);
        $burnRate = $days > 0 ? $totalConsumed / $days : 0;
        $balance = (int)($workspace['ai_credit_balance'] ?? 0);
        $daysRemaining = $burnRate > 0 ? (int)floor($balance / $burnRate) : null;
        $depletionDate = $daysRemaining !== null ? (new DateTimeImmutable('today'))->modify('+' . $daysRemaining . ' days')->format('Y-m-d') : null;
        $summary['depletion_forecast'] = [
            'burn_rate' => $burnRate,
            'days_remaining' => $daysRemaining,
            'depletion_date' => $depletionDate,
        ];
    }

    if (!empty($include['top_categories'])) {
        $stmt = $pdo->prepare(
            'SELECT COALESCE(usage_type, "unknown") AS usage_type, SUM(-credits) AS credits
             FROM workspace_credit_ledger
             WHERE workspace_id = ? AND change_type = "consume" AND created_at >= ? AND created_at <= ?
             GROUP BY usage_type
             ORDER BY credits DESC
             LIMIT 5'
        );
        $stmt->execute([(int)$workspace['id'], $startAt, $endAt]);
        $summary['top_categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    if (!empty($include['cost_breakdown'])) {
        $costPerCredit = (float)($appSettings->get('billing.cost_per_credit', '0') ?? 0);
        $costTotal = $totalConsumed * $costPerCredit;
        $summary['cost_breakdown'] = [
            'cost_per_credit' => $costPerCredit,
            'total_cost' => $costTotal,
        ];
    }

    return $summary;
}

function buildEmailBody(string $workspaceName, string $start, string $end, array $summary): string
{
    $lines = [];
    $lines[] = $workspaceName . ' workspace digest';
    $lines[] = 'Period: ' . $start . ' to ' . $end;
    $lines[] = '';

    if (!empty($summary['credit_usage_summary'])) {
        $lines[] = 'Credit usage summary';
        $lines[] = '- Total consumed: ' . $summary['credit_usage_summary']['total_consumed'];
        $lines[] = '- Current balance: ' . $summary['credit_usage_summary']['current_balance'];
        $lines[] = '';
    }

    if (!empty($summary['depletion_forecast'])) {
        $lines[] = 'Depletion forecast';
        $burn = $summary['depletion_forecast']['burn_rate'] ?? 0;
        $lines[] = '- Avg daily burn: ' . number_format((float)$burn, 2);
        $days = $summary['depletion_forecast']['days_remaining'];
        $lines[] = '- Days remaining: ' . ($days !== null ? (string)$days : 'N/A');
        $date = $summary['depletion_forecast']['depletion_date'] ?? null;
        $lines[] = '- Estimated depletion date: ' . ($date ?: 'N/A');
        $lines[] = '';
    }

    if (!empty($summary['top_categories'])) {
        $lines[] = 'Top usage categories';
        foreach ($summary['top_categories'] as $row) {
            $lines[] = '- ' . ($row['usage_type'] ?? 'unknown') . ': ' . ($row['credits'] ?? 0);
        }
        $lines[] = '';
    }

    if (!empty($summary['cost_breakdown'])) {
        $lines[] = 'Cost breakdown';
        $lines[] = '- Cost per credit: $' . number_format((float)($summary['cost_breakdown']['cost_per_credit'] ?? 0), 4);
        $lines[] = '- Total cost: $' . number_format((float)($summary['cost_breakdown']['total_cost'] ?? 0), 2);
        $lines[] = '';
    }

    $lines[] = 'You can manage report preferences in Settings → Reports.';

    return implode("\n", $lines);
}
