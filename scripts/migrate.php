<?php

declare(strict_types=1);

require __DIR__ . '/../app/Database/DB.php';
require __DIR__ . '/../app/Database/Migrator.php';

$config = require __DIR__ . '/../config.php';
$pdo = DB::connect($config);

$migrator = new Migrator($pdo, __DIR__ . '/../app/Database/Migrations');
$count = $migrator->run();

echo "Migrations applied: {$count}\n";
