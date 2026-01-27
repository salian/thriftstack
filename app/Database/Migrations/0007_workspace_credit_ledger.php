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

    if ($tableExists('workspaces') && !$columnExists('workspaces', 'ai_credit_balance')) {
        $addColumn('workspaces', $driver === 'sqlite'
            ? 'ai_credit_balance INTEGER NOT NULL DEFAULT 0'
            : 'ai_credit_balance INT NOT NULL DEFAULT 0');
    }

    if (!$tableExists('workspace_credit_ledger')) {
        if ($driver === 'sqlite') {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS workspace_credit_ledger (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    workspace_id INTEGER NOT NULL,
                    change_type TEXT NOT NULL,
                    credits INTEGER NOT NULL,
                    balance_after INTEGER NOT NULL,
                    source_type TEXT NOT NULL,
                    source_id INTEGER NULL,
                    description TEXT NULL,
                    created_at TEXT NOT NULL,
                    FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
                );'
            );
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_workspace_credit_ledger_workspace ON workspace_credit_ledger (workspace_id);');
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_workspace_credit_ledger_created ON workspace_credit_ledger (created_at);');
        } else {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS workspace_credit_ledger (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    workspace_id BIGINT UNSIGNED NOT NULL,
                    change_type VARCHAR(30) NOT NULL,
                    credits INT NOT NULL,
                    balance_after INT NOT NULL,
                    source_type VARCHAR(30) NOT NULL,
                    source_id BIGINT UNSIGNED NULL,
                    description VARCHAR(255) NULL,
                    created_at DATETIME NOT NULL,
                    INDEX idx_workspace_credit_ledger_workspace (workspace_id),
                    INDEX idx_workspace_credit_ledger_created (created_at),
                    FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
            );
        }
    }
};
