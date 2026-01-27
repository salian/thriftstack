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
            $stmt = $this->pdo->query('SELECT id, code, name, price_cents, currency, duration, plan_type, ai_credits, is_active, is_grandfathered, disabled_at, stripe_price_id, razorpay_plan_id, paypal_plan_id, lemonsqueezy_variant_id, dodo_price_id, paddle_price_id FROM plans ORDER BY price_cents ASC');
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, code, name, price_cents, currency, duration, plan_type, ai_credits, is_active, is_grandfathered, disabled_at, stripe_price_id, razorpay_plan_id, paypal_plan_id, lemonsqueezy_variant_id, dodo_price_id, paddle_price_id
             FROM plans
             WHERE is_active = 1 AND plan_type = "subscription"
             ORDER BY price_cents ASC'
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listTopupPlans(bool $includeInactive = false): array
    {
        if ($includeInactive) {
            $stmt = $this->pdo->query('SELECT id, code, name, price_cents, currency, duration, plan_type, ai_credits, is_active, is_grandfathered, disabled_at, stripe_price_id, razorpay_plan_id, paypal_plan_id, lemonsqueezy_variant_id, dodo_price_id, paddle_price_id FROM plans WHERE plan_type = "topup" ORDER BY price_cents ASC');
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, code, name, price_cents, currency, duration, plan_type, ai_credits, is_active, is_grandfathered, disabled_at, stripe_price_id, razorpay_plan_id, paypal_plan_id, lemonsqueezy_variant_id, dodo_price_id, paddle_price_id
             FROM plans
             WHERE is_active = 1 AND plan_type = "topup"
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

    public function recordGatewayEvent(?int $subscriptionId, string $provider, string $status, string $eventType, ?int $purchaseId = null): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO payment_gateway_events (provider, subscription_id, purchase_id, status, event_type, created_at)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $provider,
            $subscriptionId,
            $purchaseId,
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
        string $planType,
        int $aiCredits,
        bool $isActive,
        array $providerIds = [],
        bool $isGrandfathered = false
    ): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO plans (code, name, price_cents, currency, duration, plan_type, ai_credits, is_active, is_grandfathered, disabled_at, stripe_price_id, razorpay_plan_id, paypal_plan_id, lemonsqueezy_variant_id, dodo_price_id, paddle_price_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $disabledAt = $isActive ? null : date('Y-m-d H:i:s');
        $stmt->execute([
            $code,
            $name,
            $priceCents,
            $currency,
            $duration,
            $planType,
            $aiCredits,
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
        string $planType,
        int $aiCredits,
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
             SET name = ?, price_cents = ?, duration = ?, plan_type = ?, ai_credits = ?, is_active = ?, is_grandfathered = ?, disabled_at = ?,
                 stripe_price_id = ?, razorpay_plan_id = ?, paypal_plan_id = ?, lemonsqueezy_variant_id = ?, dodo_price_id = ?, paddle_price_id = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $name,
            $priceCents,
            $duration,
            $planType,
            $aiCredits,
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
            'SELECT id, code, name, price_cents, currency, duration, plan_type, ai_credits, is_active, is_grandfathered, disabled_at, stripe_price_id, razorpay_plan_id, paypal_plan_id, lemonsqueezy_variant_id, dodo_price_id, paddle_price_id FROM plans WHERE code = ? LIMIT 1'
        );
        $stmt->execute([$code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findPlan(int $planId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, code, name, price_cents, currency, duration, plan_type, ai_credits, is_active, is_grandfathered, disabled_at, stripe_price_id, razorpay_plan_id, paypal_plan_id, lemonsqueezy_variant_id, dodo_price_id, paddle_price_id FROM plans WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$planId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createTopupPurchase(
        int $workspaceId,
        int $planId,
        int $credits,
        int $amountCents,
        string $currency,
        string $provider,
        ?string $providerCheckoutId,
        ?string $providerStatus
    ): int {
        $expiresAt = (new DateTimeImmutable('now'))->modify('+365 days')->format('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO ai_credit_purchases (workspace_id, plan_id, credits, remaining_credits, amount_cents, currency, provider, provider_checkout_id, provider_customer_id, provider_status, status, created_at, expires_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $workspaceId,
            $planId,
            $credits,
            $credits,
            $amountCents,
            $currency,
            $provider,
            $providerCheckoutId,
            null,
            $providerStatus,
            'pending',
            date('Y-m-d H:i:s'),
            $expiresAt,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateTopupPurchase(int $purchaseId, array $fields): void
    {
        $allowed = [
            'provider',
            'provider_checkout_id',
            'provider_customer_id',
            'provider_status',
            'status',
            'applied_at',
            'remaining_credits',
            'expires_at',
            'expired_at',
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
        $params[] = $purchaseId;
        $stmt = $this->pdo->prepare('UPDATE ai_credit_purchases SET ' . implode(', ', $set) . ' WHERE id = ?');
        $stmt->execute($params);
    }

    public function findTopupPurchaseById(int $purchaseId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM ai_credit_purchases WHERE id = ? LIMIT 1');
        $stmt->execute([$purchaseId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findTopupPurchaseByCheckoutId(string $provider, string $checkoutId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM ai_credit_purchases WHERE provider = ? AND provider_checkout_id = ? LIMIT 1'
        );
        $stmt->execute([$provider, $checkoutId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function listTopupPurchases(int $workspaceId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.id, p.plan_id, p.credits, p.remaining_credits, p.amount_cents, p.currency, p.status, p.created_at, p.applied_at, p.expires_at, p.expired_at, p.provider,
                    plans.name AS plan_name
             FROM ai_credit_purchases p
             JOIN plans ON plans.id = p.plan_id
             WHERE p.workspace_id = ?
             ORDER BY p.created_at DESC
             LIMIT 20'
        );
        $stmt->execute([$workspaceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function expireTopupCredits(): int
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'SELECT id, workspace_id, remaining_credits
             FROM ai_credit_purchases
             WHERE status = ? AND remaining_credits > 0 AND expires_at IS NOT NULL AND expires_at <= ? AND expired_at IS NULL'
        );
        $stmt->execute(['paid', $now]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if (empty($rows)) {
            return 0;
        }

        $count = 0;
        foreach ($rows as $row) {
            $purchaseId = (int)($row['id'] ?? 0);
            $workspaceId = (int)($row['workspace_id'] ?? 0);
            $remaining = (int)($row['remaining_credits'] ?? 0);
            if ($purchaseId <= 0 || $workspaceId <= 0 || $remaining <= 0) {
                continue;
            }
            $this->applyCreditChange(
                $workspaceId,
                -$remaining,
                'expire',
                'topup',
                $purchaseId,
                'Top-up credits expired'
            );
            $this->updateTopupPurchase($purchaseId, [
                'remaining_credits' => 0,
                'status' => 'expired',
                'expired_at' => $now,
            ]);
            $count++;
        }

        return $count;
    }

    public function grantSubscriptionCreditsIfEligible(int $subscriptionId, ?string $status): void
    {
        if ($status !== 'active' && $status !== 'trialing') {
            return;
        }

        $stmt = $this->pdo->prepare(
            'SELECT s.id, s.workspace_id, s.credits_granted_at, p.ai_credits, p.name
             FROM subscriptions s
             JOIN plans p ON p.id = s.plan_id
             WHERE s.id = ?
             LIMIT 1'
        );
        $stmt->execute([$subscriptionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return;
        }

        if (!empty($row['credits_granted_at'])) {
            return;
        }

        $credits = (int)($row['ai_credits'] ?? 0);
        if ($credits <= 0) {
            return;
        }

        $workspaceId = (int)($row['workspace_id'] ?? 0);
        $this->applyCreditChange(
            $workspaceId,
            $credits,
            'grant',
            'subscription',
            $subscriptionId,
            'Subscription credits'
        );

        $update = $this->pdo->prepare('UPDATE subscriptions SET credits_granted_at = ? WHERE id = ?');
        $update->execute([date('Y-m-d H:i:s'), $subscriptionId]);
    }

    public function applyCreditChange(
        int $workspaceId,
        int $credits,
        string $changeType,
        string $sourceType,
        ?int $sourceId,
        ?string $description = null
    ): void {
        if ($credits === 0) {
            return;
        }

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
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
                return;
            }
            $balance = (int)$current;
            $newBalance = $balance + $credits;
            if ($newBalance < 0) {
                $newBalance = 0;
            }

            $update = $this->pdo->prepare('UPDATE workspaces SET ai_credit_balance = ? WHERE id = ?');
            $update->execute([$newBalance, $workspaceId]);

            $insert = $this->pdo->prepare(
                'INSERT INTO workspace_credit_ledger (workspace_id, change_type, credits, balance_after, source_type, source_id, description, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $insert->execute([
                $workspaceId,
                $changeType,
                $credits,
                $newBalance,
                $sourceType,
                $sourceId,
                $description,
                date('Y-m-d H:i:s'),
            ]);

            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
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
