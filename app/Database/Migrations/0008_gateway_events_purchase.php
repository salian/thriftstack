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
            // Ignore if already exists.
        }
    };

    if ($columnExists('payment_gateway_events', 'purchase_id')) {
        return;
    }

    if ($driver === 'sqlite') {
        $addColumn('payment_gateway_events', 'purchase_id INTEGER NULL');
        try {
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_gateway_events_purchase ON payment_gateway_events (purchase_id);');
        } catch (Throwable $e) {
            // Ignore index creation errors.
        }
    } else {
        $addColumn('payment_gateway_events', 'purchase_id BIGINT UNSIGNED NULL');
        try {
            $pdo->exec('CREATE INDEX idx_gateway_events_purchase ON payment_gateway_events (purchase_id);');
        } catch (Throwable $e) {
            // Ignore index creation errors.
        }
    }
};
