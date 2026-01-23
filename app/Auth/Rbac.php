<?php

declare(strict_types=1);

final class Rbac
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function roles(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, description FROM roles ORDER BY name');
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function permissions(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, description FROM permissions ORDER BY name');
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function users(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, email FROM users ORDER BY name');
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function rolesById(): array
    {
        $map = [];
        foreach ($this->roles() as $role) {
            $map[(int)$role['id']] = $role;
        }
        return $map;
    }

    public function permissionsByRole(): array
    {
        $stmt = $this->pdo->query(
            'SELECT rp.role_id, p.name
             FROM role_permissions rp
             INNER JOIN permissions p ON p.id = rp.permission_id'
        );
        $map = [];
        foreach ($stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [] as $row) {
            $map[(int)$row['role_id']][] = $row['name'];
        }
        return $map;
    }

    public function roleForUser(int $userId): ?string
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.name
             FROM roles r
             INNER JOIN user_roles ur ON ur.role_id = r.id
             WHERE ur.user_id = ?
             LIMIT 1'
        );
        $stmt->execute([$userId]);

        $role = $stmt->fetchColumn();
        return $role ? (string)$role : null;
    }

    public function assignRole(int $userId, int $roleId): void
    {
        $this->pdo->beginTransaction();
        try {
            $delete = $this->pdo->prepare('DELETE FROM user_roles WHERE user_id = ?');
            $delete->execute([$userId]);

            $insert = $this->pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)');
            $insert->execute([$userId, $roleId]);

            $this->pdo->commit();
            (new Audit($this->pdo))->log('rbac.role.assigned', $userId, ['role_id' => $roleId]);
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function setRolePermissions(int $roleId, array $permissionIds): void
    {
        $this->pdo->beginTransaction();
        try {
            $delete = $this->pdo->prepare('DELETE FROM role_permissions WHERE role_id = ?');
            $delete->execute([$roleId]);

            $insert = $this->pdo->prepare(
                'INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)'
            );

            foreach ($permissionIds as $permissionId) {
                $insert->execute([$roleId, (int)$permissionId]);
            }

            $this->pdo->commit();
            (new Audit($this->pdo))->log('rbac.role.permissions_updated', null, ['role_id' => $roleId]);
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function createRole(string $name, string $description): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO roles (name, description, created_at) VALUES (?, ?, NOW())'
        );
        $stmt->execute([$name, $description]);
        (new Audit($this->pdo))->log('rbac.role.created', null, ['name' => $name]);
    }

    public function createPermission(string $name, string $description): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO permissions (name, description, created_at) VALUES (?, ?, NOW())'
        );
        $stmt->execute([$name, $description]);
        (new Audit($this->pdo))->log('rbac.permission.created', null, ['name' => $name]);
    }
}
