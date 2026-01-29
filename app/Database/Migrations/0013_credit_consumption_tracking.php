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

    if (!$tableExists('workspace_credit_ledger')) {
        return;
    }

    if (!$columnExists('workspace_credit_ledger', 'usage_type')) {
        $addColumn('workspace_credit_ledger', $driver === 'sqlite'
            ? 'usage_type TEXT NULL'
            : 'usage_type VARCHAR(50) NULL');
    }

    if (!$columnExists('workspace_credit_ledger', 'metadata')) {
        $addColumn('workspace_credit_ledger', $driver === 'sqlite'
            ? 'metadata TEXT NULL'
            : 'metadata TEXT NULL');
    }

    try {
        if ($driver === 'sqlite') {
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_wcl_workspace_created ON workspace_credit_ledger (workspace_id, created_at)');
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_wcl_workspace_usage_created ON workspace_credit_ledger (workspace_id, usage_type, created_at)');
        } else {
            $pdo->exec('CREATE INDEX idx_wcl_workspace_created ON workspace_credit_ledger (workspace_id, created_at)');
            $pdo->exec('CREATE INDEX idx_wcl_workspace_usage_created ON workspace_credit_ledger (workspace_id, usage_type, created_at)');
        }
    } catch (Throwable $e) {
        // Ignore if indexes already exist.
    }
};
