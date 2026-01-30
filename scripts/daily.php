<?php

declare(strict_types=1);

require __DIR__ . '/../app/Database/DB.php';
require __DIR__ . '/../app/Mail/Mailer.php';
require __DIR__ . '/../app/Notifications/NotificationDispatcher.php';
require __DIR__ . '/../app/Notifications/NotificationService.php';
require __DIR__ . '/../app/Billing/BillingService.php';
require __DIR__ . '/../app/AI/RateLimiter.php';
require __DIR__ . '/../app/Settings/AppSettingsService.php';
require __DIR__ . '/../app/Settings/WorkspaceSettingsService.php';

$config = require __DIR__ . '/../config.php';
$GLOBALS['config'] = $config;
$pdo = DB::connect($config);

$notifications = new NotificationService($pdo, $config);
$sent = $notifications->dispatchDueBatches();

$billing = new BillingService($pdo, $config);
$expired = $billing->expireTopupCredits();

$limitOutput = '';
ob_start();
require __DIR__ . '/check_credit_limits.php';
$limitOutput = trim((string)ob_get_clean());

$reportOutput = '';
ob_start();
require __DIR__ . '/workspace_digest_reports.php';
$reportOutput = trim((string)ob_get_clean());

$velocityOutput = '';
ob_start();
require __DIR__ . '/check_spending_velocity.php';
$velocityOutput = trim((string)ob_get_clean());

$workspaceVelocityOutput = '';
ob_start();
require __DIR__ . '/check_workspace_velocity.php';
$workspaceVelocityOutput = trim((string)ob_get_clean());

echo 'Daily tasks: digests=' . $sent . ' credits_expired=' . $expired;
if ($limitOutput !== '') {
    echo ' ' . $limitOutput;
}
if ($reportOutput !== '') {
    echo ' ' . $reportOutput;
}
if ($velocityOutput !== '') {
    echo ' ' . $velocityOutput;
}
if ($workspaceVelocityOutput !== '') {
    echo ' ' . $workspaceVelocityOutput;
}
echo PHP_EOL;
