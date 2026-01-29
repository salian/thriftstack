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

    if ($tableExists('api_tokens')) {
        return;
    }

    if ($driver === 'sqlite') {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS api_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                workspace_id INTEGER NOT NULL,
                token_hash TEXT NOT NULL,
                name TEXT NOT NULL,
                scopes TEXT NULL,
                last_used_at TEXT NULL,
                expires_at TEXT NULL,
                created_at TEXT NOT NULL,
                FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
            );'
        );
        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_api_tokens_hash ON api_tokens (token_hash);');
    } else {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS api_tokens (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                workspace_id BIGINT UNSIGNED NOT NULL,
                token_hash VARCHAR(128) NOT NULL,
                name VARCHAR(120) NOT NULL,
                scopes TEXT NULL,
                last_used_at DATETIME NULL,
                expires_at DATETIME NULL,
                created_at DATETIME NOT NULL,
                UNIQUE INDEX idx_api_tokens_hash (token_hash),
                FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
        );
    }
};
