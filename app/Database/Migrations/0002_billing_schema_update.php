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

    $addColumn = static function (string $table, string $definition) use ($pdo, $driver): void {
        try {
            $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $definition);
        } catch (Throwable $e) {
            // Ignore if column already exists or cannot be altered.
        }
    };

    if ($tableExists('plans')) {
        if (!$columnExists('plans', 'currency')) {
            $addColumn('plans', $driver === 'sqlite' ? "currency TEXT NOT NULL DEFAULT 'USD'" : "currency VARCHAR(10) NOT NULL DEFAULT 'USD'");
        }
        if (!$columnExists('plans', 'duration')) {
            $addColumn('plans', $driver === 'sqlite' ? "duration TEXT NOT NULL DEFAULT 'monthly'" : "duration VARCHAR(20) NOT NULL DEFAULT 'monthly'");
        }
        if (!$columnExists('plans', 'is_grandfathered')) {
            $addColumn('plans', $driver === 'sqlite' ? 'is_grandfathered INTEGER NOT NULL DEFAULT 0' : 'is_grandfathered TINYINT(1) NOT NULL DEFAULT 0');
        }
        if (!$columnExists('plans', 'disabled_at')) {
            $addColumn('plans', $driver === 'sqlite' ? 'disabled_at TEXT NULL' : 'disabled_at DATETIME NULL');
        }
        foreach ([
            'stripe_price_id' => $driver === 'sqlite' ? 'stripe_price_id TEXT NULL' : 'stripe_price_id VARCHAR(120) NULL',
            'razorpay_plan_id' => $driver === 'sqlite' ? 'razorpay_plan_id TEXT NULL' : 'razorpay_plan_id VARCHAR(120) NULL',
            'paypal_plan_id' => $driver === 'sqlite' ? 'paypal_plan_id TEXT NULL' : 'paypal_plan_id VARCHAR(120) NULL',
            'lemonsqueezy_variant_id' => $driver === 'sqlite' ? 'lemonsqueezy_variant_id TEXT NULL' : 'lemonsqueezy_variant_id VARCHAR(120) NULL',
            'dodo_price_id' => $driver === 'sqlite' ? 'dodo_price_id TEXT NULL' : 'dodo_price_id VARCHAR(120) NULL',
            'paddle_price_id' => $driver === 'sqlite' ? 'paddle_price_id TEXT NULL' : 'paddle_price_id VARCHAR(120) NULL',
        ] as $column => $definition) {
            if (!$columnExists('plans', $column)) {
                $addColumn('plans', $definition);
            }
        }
    }

    if ($tableExists('subscriptions')) {
        foreach ([
            'provider' => $driver === 'sqlite' ? 'provider TEXT NULL' : 'provider VARCHAR(30) NULL',
            'provider_customer_id' => $driver === 'sqlite' ? 'provider_customer_id TEXT NULL' : 'provider_customer_id VARCHAR(120) NULL',
            'provider_subscription_id' => $driver === 'sqlite' ? 'provider_subscription_id TEXT NULL' : 'provider_subscription_id VARCHAR(120) NULL',
            'provider_checkout_id' => $driver === 'sqlite' ? 'provider_checkout_id TEXT NULL' : 'provider_checkout_id VARCHAR(120) NULL',
            'provider_status' => $driver === 'sqlite' ? 'provider_status TEXT NULL' : 'provider_status VARCHAR(40) NULL',
            'currency' => $driver === 'sqlite' ? "currency TEXT NOT NULL DEFAULT 'USD'" : "currency VARCHAR(10) NOT NULL DEFAULT 'USD'",
            'type' => $driver === 'sqlite' ? "type TEXT NOT NULL DEFAULT 'recurring'" : "type VARCHAR(20) NOT NULL DEFAULT 'recurring'",
            'trial_ends_at' => $driver === 'sqlite' ? 'trial_ends_at TEXT NULL' : 'trial_ends_at DATETIME NULL',
            'current_period_start' => $driver === 'sqlite' ? 'current_period_start TEXT NULL' : 'current_period_start DATETIME NULL',
            'current_period_end' => $driver === 'sqlite' ? 'current_period_end TEXT NULL' : 'current_period_end DATETIME NULL',
            'cancel_at_period_end' => $driver === 'sqlite' ? 'cancel_at_period_end INTEGER NOT NULL DEFAULT 0' : 'cancel_at_period_end TINYINT(1) NOT NULL DEFAULT 0',
            'canceled_at' => $driver === 'sqlite' ? 'canceled_at TEXT NULL' : 'canceled_at DATETIME NULL',
        ] as $column => $definition) {
            if (!$columnExists('subscriptions', $column)) {
                $addColumn('subscriptions', $definition);
            }
        }
    }

    if ($tableExists('subscription_changes')) {
        foreach ([
            'provider' => $driver === 'sqlite' ? 'provider TEXT NULL' : 'provider VARCHAR(50) NULL',
            'provider_checkout_id' => $driver === 'sqlite' ? 'provider_checkout_id TEXT NULL' : 'provider_checkout_id VARCHAR(120) NULL',
        ] as $column => $definition) {
            if (!$columnExists('subscription_changes', $column)) {
                $addColumn('subscription_changes', $definition);
            }
        }
    }

    if (!$tableExists('app_settings')) {
        if ($driver === 'sqlite') {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS app_settings (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    setting_key TEXT NOT NULL UNIQUE,
                    setting_value TEXT NOT NULL,
                    updated_at TEXT NOT NULL
                );'
            );
        } else {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS app_settings (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    setting_key VARCHAR(120) NOT NULL UNIQUE,
                    setting_value TEXT NOT NULL,
                    updated_at DATETIME NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
            );
        }
    }

    if (!$tableExists('payment_gateway_settings')) {
        if ($driver === 'sqlite') {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS payment_gateway_settings (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    provider TEXT NOT NULL,
                    setting_key TEXT NOT NULL,
                    setting_value TEXT NOT NULL,
                    updated_at TEXT NOT NULL
                );'
            );
        } else {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS payment_gateway_settings (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    provider VARCHAR(30) NOT NULL,
                    setting_key VARCHAR(120) NOT NULL,
                    setting_value TEXT NOT NULL,
                    updated_at DATETIME NOT NULL,
                    UNIQUE KEY idx_gateway_setting (provider, setting_key)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
            );
        }
    }

    if (!$tableExists('payment_gateway_events')) {
        if ($driver === 'sqlite') {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS payment_gateway_events (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    provider TEXT NOT NULL,
                    subscription_id INTEGER NULL,
                    status TEXT NOT NULL,
                    event_type TEXT NOT NULL,
                    created_at TEXT NOT NULL
                );'
            );
        } else {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS payment_gateway_events (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    provider VARCHAR(30) NOT NULL,
                    subscription_id BIGINT UNSIGNED NULL,
                    status VARCHAR(40) NOT NULL,
                    event_type VARCHAR(120) NOT NULL,
                    created_at DATETIME NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
            );
        }
    }

    if (!$tableExists('subscription_changes')) {
        if ($driver === 'sqlite') {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS subscription_changes (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    subscription_id INTEGER NOT NULL,
                    from_plan_id INTEGER NULL,
                    to_plan_id INTEGER NULL,
                    change_type TEXT NOT NULL,
                    status TEXT NOT NULL,
                    effective_at TEXT NULL,
                    provider TEXT NULL,
                    provider_checkout_id TEXT NULL,
                    created_at TEXT NOT NULL,
                    applied_at TEXT NULL
                );'
            );
        } else {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS subscription_changes (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    subscription_id BIGINT UNSIGNED NOT NULL,
                    from_plan_id BIGINT UNSIGNED NULL,
                    to_plan_id BIGINT UNSIGNED NULL,
                    change_type VARCHAR(40) NOT NULL,
                    status VARCHAR(30) NOT NULL,
                    effective_at DATETIME NULL,
                    provider VARCHAR(50) NULL,
                    provider_checkout_id VARCHAR(120) NULL,
                    created_at DATETIME NOT NULL,
                    applied_at DATETIME NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
            );
        }
    }
};
