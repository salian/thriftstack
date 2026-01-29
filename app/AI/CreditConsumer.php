<?php

declare(strict_types=1);

final class CreditConsumer
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @param array<string,mixed>|null $metadata
     * @return array{ok:bool,balance:int,error:?string}
     */
    public function consume(
        int $workspaceId,
        int $credits,
        string $usageType,
        ?array $metadata = null,
        ?string $description = null
    ): array {
        if ($workspaceId <= 0) {
            return ['ok' => false, 'balance' => 0, 'error' => 'Invalid workspace.'];
        }
        if ($credits <= 0) {
            return ['ok' => false, 'balance' => 0, 'error' => 'Credits must be positive.'];
        }

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $metadataJson = $metadata ? json_encode($metadata) : null;
        if ($metadataJson === 'null') {
            $metadataJson = null;
        }
        $now = date('Y-m-d H:i:s');

        $this->pdo->beginTransaction();
        try {
            $select = $driver === 'sqlite'
                ? 'SELECT ai_credit_balance FROM workspaces WHERE id = ?'
                : 'SELECT ai_credit_balance FROM workspaces WHERE id = ? FOR UPDATE';
            $stmt = $this->pdo->prepare($select);
            $stmt->execute([$workspaceId]);
            $current = $stmt->fetchColumn();
            if ($current === false) {
                $this->pdo->rollBack();
                return ['ok' => false, 'balance' => 0, 'error' => 'Workspace not found.'];
            }

            $balance = (int)$current;
            if ($balance < $credits) {
                $this->pdo->rollBack();
                return ['ok' => false, 'balance' => $balance, 'error' => 'Insufficient credits.'];
            }

            $newBalance = $balance - $credits;
            $update = $this->pdo->prepare('UPDATE workspaces SET ai_credit_balance = ? WHERE id = ?');
            $update->execute([$newBalance, $workspaceId]);

            $insert = $this->pdo->prepare(
                'INSERT INTO workspace_credit_ledger (workspace_id, change_type, credits, balance_after, source_type, source_id, description, usage_type, metadata, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $insert->execute([
                $workspaceId,
                'consume',
                -$credits,
                $newBalance,
                'usage',
                null,
                $description,
                $usageType,
                $metadataJson,
                $now,
            ]);

            $this->pdo->commit();
            return ['ok' => true, 'balance' => $newBalance, 'error' => null];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
