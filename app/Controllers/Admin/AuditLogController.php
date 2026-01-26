<?php

declare(strict_types=1);

final class AuditLogController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function index(Request $request): Response
    {
        $userId = (int)($request->session('user')['id'] ?? 0);
        if ($userId <= 0) {
            return Response::html(View::render('admin/audit/index', [
                'title' => 'Audit Log',
                'logs' => [],
            ]));
        }

        $membershipStmt = $this->pdo->prepare(
            'SELECT workspace_id FROM workspace_memberships
             WHERE user_id = ? AND workspace_role IN ("Workspace Admin", "Workspace Owner")'
        );
        $membershipStmt->execute([$userId]);
        $workspaceIds = array_map('intval', $membershipStmt->fetchAll(PDO::FETCH_COLUMN) ?: []);

        if (empty($workspaceIds)) {
            return Response::html(View::render('admin/audit/index', [
                'title' => 'Audit Log',
                'logs' => [],
            ]));
        }

        $actionFilter = trim((string)$request->query('action', ''));
        $startDate = trim((string)$request->query('start', ''));
        $endDate = trim((string)$request->query('end', ''));

        $placeholders = implode(',', array_fill(0, count($workspaceIds), '?'));
        $conditions = ['a.workspace_id IN (' . $placeholders . ')'];
        $params = $workspaceIds;

        if ($actionFilter !== '') {
            $conditions[] = 'a.action = ?';
            $params[] = $actionFilter;
        }
        if ($startDate !== '') {
            $conditions[] = 'a.created_at >= ?';
            $params[] = $startDate . ' 00:00:00';
        }
        if ($endDate !== '') {
            $conditions[] = 'a.created_at <= ?';
            $params[] = $endDate . ' 23:59:59';
        }

        $limit = 25;
        $page = max(1, (int)$request->query('page', 1));
        $offset = ($page - 1) * $limit;

        $countSql = sprintf(
            'SELECT COUNT(*) FROM audit_logs a WHERE %s',
            implode(' AND ', $conditions)
        );
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();
        $totalPages = max(1, (int)ceil($total / $limit));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $limit;

        $sql = sprintf(
            'SELECT a.id, a.action, a.metadata, a.created_at, u.email
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE %s
             ORDER BY a.created_at DESC
             LIMIT %d OFFSET %d',
            implode(' AND ', $conditions),
            $limit,
            $offset
        );
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $actionSql = sprintf(
            'SELECT DISTINCT a.action
             FROM audit_logs a
             WHERE a.workspace_id IN (%s)
             ORDER BY a.action ASC',
            $placeholders
        );
        $actionStmt = $this->pdo->prepare($actionSql);
        $actionStmt->execute($workspaceIds);
        $actions = $actionStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        return Response::html(View::render('admin/audit/index', [
            'title' => 'Audit Log',
            'logs' => $logs,
            'actions' => $actions,
            'filters' => [
                'action' => $actionFilter,
                'start' => $startDate,
                'end' => $endDate,
            ],
            'pagination' => [
                'page' => $page,
                'total' => $total,
                'totalPages' => $totalPages,
                'limit' => $limit,
            ],
        ]));
    }
}
