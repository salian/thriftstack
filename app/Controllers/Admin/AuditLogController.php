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
        $stmt = $this->pdo->query(
            'SELECT a.id, a.action, a.metadata, a.created_at, u.email
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.created_at DESC
             LIMIT 200'
        );
        $logs = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        return Response::html(View::render('admin/audit/index', [
            'title' => 'Audit Log',
            'logs' => $logs,
        ]));
    }
}
