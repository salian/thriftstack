<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS workspaces (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                created_by INTEGER NOT NULL,
                created_at TEXT NOT NULL,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
            );'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS workspace_memberships (
                user_id INTEGER NOT NULL,
                workspace_id INTEGER NOT NULL,
                role TEXT NOT NULL,
                created_at TEXT NOT NULL,
                PRIMARY KEY (user_id, workspace_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
            );'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS workspace_invites (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                workspace_id INTEGER NOT NULL,
                email TEXT NOT NULL,
                role TEXT NOT NULL,
                token_hash TEXT NOT NULL UNIQUE,
                expires_at TEXT NOT NULL,
                created_at TEXT NOT NULL,
                accepted_at TEXT NULL,
                FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
            );'
        );

        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_workspace_memberships_role ON workspace_memberships (role);');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_workspace_invites_email ON workspace_invites (email);');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_workspace_invites_expires ON workspace_invites (expires_at);');
        return;
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS workspaces (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(190) NOT NULL,
            created_by BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_workspace_created (created_at),
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS workspace_memberships (
            user_id BIGINT UNSIGNED NOT NULL,
            workspace_id BIGINT UNSIGNED NOT NULL,
            role VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (user_id, workspace_id),
            INDEX idx_workspace_members_role (role),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS workspace_invites (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            workspace_id BIGINT UNSIGNED NOT NULL,
            email VARCHAR(190) NOT NULL,
            role VARCHAR(20) NOT NULL,
            token_hash VARCHAR(128) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            accepted_at DATETIME NULL,
            INDEX idx_workspace_invites_email (email),
            INDEX idx_workspace_invites_expires (expires_at),
            FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
    );
};
