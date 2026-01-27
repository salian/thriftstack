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
            $stmt = $this->pdo->query('SELECT id, code, name, price_cents, currency, duration, is_active, is_grandfathered, disabled_at, stripe_price_id, razorpay_plan_id, paypal_plan_id, lemonsqueezy_variant_id, dodo_price_id, paddle_price_id FROM plans ORDER BY price_cents ASC');
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, code, name, price_cents, currency, duration, is_active, is_grandfathered, disabled_at, stripe_price_id, razorpay_plan_id, paypal_plan_id, lemonsqueezy_variant_id, dodo_price_id, paddle_price_id
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
            'SELECT s.id, s.workspace_id, s.plan_id, s.status, s.trial_ends_at, s.current_period_start, s.current_period_end,
                    s.cancel_at_period_end, s.canceled_at, s.provider, s.provider_customer_id, s.provider_subscription_id,
                    s.provider_checkout_id, s.provider_status, s.currency, s.type, s.created_at,
                    p.name AS plan_name, p.code AS plan_code, p.price_cents, p.currency AS plan_currency, p.duration
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
            'INSERT INTO subscriptions (workspace_id, plan_id, status, trial_ends_at, current_period_start, current_period_end, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$workspaceId, $planId, 'trialing', $trialEndAt, $now, $trialEndAt, $now]);

        return (int)$this->pdo->lastInsertId();
    }

    public function createSubscription(int $workspaceId, int $planId, string $status, ?string $periodEnd): int
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO subscriptions (workspace_id, plan_id, status, trial_ends_at, current_period_start, current_period_end, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$workspaceId, $planId, $status, null, $now, $periodEnd, $now]);

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

    public function createPendingSubscription(
        int $workspaceId,
        int $planId,
        string $provider,
        string $currency,
        string $type,
        ?string $providerCheckoutId,
        ?string $providerStatus
    ): int {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO subscriptions (workspace_id, plan_id, provider, provider_checkout_id, provider_status, currency, type, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $workspaceId,
            $planId,
            $provider,
            $providerCheckoutId,
            $providerStatus,
            $currency,
            $type,
            'pending',
            $now,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateSubscriptionProvider(
        int $subscriptionId,
        array $fields
    ): void {
        $allowed = [
            'provider',
            'provider_customer_id',
            'provider_subscription_id',
            'provider_checkout_id',
            'provider_status',
            'status',
            'current_period_start',
            'current_period_end',
            'cancel_at_period_end',
            'canceled_at',
        ];
        $set = [];
        $params = [];
        foreach ($fields as $key => $value) {
            if (!in_array($key, $allowed, true)) {
                continue;
            }
            $set[] = $key . ' = ?';
            $params[] = $value;
        }
        if (empty($set)) {
            return;
        }
        $params[] = $subscriptionId;
        $sql = 'UPDATE subscriptions SET ' . implode(', ', $set) . ' WHERE id = ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    public function findSubscriptionById(int $subscriptionId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM subscriptions WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$subscriptionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findSubscriptionByProviderId(string $provider, string $providerSubscriptionId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM subscriptions WHERE provider = ? AND provider_subscription_id = ? LIMIT 1'
        );
        $stmt->execute([$provider, $providerSubscriptionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findSubscriptionByCheckoutId(string $provider, string $providerCheckoutId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM subscriptions WHERE provider = ? AND provider_checkout_id = ? LIMIT 1'
        );
        $stmt->execute([$provider, $providerCheckoutId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function recordGatewayEvent(?int $subscriptionId, string $provider, string $status, string $eventType): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO payment_gateway_events (provider, subscription_id, status, event_type, created_at)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $provider,
            $subscriptionId,
            $status,
            $eventType,
            date('Y-m-d H:i:s'),
        ]);
    }

    public function createSubscriptionChange(
        int $subscriptionId,
        ?int $fromPlanId,
        ?int $toPlanId,
        string $changeType,
        string $status,
        ?string $effectiveAt,
        ?string $provider,
        ?string $providerCheckoutId
    ): int {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO subscription_changes (subscription_id, from_plan_id, to_plan_id, change_type, status, effective_at, created_at, applied_at, provider, provider_checkout_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $subscriptionId,
            $fromPlanId,
            $toPlanId,
            $changeType,
            $status,
            $effectiveAt,
            $now,
            $status === 'applied' ? $now : null,
            $provider,
            $providerCheckoutId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function findSubscriptionChange(int $changeId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM subscription_changes WHERE id = ? LIMIT 1');
        $stmt->execute([$changeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateSubscriptionChange(int $changeId, array $fields): void
    {
        $allowed = ['status', 'effective_at', 'applied_at', 'provider', 'provider_checkout_id'];
        $set = [];
        $params = [];
        foreach ($fields as $key => $value) {
            if (!in_array($key, $allowed, true)) {
                continue;
            }
            $set[] = $key . ' = ?';
            $params[] = $value;
        }
        if (empty($set)) {
            return;
        }
        $params[] = $changeId;
        $stmt = $this->pdo->prepare('UPDATE subscription_changes SET ' . implode(', ', $set) . ' WHERE id = ?');
        $stmt->execute($params);
    }

    public function pendingChangesForSubscription(int $subscriptionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM subscription_changes WHERE subscription_id = ? AND status = ? ORDER BY created_at ASC'
        );
        $stmt->execute([$subscriptionId, 'pending']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function applySubscriptionChange(int $changeId, int $subscriptionId, int $planId, ?string $effectiveAt = null): void
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'UPDATE subscription_changes SET status = ?, applied_at = ? WHERE id = ?'
        );
        $stmt->execute(['applied', $now, $changeId]);

        $periodEnd = $effectiveAt ?? null;
        $update = $this->pdo->prepare(
            'UPDATE subscriptions SET plan_id = ?, status = ?, current_period_end = ? WHERE id = ?'
        );
        $update->execute([$planId, 'active', $periodEnd, $subscriptionId]);
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

    public function createPlan(
        string $code,
        string $name,
        int $priceCents,
        string $currency,
        string $duration,
        bool $isActive,
        array $providerIds = [],
        bool $isGrandfathered = false
    ): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO plans (code, name, price_cents, currency, duration, is_active, is_grandfathered, disabled_at, stripe_price_id, razorpay_plan_id, paypal_plan_id, lemonsqueezy_variant_id, dodo_price_id, paddle_price_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $disabledAt = $isActive ? null : date('Y-m-d H:i:s');
        $stmt->execute([
            $code,
            $name,
            $priceCents,
            $currency,
            $duration,
            $isActive ? 1 : 0,
            $isGrandfathered ? 1 : 0,
            $disabledAt,
            $providerIds['stripe'] ?? null,
            $providerIds['razorpay'] ?? null,
            $providerIds['paypal'] ?? null,
            $providerIds['lemonsqueezy'] ?? null,
            $providerIds['dodo'] ?? null,
            $providerIds['paddle'] ?? null,
        ]);
    }

    public function updatePlan(
        int $planId,
        string $name,
        int $priceCents,
        string $duration,
        bool $isActive,
        array $providerIds = [],
        bool $isGrandfathered = false
    ): void
    {
        $current = $this->findPlan($planId);
        $disabledAt = $current['disabled_at'] ?? null;
        if ($isActive) {
            $disabledAt = null;
        } elseif (empty($disabledAt)) {
            $disabledAt = date('Y-m-d H:i:s');
        }

        $stmt = $this->pdo->prepare(
            'UPDATE plans
             SET name = ?, price_cents = ?, duration = ?, is_active = ?, is_grandfathered = ?, disabled_at = ?,
                 stripe_price_id = ?, razorpay_plan_id = ?, paypal_plan_id = ?, lemonsqueezy_variant_id = ?, dodo_price_id = ?, paddle_price_id = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $name,
            $priceCents,
            $duration,
            $isActive ? 1 : 0,
            $isGrandfathered ? 1 : 0,
            $disabledAt,
            $providerIds['stripe'] ?? null,
            $providerIds['razorpay'] ?? null,
            $providerIds['paypal'] ?? null,
            $providerIds['lemonsqueezy'] ?? null,
            $providerIds['dodo'] ?? null,
            $providerIds['paddle'] ?? null,
            $planId,
        ]);
    }

    public function findPlanByCode(string $code): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, code, name, price_cents, currency, duration, is_active, is_grandfathered, disabled_at, stripe_price_id, razorpay_plan_id, paypal_plan_id, lemonsqueezy_variant_id, dodo_price_id, paddle_price_id FROM plans WHERE code = ? LIMIT 1'
        );
        $stmt->execute([$code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findPlan(int $planId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, code, name, price_cents, currency, duration, is_active, is_grandfathered, disabled_at, stripe_price_id, razorpay_plan_id, paypal_plan_id, lemonsqueezy_variant_id, dodo_price_id, paddle_price_id FROM plans WHERE id = ? LIMIT 1'
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
