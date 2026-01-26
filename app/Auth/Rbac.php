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
        $stmt = $this->pdo->query('SELECT id, name, description FROM app_roles ORDER BY name');
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function permissions(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, description FROM app_permissions ORDER BY name');
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
            'SELECT rp.app_role_id, p.name
             FROM app_role_permissions rp
             INNER JOIN app_permissions p ON p.id = rp.app_permission_id'
        );
        $map = [];
        foreach ($stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [] as $row) {
            $map[(int)$row['app_role_id']][] = $row['name'];
        }
        return $map;
    }

    public function roleForUser(int $userId): ?string
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.name
             FROM app_roles r
             INNER JOIN user_app_roles ur ON ur.app_role_id = r.id
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
            $delete = $this->pdo->prepare('DELETE FROM user_app_roles WHERE user_id = ?');
            $delete->execute([$userId]);

            $insert = $this->pdo->prepare('INSERT INTO user_app_roles (user_id, app_role_id) VALUES (?, ?)');
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
            $delete = $this->pdo->prepare('DELETE FROM app_role_permissions WHERE app_role_id = ?');
            $delete->execute([$roleId]);

            $insert = $this->pdo->prepare(
                'INSERT INTO app_role_permissions (app_role_id, app_permission_id) VALUES (?, ?)'
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
            'INSERT INTO app_roles (name, description, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([$name, $description]);
        (new Audit($this->pdo))->log('rbac.role.created', null, ['name' => $name]);
    }

    public function createPermission(string $name, string $description): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO app_permissions (name, description, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([$name, $description]);
        (new Audit($this->pdo))->log('rbac.permission.created', null, ['name' => $name]);
    }

    public function workspacePermissions(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, description FROM workspace_permissions ORDER BY name');
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function workspacePermissionsByRole(): array
    {
        $stmt = $this->pdo->query(
            'SELECT wrp.workspace_role, p.name
             FROM workspace_role_permissions wrp
             INNER JOIN workspace_permissions p ON p.id = wrp.workspace_permission_id'
        );
        $map = [];
        foreach ($stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [] as $row) {
            $map[(string)$row['workspace_role']][] = $row['name'];
        }
        return $map;
    }

    public function setWorkspaceRolePermissions(string $role, array $permissionIds): void
    {
        $this->pdo->beginTransaction();
        try {
            $delete = $this->pdo->prepare('DELETE FROM workspace_role_permissions WHERE workspace_role = ?');
            $delete->execute([$role]);

            $insert = $this->pdo->prepare(
                'INSERT INTO workspace_role_permissions (workspace_role, workspace_permission_id) VALUES (?, ?)'
            );

            foreach ($permissionIds as $permissionId) {
                $insert->execute([$role, (int)$permissionId]);
            }

            $this->pdo->commit();
            (new Audit($this->pdo))->log('rbac.workspace_role.permissions_updated', null, ['role' => $role]);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function createWorkspacePermission(string $name, string $description): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO workspace_permissions (name, description, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([$name, $description]);
        (new Audit($this->pdo))->log('rbac.workspace_permission.created', null, ['name' => $name]);
    }
}
