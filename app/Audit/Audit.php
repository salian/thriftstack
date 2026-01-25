<?php

declare(strict_types=1);

final class Audit
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function log(string $action, ?int $userId = null, array $metadata = []): void
    {
        $payload = $metadata ? json_encode($metadata, JSON_UNESCAPED_SLASHES) : null;

        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_logs (user_id, action, metadata, created_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([$userId, $action, $payload]);
    }
}
