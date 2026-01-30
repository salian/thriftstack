<?php

declare(strict_types=1);

final class WorkspaceSettingsService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function defaults(): array
    {
        return [
            'reports' => [
                'digest_frequency' => 'off',
                'include_metrics' => [
                    'credit_usage_summary',
                    'depletion_forecast',
                    'top_categories',
                    'cost_breakdown',
                ],
                'recipients' => [],
                'last_sent_at' => null,
            ],
        ];
    }

    public function getSettings(int $workspaceId): array
    {
        $stmt = $this->pdo->prepare('SELECT settings_json FROM workspace_settings WHERE workspace_id = ?');
        $stmt->execute([$workspaceId]);
        $json = $stmt->fetchColumn();

        if (!$json) {
            return $this->defaults();
        }

        $data = json_decode((string)$json, true);
        if (!is_array($data)) {
            return $this->defaults();
        }

        return array_replace_recursive($this->defaults(), $data);
    }

    public function saveSettings(int $workspaceId, array $settings): void
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $now = date('Y-m-d H:i:s');
        $payload = json_encode($settings, JSON_UNESCAPED_SLASHES);

        if ($driver === 'sqlite') {
            $stmt = $this->pdo->prepare(
                'INSERT INTO workspace_settings (workspace_id, settings_json, created_at, updated_at)
                 VALUES (?, ?, ?, ?)
                 ON CONFLICT(workspace_id) DO UPDATE SET settings_json = excluded.settings_json, updated_at = excluded.updated_at'
            );
            $stmt->execute([$workspaceId, $payload, $now, $now]);
            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO workspace_settings (workspace_id, settings_json, created_at, updated_at)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE settings_json = VALUES(settings_json), updated_at = VALUES(updated_at)'
        );
        $stmt->execute([$workspaceId, $payload, $now, $now]);
    }
}
