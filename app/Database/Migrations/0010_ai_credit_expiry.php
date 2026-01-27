<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

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
            // Ignore if column already exists.
        }
    };

    $columns = [
        'remaining_credits' => $driver === 'sqlite' ? 'remaining_credits INTEGER NOT NULL DEFAULT 0' : 'remaining_credits INT NOT NULL DEFAULT 0',
        'expires_at' => $driver === 'sqlite' ? 'expires_at TEXT NULL' : 'expires_at DATETIME NULL',
        'expired_at' => $driver === 'sqlite' ? 'expired_at TEXT NULL' : 'expired_at DATETIME NULL',
    ];

    foreach ($columns as $column => $definition) {
        if (!$columnExists('ai_credit_purchases', $column)) {
            $addColumn('ai_credit_purchases', $definition);
        }
    }
};
