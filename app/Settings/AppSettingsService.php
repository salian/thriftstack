<?php

declare(strict_types=1);

final class AppSettingsService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $stmt = $this->pdo->prepare('SELECT setting_value FROM app_settings WHERE setting_key = ? LIMIT 1');
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        if ($value === false || $value === null) {
            return $default;
        }
        return (string)$value;
    }

    public function getJson(string $key, array $default = []): array
    {
        $value = $this->get($key);
        if ($value === null || $value === '') {
            return $default;
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : $default;
    }

    public function set(string $key, string $value): void
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM app_settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $exists = (int)$stmt->fetchColumn();

        if ($exists > 0) {
            $update = $this->pdo->prepare('UPDATE app_settings SET setting_value = ?, updated_at = ? WHERE setting_key = ?');
            $update->execute([$value, $now, $key]);
            return;
        }

        $insert = $this->pdo->prepare('INSERT INTO app_settings (setting_key, setting_value, updated_at) VALUES (?, ?, ?)');
        $insert->execute([$key, $value, $now]);
    }
}
