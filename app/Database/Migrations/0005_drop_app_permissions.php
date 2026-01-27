<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    try {
        $pdo->exec('DROP TABLE IF EXISTS app_permissions');
    } catch (Throwable $e) {
        // Ignore if table cannot be dropped.
    }
};
