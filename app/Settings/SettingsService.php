<?php

declare(strict_types=1);

final class SettingsService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function defaults(): array
    {
        return [
            'notify_email' => true,
            'notify_in_app' => true,
            'notify_digest' => false,
        ];
    }

    public function getSettings(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT settings_json FROM user_settings WHERE user_id = ?');
        $stmt->execute([$userId]);
        $json = $stmt->fetchColumn();

        if (!$json) {
            return $this->defaults();
        }

        $data = json_decode((string)$json, true);
        if (!is_array($data)) {
            return $this->defaults();
        }

        return array_merge($this->defaults(), $data);
    }

    public function saveSettings(int $userId, array $settings): void
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $now = date('Y-m-d H:i:s');
        $payload = json_encode($settings, JSON_UNESCAPED_SLASHES);

        if ($driver === 'sqlite') {
            $stmt = $this->pdo->prepare(
                'INSERT INTO user_settings (user_id, settings_json, updated_at)
                 VALUES (?, ?, ?)
                 ON CONFLICT(user_id) DO UPDATE SET settings_json = excluded.settings_json, updated_at = excluded.updated_at'
            );
            $stmt->execute([$userId, $payload, $now]);
            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO user_settings (user_id, settings_json, updated_at)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE settings_json = VALUES(settings_json), updated_at = VALUES(updated_at)'
        );
        $stmt->execute([$userId, $payload, $now]);
    }
}
