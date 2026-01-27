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

    $columnExists = static function (string $table, string $column) use ($pdo, $driver): bool {
        if ($driver === 'sqlite') {
            $stmt = $pdo->prepare('PRAGMA table_info(' . $table . ')');
            $stmt->execute();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $info) {
                if (($info['name'] ?? '') === $column) {
                    return true;
                }
            }
            return false;
        }
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    };

    $addColumn = static function (string $table, string $definition) use ($pdo): void {
        try {
            $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $definition);
        } catch (Throwable $e) {
            // Ignore if column already exists or cannot be altered.
        }
    };

    if ($tableExists('plans')) {
        if (!$columnExists('plans', 'plan_type')) {
            $addColumn('plans', $driver === 'sqlite'
                ? "plan_type TEXT NOT NULL DEFAULT 'subscription'"
                : "plan_type ENUM('subscription','topup') NOT NULL DEFAULT 'subscription'");
        }
        if (!$columnExists('plans', 'ai_credits')) {
            $addColumn('plans', $driver === 'sqlite'
                ? 'ai_credits INTEGER NOT NULL DEFAULT 0'
                : 'ai_credits INT NOT NULL DEFAULT 0');
        }
    }

    if (!$tableExists('ai_credit_purchases')) {
        if ($driver === 'sqlite') {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS ai_credit_purchases (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    workspace_id INTEGER NOT NULL,
                    plan_id INTEGER NOT NULL,
                    credits INTEGER NOT NULL,
                    amount_cents INTEGER NOT NULL,
                    currency TEXT NOT NULL DEFAULT \'USD\',
                    provider TEXT NULL,
                    provider_checkout_id TEXT NULL,
                    provider_customer_id TEXT NULL,
                    provider_status TEXT NULL,
                    status TEXT NOT NULL,
                    created_at TEXT NOT NULL,
                    applied_at TEXT NULL,
                    FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
                    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE RESTRICT
                );'
            );
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_ai_credit_purchases_workspace ON ai_credit_purchases (workspace_id);');
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_ai_credit_purchases_status ON ai_credit_purchases (status);');
        } else {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS ai_credit_purchases (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    workspace_id BIGINT UNSIGNED NOT NULL,
                    plan_id BIGINT UNSIGNED NOT NULL,
                    credits INT NOT NULL,
                    amount_cents INT NOT NULL,
                    currency VARCHAR(10) NOT NULL DEFAULT "USD",
                    provider VARCHAR(30) NULL,
                    provider_checkout_id VARCHAR(120) NULL,
                    provider_customer_id VARCHAR(120) NULL,
                    provider_status VARCHAR(60) NULL,
                    status VARCHAR(30) NOT NULL,
                    created_at DATETIME NOT NULL,
                    applied_at DATETIME NULL,
                    INDEX idx_ai_credit_purchases_workspace (workspace_id),
                    INDEX idx_ai_credit_purchases_status (status),
                    FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
                    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE RESTRICT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
            );
        }
    }
};
