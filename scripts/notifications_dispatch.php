<?php

declare(strict_types=1);

require __DIR__ . '/../app/Database/DB.php';
require __DIR__ . '/../app/Mail/Mailer.php';
require __DIR__ . '/../app/Notifications/NotificationDispatcher.php';
require __DIR__ . '/../app/Notifications/NotificationService.php';

$config = require __DIR__ . '/../config.php';
$pdo = DB::connect($config);

$service = new NotificationService($pdo, $config);
$count = $service->dispatchDueBatches();

echo "Batches dispatched: {$count}\n";
