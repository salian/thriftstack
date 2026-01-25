<?php

declare(strict_types=1);

final class WorkspaceService
{
    private PDO $pdo;
    private Audit $audit;

    public function __construct(PDO $pdo, ?Audit $audit = null)
    {
        $this->pdo = $pdo;
        $this->audit = $audit ?? new Audit($pdo);
    }

    public function listForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT w.id, w.name, w.created_by, w.created_at, m.role
             FROM workspaces w
             JOIN workspace_memberships m ON m.workspace_id = w.id
             WHERE m.user_id = ?
             ORDER BY w.created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function createWorkspace(string $name, int $userId): int
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO workspaces (name, created_by, created_at) VALUES (?, ?, ?)'
        );
        $stmt->execute([$name, $userId, $now]);
        $workspaceId = (int)$this->pdo->lastInsertId();

        $memberStmt = $this->pdo->prepare(
            'INSERT INTO workspace_memberships (user_id, workspace_id, role, created_at) VALUES (?, ?, ?, ?)'
        );
        $memberStmt->execute([$userId, $workspaceId, 'Owner', $now]);

        $this->setCurrentWorkspace($workspaceId);
        $this->audit->log('workspaces.created', $userId, ['workspace_id' => $workspaceId]);

        return $workspaceId;
    }

    public function ensureCurrentWorkspace(int $userId): ?array
    {
        $currentId = $this->currentWorkspaceId();
        if ($currentId) {
            $role = $this->membershipRole($userId, $currentId);
            if ($role !== null) {
                return $this->getWorkspace($currentId);
            }
        }

        $workspaces = $this->listForUser($userId);
        if (empty($workspaces)) {
            return null;
        }

        $workspaceId = (int)$workspaces[0]['id'];
        $this->setCurrentWorkspace($workspaceId);

        return $this->getWorkspace($workspaceId);
    }

    public function getWorkspace(int $workspaceId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, created_by, created_at FROM workspaces WHERE id = ?');
        $stmt->execute([$workspaceId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function setCurrentWorkspace(int $workspaceId): void
    {
        $_SESSION['workspace_id'] = $workspaceId;
    }

    public function currentWorkspaceId(): ?int
    {
        $value = $_SESSION['workspace_id'] ?? null;
        return $value ? (int)$value : null;
    }

    public function membershipRole(int $userId, int $workspaceId): ?string
    {
        $stmt = $this->pdo->prepare(
            'SELECT role FROM workspace_memberships WHERE user_id = ? AND workspace_id = ? LIMIT 1'
        );
        $stmt->execute([$userId, $workspaceId]);
        $role = $stmt->fetchColumn();

        return $role ? (string)$role : null;
    }

    public function listMembers(int $workspaceId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.name, u.email, m.role, m.created_at
             FROM workspace_memberships m
             JOIN users u ON u.id = m.user_id
             WHERE m.workspace_id = ?
             ORDER BY m.created_at ASC'
        );
        $stmt->execute([$workspaceId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function changeMemberRole(int $workspaceId, int $memberId, string $role, int $actorId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE workspace_memberships SET role = ? WHERE workspace_id = ? AND user_id = ?'
        );
        $stmt->execute([$role, $workspaceId, $memberId]);

        if ($stmt->rowCount() > 0) {
            $this->audit->log('workspaces.member.role_changed', $actorId, [
                'workspace_id' => $workspaceId,
                'member_id' => $memberId,
                'role' => $role,
            ]);
            return true;
        }

        return false;
    }

    public function listInvites(int $workspaceId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, email, role, expires_at, created_at, accepted_at
             FROM workspace_invites
             WHERE workspace_id = ?
             ORDER BY created_at DESC'
        );
        $stmt->execute([$workspaceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function createInvite(int $workspaceId, string $email, string $role, int $actorId): string
    {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $now = new DateTimeImmutable('now');
        $expires = $now->modify('+7 days');

        $stmt = $this->pdo->prepare(
            'INSERT INTO workspace_invites (workspace_id, email, role, token_hash, expires_at, created_at)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $workspaceId,
            $email,
            $role,
            $tokenHash,
            $expires->format('Y-m-d H:i:s'),
            $now->format('Y-m-d H:i:s'),
        ]);

        $this->audit->log('workspaces.invite.created', $actorId, [
            'workspace_id' => $workspaceId,
            'email' => $email,
            'role' => $role,
        ]);

        return $token;
    }

    public function lookupInvite(string $token): ?array
    {
        $tokenHash = hash('sha256', $token);
        $stmt = $this->pdo->prepare(
            'SELECT i.id, i.workspace_id, i.email, i.role, i.expires_at, i.accepted_at, w.name AS workspace_name
             FROM workspace_invites i
             JOIN workspaces w ON w.id = i.workspace_id
             WHERE i.token_hash = ?
             LIMIT 1'
        );
        $stmt->execute([$tokenHash]);
        $invite = $stmt->fetch(PDO::FETCH_ASSOC);

        return $invite ?: null;
    }

    public function acceptInvite(string $token, int $userId): array
    {
        $invite = $this->lookupInvite($token);
        if (!$invite) {
            return ['ok' => false, 'error' => 'Invite token is invalid.'];
        }

        if (!empty($invite['accepted_at'])) {
            return ['ok' => false, 'error' => 'Invite has already been accepted.'];
        }

        if (strtotime((string)$invite['expires_at']) < time()) {
            return ['ok' => false, 'error' => 'Invite has expired.'];
        }

        $workspaceId = (int)$invite['workspace_id'];
        $role = (string)$invite['role'];
        $now = date('Y-m-d H:i:s');

        $existing = $this->membershipRole($userId, $workspaceId);
        if ($existing === null) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO workspace_memberships (user_id, workspace_id, role, created_at) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$userId, $workspaceId, $role, $now]);
        }

        $update = $this->pdo->prepare('UPDATE workspace_invites SET accepted_at = ? WHERE id = ?');
        $update->execute([$now, (int)$invite['id']]);

        $this->setCurrentWorkspace($workspaceId);
        $this->audit->log('workspaces.invite.accepted', $userId, [
            'workspace_id' => $workspaceId,
            'role' => $role,
        ]);

        return ['ok' => true, 'workspace_id' => $workspaceId, 'role' => $role];
    }

    public function isRoleAtLeast(string $role, string $required): bool
    {
        $weights = [
            'Member' => 1,
            'Admin' => 2,
            'Owner' => 3,
        ];

        return ($weights[$role] ?? 0) >= ($weights[$required] ?? 0);
    }
}
