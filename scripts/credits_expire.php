<?php

declare(strict_types=1);

require __DIR__ . '/../app/Database/DB.php';
require __DIR__ . '/../app/Billing/BillingService.php';

$config = require __DIR__ . '/../config.php';
$pdo = DB::connect($config);

$billing = new BillingService($pdo, $config);
$expired = $billing->expireTopupCredits();

echo 'Expired top-up credits: ' . $expired . PHP_EOL;
