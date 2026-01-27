<?php

declare(strict_types=1);

require __DIR__ . '/../app/Database/DB.php';
require __DIR__ . '/../app/Mail/Mailer.php';
require __DIR__ . '/../app/Notifications/NotificationDispatcher.php';
require __DIR__ . '/../app/Notifications/NotificationService.php';
require __DIR__ . '/../app/Billing/BillingService.php';

$config = require __DIR__ . '/../config.php';
$pdo = DB::connect($config);

$notifications = new NotificationService($pdo, $config);
$sent = $notifications->dispatchDueBatches();

$billing = new BillingService($pdo, $config);
$expired = $billing->expireTopupCredits();

echo 'Daily tasks: digests=' . $sent . ' credits_expired=' . $expired . PHP_EOL;
