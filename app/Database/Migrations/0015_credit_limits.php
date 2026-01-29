<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    $tableExists = static function (string $table) use ($pdo, $driver): bool {
        if ($driver === 'sqlite') {
            $stmt = $pdo->prepare('SELECT name FROM sqlite_master WHERE type = "table" AND name = ?');
            $stmt->execute([$table]);
            return (bool)$stmt->fetchColumn();
        }
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
        $stmt->execute([$table]);
        return (int)$stmt->fetchColumn() > 0;
    };

    if ($tableExists('workspace_credit_limits')) {
        return;
    }

    if ($driver === 'sqlite') {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS workspace_credit_limits (
                workspace_id INTEGER PRIMARY KEY,
                daily_limit INTEGER NOT NULL DEFAULT 0,
                monthly_limit INTEGER NOT NULL DEFAULT 0,
                alert_threshold_percent INTEGER NOT NULL DEFAULT 80,
                last_alert_sent_at TEXT NULL,
                FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
            );'
        );
    } else {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS workspace_credit_limits (
                workspace_id BIGINT UNSIGNED PRIMARY KEY,
                daily_limit INT NOT NULL DEFAULT 0,
                monthly_limit INT NOT NULL DEFAULT 0,
                alert_threshold_percent INT NOT NULL DEFAULT 80,
                last_alert_sent_at DATETIME NULL,
                FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
        );
    }
};
