<?php

declare(strict_types=1);

final class PaymentGatewaySettingsService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getProviderSettings(string $provider): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT setting_key, setting_value FROM payment_gateway_settings WHERE provider = ?'
        );
        $stmt->execute([$provider]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $settings = [];
        foreach ($rows as $row) {
            $key = (string)($row['setting_key'] ?? '');
            if ($key === '') {
                continue;
            }
            $settings[$key] = (string)($row['setting_value'] ?? '');
        }

        return $settings;
    }

    public function getAllSettings(): array
    {
        $stmt = $this->pdo->query('SELECT provider, setting_key, setting_value FROM payment_gateway_settings');
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $settings = [];
        foreach ($rows as $row) {
            $provider = (string)($row['provider'] ?? '');
            $key = (string)($row['setting_key'] ?? '');
            if ($provider === '' || $key === '') {
                continue;
            }
            $settings[$provider][$key] = (string)($row['setting_value'] ?? '');
        }

        return $settings;
    }

    public function enabledProviders(): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT provider FROM payment_gateway_settings WHERE setting_key = ? AND setting_value = ?'
        );
        $stmt->execute(['enabled', '1']);
        $providers = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        return array_values(array_unique(array_map('strval', $providers)));
    }

    public function saveProviderSettings(string $provider, array $payload): void
    {
        $now = date('Y-m-d H:i:s');
        $insert = $this->pdo->prepare(
            'INSERT INTO payment_gateway_settings (provider, setting_key, setting_value, updated_at)
             VALUES (?, ?, ?, ?)'
        );
        $update = $this->pdo->prepare(
            'UPDATE payment_gateway_settings SET setting_value = ?, updated_at = ? WHERE provider = ? AND setting_key = ?'
        );
        $exists = $this->pdo->prepare(
            'SELECT COUNT(*) FROM payment_gateway_settings WHERE provider = ? AND setting_key = ?'
        );

        foreach ($payload as $key => $value) {
            if ($value === '') {
                $delete = $this->pdo->prepare('DELETE FROM payment_gateway_settings WHERE provider = ? AND setting_key = ?');
                $delete->execute([$provider, $key]);
                continue;
            }
            $exists->execute([$provider, $key]);
            $count = (int)$exists->fetchColumn();
            if ($count > 0) {
                $update->execute([$value, $now, $provider, $key]);
            } else {
                $insert->execute([$provider, $key, $value, $now]);
            }
        }
    }
}
