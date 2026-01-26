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

        $roles = [
            ['App Super Admin', 'Full system access'],
            ['App Staff', 'Internal staff access'],
            ['App User', 'Standard user access'],
        ];

        $roleStmt = $this->pdo->prepare(
            $this->insertIgnore . ' INTO app_roles (name, description, created_at) VALUES (?, ?, ?)'
        );
        foreach ($roles as [$name, $description]) {
            $roleStmt->execute([$name, $description, $now]);
        }

        $permissions = [
            ['admin.view', 'View admin area'],
            ['users.manage', 'Manage users'],
            ['roles.manage', 'Manage roles'],
            ['audit.view', 'View audit logs'],
            ['uploads.manage', 'Manage uploads'],
            ['billing.admin', 'Manage billing plans and payment gateways'],
        ];

        $permStmt = $this->pdo->prepare(
            $this->insertIgnore . ' INTO app_permissions (name, description, created_at) VALUES (?, ?, ?)'
        );
        foreach ($permissions as [$name, $description]) {
            $permStmt->execute([$name, $description, $now]);
        }

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
            ['free', 'Free', 0, 'monthly', 1],
            ['trial', 'Trial', 0, 'trial', 1],
            ['pro', 'Pro', 2900, 'monthly', 1],
            ['business', 'Business', 9900, 'monthly', 1],
        ];

        $planStmt = $this->pdo->prepare(
            $this->insertIgnore . ' INTO plans (code, name, price_cents, interval, is_active) VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($plans as [$code, $name, $price, $interval, $active]) {
            $planStmt->execute([$code, $name, $price, $interval, $active]);
        }

        $adminId = $this->ensureDummyUser($now);
        $this->assignRole($adminId, 'App Super Admin');
        $this->grantAllPermissionsToRole('App Super Admin');
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
            'INSERT INTO users (name, email, password_hash, email_verified_at, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $insert->execute([
            'Demo User',
            $email,
            $hash,
            $now,
            'active',
            $now,
            $now,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    private function assignRole(int $userId, string $roleName): void
    {
        $roleId = $this->fetchId('app_roles', $roleName);
        if ($roleId === null) {
            return;
        }

        $stmt = $this->pdo->prepare(
            $this->insertIgnore . ' INTO user_app_roles (user_id, app_role_id) VALUES (?, ?)'
        );
        $stmt->execute([$userId, $roleId]);
    }

    private function grantAllPermissionsToRole(string $roleName): void
    {
        $roleId = $this->fetchId('app_roles', $roleName);
        if ($roleId === null) {
            return;
        }

        $perms = $this->pdo->query('SELECT id FROM app_permissions')->fetchAll(PDO::FETCH_COLUMN);
        $stmt = $this->pdo->prepare(
            $this->insertIgnore . ' INTO app_role_permissions (app_role_id, app_permission_id) VALUES (?, ?)'
        );

        foreach ($perms as $permissionId) {
            $stmt->execute([$roleId, (int)$permissionId]);
        }
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

    private function fetchId(string $table, string $name): ?int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM ' . $table . ' WHERE name = ?');
        $stmt->execute([$name]);
        $value = $stmt->fetchColumn();

        return $value ? (int)$value : null;
    }
}
