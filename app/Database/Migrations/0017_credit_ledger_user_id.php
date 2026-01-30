<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'sqlite') {
        $pdo->exec('ALTER TABLE workspace_credit_ledger ADD COLUMN user_id INTEGER NULL');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_wcl_workspace_user_created ON workspace_credit_ledger (workspace_id, user_id, created_at)');
        return;
    }

    $pdo->exec('ALTER TABLE workspace_credit_ledger ADD COLUMN user_id BIGINT UNSIGNED NULL');
    $pdo->exec('CREATE INDEX idx_wcl_workspace_user_created ON workspace_credit_ledger (workspace_id, user_id, created_at)');
};
