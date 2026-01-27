<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        return;
    }

    $alter = static function (string $table, string $column) use ($pdo): void {
        try {
            $pdo->exec(
                "ALTER TABLE {$table} MODIFY COLUMN {$column} ENUM('Workspace Owner','Workspace Admin','Workspace Member') NOT NULL"
            );
        } catch (Throwable $e) {
            // Ignore if the column already uses ENUM or cannot be altered.
        }
    };

    foreach ([
        ['workspace_memberships', 'workspace_role'],
        ['workspace_invites', 'workspace_role'],
        ['workspace_role_permissions', 'workspace_role'],
    ] as [$table, $column]) {
        $alter($table, $column);
    }
};
