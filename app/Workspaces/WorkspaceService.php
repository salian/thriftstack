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
            'SELECT w.id, w.name, w.created_by, w.created_at, w.ai_credit_balance, m.workspace_role AS role,
                (SELECT COUNT(*) FROM workspace_memberships wm2 WHERE wm2.workspace_id = w.id) AS member_count
             FROM workspaces w
             JOIN workspace_memberships m ON m.workspace_id = w.id
             WHERE m.user_id = ?
             ORDER BY w.created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countWorkspacesForUser(int $userId, string $search, string $role): int
    {
        $conditions = ['m.user_id = ?'];
        $params = [$userId];

        if ($search !== '') {
            $conditions[] = 'w.name LIKE ?';
            $params[] = '%' . $search . '%';
        }

        if ($role !== '' && $role !== 'all') {
            $conditions[] = 'm.workspace_role = ?';
            $params[] = $role;
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM workspaces w
             JOIN workspace_memberships m ON m.workspace_id = w.id
             ' . $where
        );
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    public function listWorkspacesForUser(
        int $userId,
        string $search,
        string $role,
        int $limit,
        int $offset
    ): array {
        $conditions = ['m.user_id = ?'];
        $params = [$userId];

        if ($search !== '') {
            $conditions[] = 'w.name LIKE ?';
            $params[] = '%' . $search . '%';
        }

        if ($role !== '' && $role !== 'all') {
            $conditions[] = 'm.workspace_role = ?';
            $params[] = $role;
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $sql = 'SELECT w.id, w.name, w.created_by, w.created_at, m.workspace_role AS role,
                (SELECT COUNT(*) FROM workspace_memberships wm2 WHERE wm2.workspace_id = w.id) AS member_count
             FROM workspaces w
             JOIN workspace_memberships m ON m.workspace_id = w.id
             ' . $where . '
             ORDER BY w.created_at DESC
             LIMIT ? OFFSET ?';
        $stmt = $this->pdo->prepare($sql);
        $bindIndex = 1;
        foreach ($params as $param) {
            $stmt->bindValue($bindIndex, $param);
            $bindIndex++;
        }
        $stmt->bindValue($bindIndex, $limit, PDO::PARAM_INT);
        $stmt->bindValue($bindIndex + 1, $offset, PDO::PARAM_INT);
        $stmt->execute();

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
            'INSERT INTO workspace_memberships (user_id, workspace_id, workspace_role, created_at) VALUES (?, ?, ?, ?)'
        );
        $memberStmt->execute([$userId, $workspaceId, 'Workspace Owner', $now]);

        $this->setCurrentWorkspace($workspaceId);
        $this->audit->log('workspaces.created', $userId, ['workspace_id' => $workspaceId]);

        return $workspaceId;
    }

    public function updateWorkspaceName(int $workspaceId, string $name, int $actorId): bool
    {
        $stmt = $this->pdo->prepare('UPDATE workspaces SET name = ? WHERE id = ?');
        $stmt->execute([$name, $workspaceId]);

        if ($stmt->rowCount() > 0) {
            $this->audit->log('workspaces.updated', $actorId, [
                'workspace_id' => $workspaceId,
                'name' => $name,
            ]);
            return true;
        }

        return false;
    }

    public function ensureWorkspaceForUser(int $userId, string $userName): int
    {
        $workspaces = $this->listForUser($userId);
        if (empty($workspaces)) {
            return $this->createWorkspace($this->defaultWorkspaceName($userName), $userId);
        }

        $current = $this->ensureCurrentWorkspace($userId);
        if ($current) {
            return (int)$current['id'];
        }

        return (int)$workspaces[0]['id'];
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
        $stmt = $this->pdo->prepare('SELECT id, name, created_by, created_at, ai_credit_balance FROM workspaces WHERE id = ?');
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
            'SELECT workspace_role FROM workspace_memberships WHERE user_id = ? AND workspace_id = ? LIMIT 1'
        );
        $stmt->execute([$userId, $workspaceId]);
        $role = $stmt->fetchColumn();

        return $role ? (string)$role : null;
    }

    public function workspacePermissionsForRole(string $role): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.name
             FROM workspace_role_permissions wrp
             INNER JOIN workspace_permissions p ON p.id = wrp.workspace_permission_id
             WHERE wrp.workspace_role = ?
             ORDER BY p.name'
        );
        $stmt->execute([$role]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public function hasWorkspacePermission(int $userId, int $workspaceId, string $permission): bool
    {
        $role = $this->membershipRole($userId, $workspaceId);
        if ($role === null) {
            return false;
        }

        $permissions = $this->workspacePermissionsForRole($role);
        return in_array($permission, $permissions, true);
    }

    public function listMembers(int $workspaceId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.name, u.email, m.workspace_role AS role, m.created_at
             FROM workspace_memberships m
             JOIN users u ON u.id = m.user_id
             WHERE m.workspace_id = ?
             ORDER BY m.created_at ASC'
        );
        $stmt->execute([$workspaceId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countTeamEntries(int $workspaceId, string $search, string $role): int
    {
        [$memberWhere, $memberParams] = $this->buildMemberFilters($workspaceId, $search, $role);
        [$inviteWhere, $inviteParams] = $this->buildInviteFilters($workspaceId, $search, $role);

        $sql = 'SELECT COUNT(*) FROM (
                SELECT u.id
                FROM workspace_memberships m
                JOIN users u ON u.id = m.user_id
                ' . $memberWhere . '
                UNION ALL
                SELECT i.id
                FROM workspace_invites i
                ' . $inviteWhere . '
            ) AS entries';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge($memberParams, $inviteParams));

        return (int)$stmt->fetchColumn();
    }

    public function listTeamEntries(
        int $workspaceId,
        string $search,
        string $role,
        int $limit,
        int $offset
    ): array {
        [$memberWhere, $memberParams] = $this->buildMemberFilters($workspaceId, $search, $role);
        [$inviteWhere, $inviteParams] = $this->buildInviteFilters($workspaceId, $search, $role);

        $sql = 'SELECT u.id AS user_id, u.name, u.email, m.workspace_role AS role,
                    "Active" AS status, 0 AS is_invite, NULL AS invite_id, m.created_at AS sort_date
                FROM workspace_memberships m
                JOIN users u ON u.id = m.user_id
                ' . $memberWhere . '
                UNION ALL
                SELECT NULL AS user_id, NULL AS name, i.email, i.workspace_role AS role,
                    "Invited" AS status, 1 AS is_invite, i.id AS invite_id, i.created_at AS sort_date
                FROM workspace_invites i
                ' . $inviteWhere . '
                ORDER BY is_invite ASC, sort_date ASC
                LIMIT ? OFFSET ?';
        $stmt = $this->pdo->prepare($sql);
        $params = array_merge($memberParams, $inviteParams);
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function buildMemberFilters(int $workspaceId, string $search, string $role): array
    {
        $conditions = ['m.workspace_id = ?'];
        $params = [$workspaceId];

        if ($search !== '') {
            $conditions[] = '(u.name LIKE ? OR u.email LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        if ($role !== '' && $role !== 'all') {
            $conditions[] = 'm.workspace_role = ?';
            $params[] = $role;
        }

        return ['WHERE ' . implode(' AND ', $conditions), $params];
    }

    private function buildInviteFilters(int $workspaceId, string $search, string $role): array
    {
        $conditions = ['i.workspace_id = ?', 'i.accepted_at IS NULL'];
        $params = [$workspaceId];

        if ($search !== '') {
            $conditions[] = 'i.email LIKE ?';
            $params[] = '%' . $search . '%';
        }

        if ($role !== '' && $role !== 'all') {
            $conditions[] = 'i.workspace_role = ?';
            $params[] = $role;
        }

        return ['WHERE ' . implode(' AND ', $conditions), $params];
    }

    public function changeMemberRole(int $workspaceId, int $memberId, string $role, int $actorId): bool
    {
        $currentRole = $this->membershipRole($memberId, $workspaceId);
        if ($currentRole === null) {
            return false;
        }

        if ($currentRole === $role) {
            return true;
        }

        $this->pdo->beginTransaction();
        try {
            if ($role === 'Workspace Owner') {
                $ownerStmt = $this->pdo->prepare(
                    'SELECT user_id FROM workspace_memberships WHERE workspace_id = ? AND workspace_role = ? LIMIT 1'
                );
                $ownerStmt->execute([$workspaceId, 'Workspace Owner']);
                $currentOwnerId = (int)($ownerStmt->fetchColumn() ?: 0);

                if ($currentOwnerId > 0 && $currentOwnerId !== $memberId) {
                    $demote = $this->pdo->prepare(
                        'UPDATE workspace_memberships SET workspace_role = ? WHERE workspace_id = ? AND user_id = ?'
                    );
                    $demote->execute(['Workspace Admin', $workspaceId, $currentOwnerId]);
                }
            }

            $stmt = $this->pdo->prepare(
                'UPDATE workspace_memberships SET workspace_role = ? WHERE workspace_id = ? AND user_id = ?'
            );
            $stmt->execute([$role, $workspaceId, $memberId]);

            $this->pdo->commit();
            $this->audit->log('workspaces.member.role_changed', $actorId, [
                'workspace_id' => $workspaceId,
                'member_id' => $memberId,
                'role' => $role,
            ]);
            return true;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function listInvites(int $workspaceId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, email, workspace_role AS role, expires_at, created_at, accepted_at
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
            'INSERT INTO workspace_invites (workspace_id, email, workspace_role, token_hash, expires_at, created_at)
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

    public function resendInvite(int $inviteId, int $workspaceId, int $actorId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, email, workspace_role AS role, accepted_at FROM workspace_invites WHERE id = ? AND workspace_id = ? LIMIT 1'
        );
        $stmt->execute([$inviteId, $workspaceId]);
        $invite = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$invite || !empty($invite['accepted_at'])) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $now = new DateTimeImmutable('now');
        $expires = $now->modify('+7 days');

        $update = $this->pdo->prepare(
            'UPDATE workspace_invites
             SET token_hash = ?, expires_at = ?, created_at = ?, accepted_at = NULL
             WHERE id = ?'
        );
        $update->execute([
            $tokenHash,
            $expires->format('Y-m-d H:i:s'),
            $now->format('Y-m-d H:i:s'),
            (int)$invite['id'],
        ]);

        $this->audit->log('workspaces.invite.resent', $actorId, [
            'workspace_id' => $workspaceId,
            'invite_id' => (int)$invite['id'],
            'email' => $invite['email'] ?? '',
            'role' => $invite['role'] ?? '',
        ]);

        return [
            'token' => $token,
            'email' => $invite['email'] ?? '',
            'role' => $invite['role'] ?? '',
        ];
    }

    public function lookupInvite(string $token): ?array
    {
        $tokenHash = hash('sha256', $token);
        $stmt = $this->pdo->prepare(
            'SELECT i.id, i.workspace_id, i.email, i.workspace_role AS role, i.expires_at, i.accepted_at, w.name AS workspace_name
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
                'INSERT INTO workspace_memberships (user_id, workspace_id, workspace_role, created_at) VALUES (?, ?, ?, ?)'
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
            'Workspace Member' => 1,
            'Admin' => 2,
            'Workspace Admin' => 2,
            'Owner' => 3,
            'Workspace Owner' => 3,
        ];

        return ($weights[$role] ?? 0) >= ($weights[$required] ?? 0);
    }

    private function defaultWorkspaceName(string $userName): string
    {
        $userName = trim($userName);
        if ($userName === '') {
            return 'My Workspace';
        }

        $first = $userName;
        $spacePos = strpos($userName, ' ');
        if ($spacePos !== false) {
            $first = substr($userName, 0, $spacePos);
        }
        $first = substr($first, 0, 10);

        return $first . "'s Workspace";
    }
}
