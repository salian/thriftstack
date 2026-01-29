<?php

declare(strict_types=1);

final class WorkspaceAnalyticsController
{
    private PDO $pdo;
    private WorkspaceService $workspaces;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->workspaces = new WorkspaceService($pdo, new Audit($pdo));
    }

    public function credits(Request $request): Response
    {
        $userId = (int)($request->session('user')['id'] ?? 0);
        $workspace = $this->workspaces->ensureCurrentWorkspace($userId);
        if (!$workspace) {
            return Response::redirect('/teams');
        }

        $workspaceId = (int)$workspace['id'];
        $balance = (int)($workspace['ai_credit_balance'] ?? 0);

        $trend = $this->fetchDailyTrends($workspaceId);
        $usageTotals = $this->fetchUsageByType($workspaceId);
        $totalConsumed = 0;
        foreach ($usageTotals as $row) {
            $totalConsumed += (int)($row['credits'] ?? 0);
        }

        $burnRate = $this->calculateBurnRate($workspaceId);
        $daysRemaining = $burnRate > 0 ? (int)floor($balance / $burnRate) : null;
        $depletionDate = $daysRemaining !== null ? (new DateTimeImmutable('now'))->modify('+' . $daysRemaining . ' days')->format('Y-m-d') : null;

        return Response::html(View::render('workspace_admin/analytics/credits', [
            'title' => 'Credits Analytics',
            'workspace' => $workspace,
            'trend' => $trend,
            'usageTotals' => $usageTotals,
            'totalConsumed' => $totalConsumed,
            'currentBalance' => $balance,
            'burnRate' => $burnRate,
            'depletionDate' => $depletionDate,
        ]));
    }

    public function exportCreditsUsage(Request $request): Response
    {
        $userId = (int)($request->session('user')['id'] ?? 0);
        $workspace = $this->workspaces->ensureCurrentWorkspace($userId);
        if (!$workspace) {
            return Response::redirect('/teams');
        }
        $workspaceId = (int)$workspace['id'];

        $rows = $this->fetchDailyTrends($workspaceId, true);
        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['date', 'usage_type', 'credits', 'cumulative']);

        $cumulative = [];
        foreach ($rows as $row) {
            $type = $row['usage_type'] ?? 'unknown';
            $credits = (int)($row['credits'] ?? 0);
            $cumulative[$type] = ($cumulative[$type] ?? 0) + $credits;
            fputcsv($output, [$row['date'], $type, $credits, $cumulative[$type]]);
        }

        rewind($output);
        $csv = stream_get_contents($output) ?: '';
        fclose($output);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="credits-usage.csv"',
        ];

        return new Response($csv, 200, $headers);
    }

    private function fetchDailyTrends(int $workspaceId, bool $includeZeros = false): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT DATE(created_at) AS date, COALESCE(usage_type, "unknown") AS usage_type, SUM(-credits) AS credits
             FROM workspace_credit_ledger
             WHERE workspace_id = ? AND change_type = "consume"
             GROUP BY DATE(created_at), usage_type
             ORDER BY DATE(created_at) ASC'
        );
        $stmt->execute([$workspaceId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if ($includeZeros) {
            return $rows;
        }
        return array_filter($rows, static fn($row) => (int)($row['credits'] ?? 0) > 0);
    }

    private function fetchUsageByType(int $workspaceId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(usage_type, "unknown") AS usage_type, SUM(-credits) AS credits
             FROM workspace_credit_ledger
             WHERE workspace_id = ? AND change_type = "consume"
             GROUP BY usage_type
             ORDER BY credits DESC'
        );
        $stmt->execute([$workspaceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function calculateBurnRate(int $workspaceId): float
    {
        $since = (new DateTimeImmutable('now'))->modify('-30 days')->format('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(SUM(-credits), 0) AS credits
             FROM workspace_credit_ledger
             WHERE workspace_id = ? AND change_type = "consume" AND created_at >= ?'
        );
        $stmt->execute([$workspaceId, $since]);
        $total = (int)$stmt->fetchColumn();
        return $total / 30;
    }
}
