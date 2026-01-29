<?php

declare(strict_types=1);

final class CreditLimitsController
{
    private PDO $pdo;
    private WorkspaceService $workspaces;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->workspaces = new WorkspaceService($pdo, new Audit($pdo));
    }

    public function index(Request $request): Response
    {
        $workspaceId = $this->workspaceId();
        if ($workspaceId <= 0) {
            return Response::redirect('/teams');
        }

        $limits = $this->fetchLimits($workspaceId);
        $flash = $_SESSION['flash'] ?? null;
        if ($flash) {
            unset($_SESSION['flash']);
        }

        return Response::html(View::render('settings/credit_limits', [
            'title' => 'Credit Limits',
            'limits' => $limits,
            'message' => $flash['message'] ?? null,
            'error' => $flash['error'] ?? null,
        ]));
    }

    public function save(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $workspaceId = $this->workspaceId();
        if ($workspaceId <= 0) {
            return Response::redirect('/teams');
        }

        $daily = max(0, (int)$request->input('daily_limit', 0));
        $monthly = max(0, (int)$request->input('monthly_limit', 0));
        $threshold = (int)$request->input('alert_threshold_percent', 80);
        if ($threshold < 1) {
            $threshold = 1;
        }
        if ($threshold > 100) {
            $threshold = 100;
        }

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $this->pdo->prepare(
                'INSERT INTO workspace_credit_limits (workspace_id, daily_limit, monthly_limit, alert_threshold_percent, last_alert_sent_at)
                 VALUES (?, ?, ?, ?, NULL)
                 ON CONFLICT(workspace_id) DO UPDATE SET daily_limit = excluded.daily_limit, monthly_limit = excluded.monthly_limit,
                 alert_threshold_percent = excluded.alert_threshold_percent'
            );
            $stmt->execute([$workspaceId, $daily, $monthly, $threshold]);
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO workspace_credit_limits (workspace_id, daily_limit, monthly_limit, alert_threshold_percent, last_alert_sent_at)
                 VALUES (?, ?, ?, ?, NULL)
                 ON DUPLICATE KEY UPDATE daily_limit = VALUES(daily_limit), monthly_limit = VALUES(monthly_limit),
                 alert_threshold_percent = VALUES(alert_threshold_percent)'
            );
            $stmt->execute([$workspaceId, $daily, $monthly, $threshold]);
        }

        $_SESSION['flash'] = ['message' => 'Credit limits updated.'];
        return Response::redirect('/settings/credit-limits');
    }

    private function workspaceId(): int
    {
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $workspace = $this->workspaces->ensureCurrentWorkspace($userId);
        return (int)($workspace['id'] ?? 0);
    }

    private function fetchLimits(int $workspaceId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT daily_limit, monthly_limit, alert_threshold_percent
             FROM workspace_credit_limits
             WHERE workspace_id = ?
             LIMIT 1'
        );
        $stmt->execute([$workspaceId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'daily_limit' => (int)($row['daily_limit'] ?? 0),
            'monthly_limit' => (int)($row['monthly_limit'] ?? 0),
            'alert_threshold_percent' => (int)($row['alert_threshold_percent'] ?? 80),
        ];
    }
}
