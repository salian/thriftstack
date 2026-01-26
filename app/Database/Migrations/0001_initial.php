<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                email_verified_at TEXT NULL,
                status TEXT NOT NULL DEFAULT "active",
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS app_roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                description TEXT NULL,
                created_at TEXT NOT NULL
            );'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS app_permissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                description TEXT NULL,
                created_at TEXT NOT NULL
            );'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS app_role_permissions (
                app_role_id INTEGER NOT NULL,
                app_permission_id INTEGER NOT NULL,
                PRIMARY KEY (app_role_id, app_permission_id),
                FOREIGN KEY (app_role_id) REFERENCES app_roles(id) ON DELETE CASCADE,
                FOREIGN KEY (app_permission_id) REFERENCES app_permissions(id) ON DELETE CASCADE
            );'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS user_app_roles (
                user_id INTEGER NOT NULL,
                app_role_id INTEGER NOT NULL,
                PRIMARY KEY (user_id, app_role_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (app_role_id) REFERENCES app_roles(id) ON DELETE CASCADE
            );'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS audit_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NULL,
                workspace_id INTEGER NULL,
                action TEXT NOT NULL,
                metadata TEXT NULL,
                created_at TEXT NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            );'
        );

        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_audit_user ON audit_logs (user_id);');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_audit_workspace ON audit_logs (workspace_id);');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_audit_created ON audit_logs (created_at);');

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS uploads (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NULL,
                type TEXT NOT NULL,
                original_name TEXT NOT NULL,
                path TEXT NOT NULL,
                mime_type TEXT NOT NULL,
                size INTEGER NOT NULL,
                created_at TEXT NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            );'
        );

        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_uploads_user ON uploads (user_id);');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_uploads_type ON uploads (type);');

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS email_verifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                token_hash TEXT NOT NULL UNIQUE,
                expires_at TEXT NOT NULL,
                created_at TEXT NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );'
        );

        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_email_verifications_user ON email_verifications (user_id);');

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS password_resets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                token_hash TEXT NOT NULL UNIQUE,
                expires_at TEXT NOT NULL,
                used_at TEXT NULL,
                created_at TEXT NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );'
        );

        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_password_resets_user ON password_resets (user_id);');

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
                workspace_role TEXT NOT NULL,
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
                workspace_role TEXT NOT NULL,
                token_hash TEXT NOT NULL UNIQUE,
                expires_at TEXT NOT NULL,
                created_at TEXT NOT NULL,
                accepted_at TEXT NULL,
                FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
            );'
        );

        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_workspace_memberships_role ON workspace_memberships (workspace_role);');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_workspace_invites_email ON workspace_invites (email);');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_workspace_invites_expires ON workspace_invites (expires_at);');

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS workspace_permissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                description TEXT NULL,
                created_at TEXT NOT NULL
            );'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS workspace_role_permissions (
                workspace_role TEXT NOT NULL,
                workspace_permission_id INTEGER NOT NULL,
                PRIMARY KEY (workspace_role, workspace_permission_id),
                FOREIGN KEY (workspace_permission_id) REFERENCES workspace_permissions(id) ON DELETE CASCADE
            );'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS user_settings (
                user_id INTEGER PRIMARY KEY,
                settings_json TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS notifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                channel TEXT NOT NULL,
                subject TEXT NOT NULL,
                body TEXT NOT NULL,
                status TEXT NOT NULL,
                is_read INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL,
                sent_at TEXT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );'
        );

        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_notifications_user ON notifications (user_id);');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_notifications_status ON notifications (status);');

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS notification_batches (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                scheduled_for TEXT NOT NULL,
                status TEXT NOT NULL,
                created_at TEXT NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );'
        );

        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_notification_batches_user ON notification_batches (user_id);');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_notification_batches_schedule ON notification_batches (scheduled_for);');

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS plans (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                code TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                price_cents INTEGER NOT NULL DEFAULT 0,
                interval TEXT NOT NULL,
                is_active INTEGER NOT NULL DEFAULT 1
            );'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS subscriptions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                workspace_id INTEGER NOT NULL,
                plan_id INTEGER NOT NULL,
                status TEXT NOT NULL,
                trial_ends_at TEXT NULL,
                current_period_end TEXT NULL,
                created_at TEXT NOT NULL,
                FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
                FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE RESTRICT
            );'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS invoices (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                subscription_id INTEGER NOT NULL,
                amount_cents INTEGER NOT NULL,
                status TEXT NOT NULL,
                provider TEXT NOT NULL,
                external_id TEXT NOT NULL,
                created_at TEXT NOT NULL,
                FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
            );'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS webhook_events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                provider TEXT NOT NULL,
                event_type TEXT NOT NULL,
                payload TEXT NOT NULL,
                received_at TEXT NOT NULL
            );'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS payment_gateway_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                provider TEXT NOT NULL,
                setting_key TEXT NOT NULL,
                setting_value TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );'
        );

        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_gateway_setting ON payment_gateway_settings (provider, setting_key);');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_gateway_provider ON payment_gateway_settings (provider);');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_plans_active ON plans (is_active);');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_subscriptions_workspace ON subscriptions (workspace_id);');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_subscriptions_status ON subscriptions (status);');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_invoices_subscription ON invoices (subscription_id);');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_webhooks_provider ON webhook_events (provider);');
        return;
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            email_verified_at DATETIME NULL,
            status VARCHAR(20) NOT NULL DEFAULT "active",
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS app_roles (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            description VARCHAR(255) NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS app_permissions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            description VARCHAR(255) NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS app_role_permissions (
            app_role_id BIGINT UNSIGNED NOT NULL,
            app_permission_id BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (app_role_id, app_permission_id),
            FOREIGN KEY (app_role_id) REFERENCES app_roles(id) ON DELETE CASCADE,
            FOREIGN KEY (app_permission_id) REFERENCES app_permissions(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS user_app_roles (
            user_id BIGINT UNSIGNED NOT NULL,
            app_role_id BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (user_id, app_role_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (app_role_id) REFERENCES app_roles(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS audit_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NULL,
            workspace_id BIGINT UNSIGNED NULL,
            action VARCHAR(120) NOT NULL,
            metadata TEXT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_audit_user (user_id),
            INDEX idx_audit_workspace (workspace_id),
            INDEX idx_audit_created (created_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS uploads (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NULL,
            type VARCHAR(50) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            path VARCHAR(255) NOT NULL,
            mime_type VARCHAR(120) NOT NULL,
            size BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_uploads_user (user_id),
            INDEX idx_uploads_type (type),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS email_verifications (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            token_hash VARCHAR(128) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_email_verifications_user (user_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS password_resets (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            token_hash VARCHAR(128) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_password_resets_user (user_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
    );

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
            workspace_role VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (user_id, workspace_id),
            INDEX idx_workspace_members_role (workspace_role),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS workspace_invites (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            workspace_id BIGINT UNSIGNED NOT NULL,
            email VARCHAR(190) NOT NULL,
            workspace_role VARCHAR(20) NOT NULL,
            token_hash VARCHAR(128) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            accepted_at DATETIME NULL,
            INDEX idx_workspace_invites_email (email),
            INDEX idx_workspace_invites_expires (expires_at),
            FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS workspace_permissions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            description VARCHAR(255) NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS workspace_role_permissions (
            workspace_role VARCHAR(20) NOT NULL,
            workspace_permission_id BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (workspace_role, workspace_permission_id),
            FOREIGN KEY (workspace_permission_id) REFERENCES workspace_permissions(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS user_settings (
            user_id BIGINT UNSIGNED PRIMARY KEY,
            settings_json TEXT NOT NULL,
            updated_at DATETIME NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS notifications (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            channel VARCHAR(30) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            body TEXT NOT NULL,
            status VARCHAR(30) NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            sent_at DATETIME NULL,
            INDEX idx_notifications_user (user_id),
            INDEX idx_notifications_status (status),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS notification_batches (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            scheduled_for DATETIME NOT NULL,
            status VARCHAR(30) NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_notification_batches_user (user_id),
            INDEX idx_notification_batches_schedule (scheduled_for),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS plans (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(60) NOT NULL UNIQUE,
            name VARCHAR(120) NOT NULL,
            price_cents INT NOT NULL DEFAULT 0,
            interval VARCHAR(20) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            INDEX idx_plans_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS subscriptions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            workspace_id BIGINT UNSIGNED NOT NULL,
            plan_id BIGINT UNSIGNED NOT NULL,
            status VARCHAR(30) NOT NULL,
            trial_ends_at DATETIME NULL,
            current_period_end DATETIME NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_subscriptions_workspace (workspace_id),
            INDEX idx_subscriptions_status (status),
            FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
            FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS invoices (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            subscription_id BIGINT UNSIGNED NOT NULL,
            amount_cents INT NOT NULL,
            status VARCHAR(30) NOT NULL,
            provider VARCHAR(30) NOT NULL,
            external_id VARCHAR(120) NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_invoices_subscription (subscription_id),
            FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS webhook_events (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            provider VARCHAR(30) NOT NULL,
            event_type VARCHAR(120) NOT NULL,
            payload LONGTEXT NOT NULL,
            received_at DATETIME NOT NULL,
            INDEX idx_webhooks_provider (provider)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS payment_gateway_settings (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            provider VARCHAR(40) NOT NULL,
            setting_key VARCHAR(120) NOT NULL,
            setting_value TEXT NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY idx_gateway_setting (provider, setting_key),
            INDEX idx_gateway_provider (provider)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
    );
};
