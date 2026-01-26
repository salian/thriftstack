<?php

declare(strict_types=1);

final class BillingService
{
    private PDO $pdo;
    private array $config;

    public function __construct(PDO $pdo, array $config)
    {
        $this->pdo = $pdo;
        $this->config = $config;
    }

    public function listPlans(bool $includeInactive = false): array
    {
        if ($includeInactive) {
            $stmt = $this->pdo->query('SELECT id, code, name, price_cents, `interval`, is_active FROM plans ORDER BY price_cents ASC');
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, code, name, price_cents, `interval`, is_active
             FROM plans
             WHERE is_active = 1
             ORDER BY price_cents ASC'
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getSubscriptionForWorkspace(int $workspaceId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.id, s.workspace_id, s.plan_id, s.status, s.trial_ends_at, s.current_period_end, s.created_at,
                    p.name AS plan_name, p.code AS plan_code, p.price_cents, p.`interval`
             FROM subscriptions s
             JOIN plans p ON p.id = s.plan_id
             WHERE s.workspace_id = ?
             ORDER BY s.id DESC
             LIMIT 1'
        );
        $stmt->execute([$workspaceId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function createTrialSubscription(int $workspaceId, int $planId, int $days): int
    {
        $trialEnd = (new DateTimeImmutable('now'))->modify('+' . $days . ' days');
        $trialEndAt = $trialEnd->format('Y-m-d H:i:s');
        $now = date('Y-m-d H:i:s');

        $stmt = $this->pdo->prepare(
            'INSERT INTO subscriptions (workspace_id, plan_id, status, trial_ends_at, current_period_end, created_at)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$workspaceId, $planId, 'trialing', $trialEndAt, $trialEndAt, $now]);

        return (int)$this->pdo->lastInsertId();
    }

    public function createSubscription(int $workspaceId, int $planId, string $status, ?string $periodEnd): int
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO subscriptions (workspace_id, plan_id, status, trial_ends_at, current_period_end, created_at)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$workspaceId, $planId, $status, null, $periodEnd, $now]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateSubscriptionPlan(int $subscriptionId, int $planId, string $status, ?string $periodEnd): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE subscriptions
             SET plan_id = ?, status = ?, current_period_end = ?
             WHERE id = ?'
        );
        $stmt->execute([$planId, $status, $periodEnd, $subscriptionId]);
    }

    public function listInvoices(int $subscriptionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, amount_cents, status, provider, external_id, created_at
             FROM invoices
             WHERE subscription_id = ?
             ORDER BY created_at DESC'
        );
        $stmt->execute([$subscriptionId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function recordInvoice(
        int $subscriptionId,
        int $amountCents,
        string $status,
        string $provider,
        string $externalId
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO invoices (subscription_id, amount_cents, status, provider, external_id, created_at)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $subscriptionId,
            $amountCents,
            $status,
            $provider,
            $externalId,
            date('Y-m-d H:i:s'),
        ]);
    }

    public function recordWebhookEvent(string $provider, string $eventType, string $payload): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO webhook_events (provider, event_type, payload, received_at)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$provider, $eventType, $payload, date('Y-m-d H:i:s')]);
    }

    public function createPlan(string $code, string $name, int $priceCents, string $interval, bool $isActive): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO plans (code, name, price_cents, `interval`, is_active)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$code, $name, $priceCents, $interval, $isActive ? 1 : 0]);
    }

    public function updatePlan(int $planId, string $name, int $priceCents, string $interval, bool $isActive): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE plans
             SET name = ?, price_cents = ?, `interval` = ?, is_active = ?
             WHERE id = ?'
        );
        $stmt->execute([$name, $priceCents, $interval, $isActive ? 1 : 0, $planId]);
    }

    public function findPlanByCode(string $code): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, code, name, price_cents, `interval`, is_active FROM plans WHERE code = ? LIMIT 1'
        );
        $stmt->execute([$code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findPlan(int $planId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, code, name, price_cents, `interval`, is_active FROM plans WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$planId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function trialDays(): int
    {
        return (int)($this->config['billing']['trial_days'] ?? 14);
    }

    public function ownerRoles(): array
    {
        $roles = $this->config['billing']['owner_roles'] ?? ['Workspace Owner', 'Owner'];
        if (!is_array($roles)) {
            return ['Workspace Owner', 'Owner'];
        }
        return $roles;
    }
}
