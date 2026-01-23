<?php

declare(strict_types=1);

final class Seeder
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $roles = [
            ['Admin', 'Full system access'],
            ['Staff', 'Internal staff access'],
            ['User', 'Standard user access'],
        ];

        $roleStmt = $this->pdo->prepare(
            'INSERT IGNORE INTO roles (name, description, created_at) VALUES (?, ?, ?)'
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
        ];

        $permStmt = $this->pdo->prepare(
            'INSERT IGNORE INTO permissions (name, description, created_at) VALUES (?, ?, ?)'
        );
        foreach ($permissions as [$name, $description]) {
            $permStmt->execute([$name, $description, $now]);
        }

        $adminId = $this->ensureDummyUser($now);
        $this->assignRole($adminId, 'Admin');
        $this->grantAllPermissionsToRole('Admin');
    }

    private function ensureDummyUser(string $now): int
    {
        $email = 'admin@example.com';
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $existing = $stmt->fetchColumn();

        if ($existing) {
            return (int)$existing;
        }

        $hash = password_hash('password123', PASSWORD_BCRYPT);
        $insert = $this->pdo->prepare(
            'INSERT INTO users (name, email, password_hash, email_verified_at, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $insert->execute([
            'Admin User',
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
        $roleId = $this->fetchId('roles', $roleName);
        if ($roleId === null) {
            return;
        }

        $stmt = $this->pdo->prepare('INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)');
        $stmt->execute([$userId, $roleId]);
    }

    private function grantAllPermissionsToRole(string $roleName): void
    {
        $roleId = $this->fetchId('roles', $roleName);
        if ($roleId === null) {
            return;
        }

        $perms = $this->pdo->query('SELECT id FROM permissions')->fetchAll(PDO::FETCH_COLUMN);
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)'
        );

        foreach ($perms as $permissionId) {
            $stmt->execute([$roleId, (int)$permissionId]);
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
