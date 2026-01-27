<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    $columnExists = static function (string $table, string $column) use ($pdo, $driver): bool {
        if ($driver === 'sqlite') {
            $stmt = $pdo->prepare('PRAGMA table_info(' . $table . ')');
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($columns as $info) {
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

    $addColumn = static function (string $table, string $definition) use ($pdo): void {
        try {
            $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $definition);
        } catch (Throwable $e) {
            // Ignore if column already exists or cannot be altered.
        }
    };

    if ($tableExists('users')) {
        if (!$columnExists('users', 'is_system_admin')) {
            $addColumn('users', $driver === 'sqlite' ? 'is_system_admin INTEGER NOT NULL DEFAULT 0' : 'is_system_admin TINYINT(1) NOT NULL DEFAULT 0');
        }
        if (!$columnExists('users', 'is_system_staff')) {
            $addColumn('users', $driver === 'sqlite' ? 'is_system_staff INTEGER NOT NULL DEFAULT 0' : 'is_system_staff TINYINT(1) NOT NULL DEFAULT 0');
        }
    }

    foreach (['user_app_roles', 'app_role_permissions', 'app_roles'] as $table) {
        if (!$tableExists($table)) {
            continue;
        }
        try {
            $pdo->exec('DROP TABLE ' . $table);
        } catch (Throwable $e) {
            // Ignore drop failures.
        }
    }
};
