<?php

declare(strict_types=1);

require __DIR__ . '/../app/Database/DB.php';
require __DIR__ . '/../app/Database/Seeder.php';

$config = require __DIR__ . '/../config.php';
$pdo = DB::connect($config);

$seeder = new Seeder($pdo);
$seeder->run();

echo "Seed data inserted.\n";
