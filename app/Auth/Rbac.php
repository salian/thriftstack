<?php

declare(strict_types=1);

final class Rbac
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function users(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, email FROM users ORDER BY name');
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
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
