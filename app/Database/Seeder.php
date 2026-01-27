<?php

declare(strict_types=1);

final class Seeder
{
    private PDO $pdo;
    private string $insertIgnore;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $this->insertIgnore = $driver === 'sqlite' ? 'INSERT OR IGNORE' : 'INSERT IGNORE';
    }

    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $workspacePermissions = [
            ['workspace.manage', 'Manage workspace settings'],
            ['members.manage', 'Manage workspace members'],
            ['invites.manage', 'Manage workspace invites'],
            ['uploads.manage', 'Manage workspace uploads'],
            ['billing.manage', 'Manage workspace billing and subscriptions'],
        ];

        $workspacePermStmt = $this->pdo->prepare(
            $this->insertIgnore . ' INTO workspace_permissions (name, description, created_at) VALUES (?, ?, ?)'
        );
        foreach ($workspacePermissions as [$name, $description]) {
            $workspacePermStmt->execute([$name, $description, $now]);
        }

        $plans = [
            ['free', 'Free', 0, 'monthly', 'subscription', 10, 1],
            ['trial', 'Trial', 0, 'trial', 'subscription', 10, 1],
            ['pro', 'Pro', 2900, 'monthly', 'subscription', 100, 1],
            ['pro-yearly', 'Pro (Annual)', 29900, 'yearly', 'subscription', 1200, 1],
            ['business', 'Business', 9900, 'monthly', 'subscription', 350, 1],
            ['business-yearly', 'Business (Annual)', 99900, 'yearly', 'subscription', 4200, 1],
            ['topup-500', 'AI Credits 500', 500, 'one_time', 'topup', 500, 1],
            ['topup-2000', 'AI Credits 2000', 1800, 'one_time', 'topup', 2000, 1],
        ];

        $planStmt = $this->pdo->prepare(
            $this->insertIgnore . ' INTO plans (code, name, price_cents, currency, duration, plan_type, ai_credits, is_active, is_grandfathered, disabled_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($plans as [$code, $name, $price, $interval, $type, $credits, $active]) {
            $planStmt->execute([$code, $name, $price, 'USD', $interval, $type, $credits, $active, 0, null]);
        }

        $this->seedAppSettings($now);
        $this->seedPaymentGatewaySettings($now);

        $adminId = $this->ensureDummyUser($now);
        $this->grantSystemAccess($adminId);
        $this->seedWorkspaceRolePermissions();
    }

    private function ensureDummyUser(string $now): int
    {
        $email = 'ops@workware.in';
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $existing = $stmt->fetchColumn();

        if ($existing) {
            return (int)$existing;
        }

        $hash = password_hash('Ma3GqqHVkb', PASSWORD_BCRYPT);
        $insert = $this->pdo->prepare(
            'INSERT INTO users (name, email, password_hash, email_verified_at, status, is_system_admin, is_system_staff, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insert->execute([
            'Demo User',
            $email,
            $hash,
            $now,
            'active',
            1,
            1,
            $now,
            $now,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    private function grantSystemAccess(int $userId): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET is_system_admin = 1, is_system_staff = 1 WHERE id = ?');
        $stmt->execute([$userId]);
    }

    private function seedWorkspaceRolePermissions(): void
    {
        $permissions = $this->pdo->query('SELECT id, name FROM workspace_permissions')->fetchAll(PDO::FETCH_ASSOC);
        if (!$permissions) {
            return;
        }

        $byName = [];
        foreach ($permissions as $permission) {
            $byName[$permission['name']] = (int)$permission['id'];
        }

        $map = [
            'Workspace Owner' => array_keys($byName),
            'Workspace Admin' => ['workspace.manage', 'members.manage', 'invites.manage', 'uploads.manage'],
            'Workspace Member' => [],
        ];

        $stmt = $this->pdo->prepare(
            $this->insertIgnore . ' INTO workspace_role_permissions (workspace_role, workspace_permission_id) VALUES (?, ?)'
        );

        foreach ($map as $role => $permissionNames) {
            foreach ($permissionNames as $name) {
                $permissionId = $byName[$name] ?? null;
                if ($permissionId === null) {
                    continue;
                }
                $stmt->execute([$role, $permissionId]);
            }
        }
    }

    private function seedAppSettings(string $now): void
    {
        $settings = [
            ['billing.currency', 'USD'],
            ['billing.gateway_rule', 'priority'],
            ['billing.gateway_priority', json_encode(['stripe', 'razorpay', 'paypal', 'lemonsqueezy', 'dodo', 'paddle'])],
        ];

        $stmt = $this->pdo->prepare(
            $this->insertIgnore . ' INTO app_settings (setting_key, setting_value, updated_at) VALUES (?, ?, ?)'
        );

        foreach ($settings as [$key, $value]) {
            $stmt->execute([$key, $value, $now]);
        }
    }

    private function seedPaymentGatewaySettings(string $now): void
    {
        $providers = ['stripe', 'razorpay', 'paypal', 'lemonsqueezy', 'dodo', 'paddle'];
        $stmt = $this->pdo->prepare(
            $this->insertIgnore . ' INTO payment_gateway_settings (provider, setting_key, setting_value, updated_at) VALUES (?, ?, ?, ?)'
        );

        foreach ($providers as $provider) {
            $stmt->execute([$provider, 'enabled', '1', $now]);
        }
    }

    private function fetchId(string $table, string $name): ?int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM ' . $table . ' WHERE name = ?');
        $stmt->execute([$name]);
        $value = $stmt->fetchColumn();

        return $value ? (int)$value : null;
    }
}
