<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
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
        return;
    }

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
};
