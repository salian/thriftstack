<?php

declare(strict_types=1);

final class UsersController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function index(Request $request): Response
    {
        $workspaceService = new WorkspaceService($this->pdo);
        $userId = (int)($request->session('user')['id'] ?? 0);
        $workspaces = $workspaceService->listForUser($userId);

        $eligibleWorkspaces = [];
        foreach ($workspaces as $workspace) {
            $role = (string)($workspace['role'] ?? '');
            if ($workspaceService->isRoleAtLeast($role, 'Admin')) {
                $eligibleWorkspaces[] = $workspace;
            }
        }

        $selectedWorkspace = (string)$request->query('workspace_id', 'all');
        $search = trim((string)$request->query('search', ''));
        $page = max(1, (int)$request->query('page', 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $workspaceIds = array_map(static function ($workspace): int {
            return (int)$workspace['id'];
        }, $eligibleWorkspaces);

        $users = [];
        $total = 0;
        if (!empty($workspaceIds)) {
            $filters = [];
            $params = [];

            if ($selectedWorkspace !== 'all') {
                $selectedId = (int)$selectedWorkspace;
                if (in_array($selectedId, $workspaceIds, true)) {
                    $filters[] = 'wm.workspace_id = ?';
                    $params[] = $selectedId;
                }
            } else {
                $placeholders = implode(',', array_fill(0, count($workspaceIds), '?'));
                $filters[] = 'wm.workspace_id IN (' . $placeholders . ')';
                $params = array_merge($params, $workspaceIds);
            }

            if ($search !== '') {
                $filters[] = '(u.name LIKE ? OR u.email LIKE ?)';
                $like = '%' . $search . '%';
                $params[] = $like;
                $params[] = $like;
            }

            $where = $filters ? 'WHERE ' . implode(' AND ', $filters) : '';

            $countSql = 'SELECT COUNT(DISTINCT u.id)
                FROM users u
                JOIN workspace_memberships wm ON wm.user_id = u.id
                ' . $where;
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();

            $listSql = 'SELECT u.id, u.name, u.email, u.status, u.created_at,
                    (SELECT r.name
                     FROM user_roles ur
                     JOIN roles r ON r.id = ur.role_id
                     WHERE ur.user_id = u.id
                     LIMIT 1) AS role
                FROM users u
                JOIN workspace_memberships wm ON wm.user_id = u.id
                ' . $where . '
                GROUP BY u.id
                ORDER BY u.created_at DESC
                LIMIT ? OFFSET ?';
            $stmt = $this->pdo->prepare($listSql);
            $bindIndex = 1;
            foreach ($params as $param) {
                $stmt->bindValue($bindIndex, $param);
                $bindIndex++;
            }
            $stmt->bindValue($bindIndex, $perPage, PDO::PARAM_INT);
            $stmt->bindValue($bindIndex + 1, $offset, PDO::PARAM_INT);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        return Response::html(View::render('admin/users/index', [
            'title' => 'Users',
            'users' => $users,
            'workspaces' => $eligibleWorkspaces,
            'selectedWorkspace' => $selectedWorkspace,
            'search' => $search,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
        ]));
    }
}
