<?php

declare(strict_types=1);

require __DIR__ . '/../app/Database/DB.php';
require __DIR__ . '/../app/Mail/Mailer.php';
require __DIR__ . '/../app/Notifications/NotificationDispatcher.php';
require __DIR__ . '/../app/Notifications/NotificationService.php';
require __DIR__ . '/../app/Billing/BillingService.php';
require __DIR__ . '/../app/AI/RateLimiter.php';

$config = require __DIR__ . '/../config.php';
$pdo = DB::connect($config);

$notifications = new NotificationService($pdo, $config);
$sent = $notifications->dispatchDueBatches();

$billing = new BillingService($pdo, $config);
$expired = $billing->expireTopupCredits();

$limitOutput = '';
ob_start();
require __DIR__ . '/check_credit_limits.php';
$limitOutput = trim((string)ob_get_clean());

echo 'Daily tasks: digests=' . $sent . ' credits_expired=' . $expired;
if ($limitOutput !== '') {
    echo ' ' . $limitOutput;
}
echo PHP_EOL;
