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
             WHERE user_id = ? AND role IN ("Admin", "Owner")'
        );
        $membershipStmt->execute([$userId]);
        $workspaceIds = array_map('intval', $membershipStmt->fetchAll(PDO::FETCH_COLUMN) ?: []);

        if (empty($workspaceIds)) {
            return Response::html(View::render('admin/audit/index', [
                'title' => 'Audit Log',
                'logs' => [],
            ]));
        }

        $placeholders = implode(',', array_fill(0, count($workspaceIds), '?'));
        $sql = sprintf(
            'SELECT a.id, a.action, a.metadata, a.created_at, u.email
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE a.workspace_id IN (%s)
             ORDER BY a.created_at DESC
             LIMIT 200',
            $placeholders
        );
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($workspaceIds);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return Response::html(View::render('admin/audit/index', [
            'title' => 'Audit Log',
            'logs' => $logs,
        ]));
    }
}
