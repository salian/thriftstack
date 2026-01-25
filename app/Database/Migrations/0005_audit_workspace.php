<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        $pdo->exec('ALTER TABLE audit_logs ADD COLUMN workspace_id INTEGER NULL');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_audit_workspace ON audit_logs (workspace_id)');
        return;
    }

    $pdo->exec('ALTER TABLE audit_logs ADD COLUMN workspace_id BIGINT UNSIGNED NULL AFTER user_id');
    $pdo->exec('CREATE INDEX idx_audit_workspace ON audit_logs (workspace_id)');
};
