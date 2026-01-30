<?php

declare(strict_types=1);

final class WorkspaceAnalyticsController
{
    private PDO $pdo;
    private WorkspaceService $workspaces;
    private WorkspaceSettingsService $settings;

    public function __construct(PDO $pdo, WorkspaceSettingsService $settings)
    {
        $this->pdo = $pdo;
        $this->workspaces = new WorkspaceService($pdo, new Audit($pdo));
        $this->settings = $settings;
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

        [$startDate, $endDate, $start, $end] = $this->resolveDateRange($request);
        $trend = $this->fetchDailyTrends($workspaceId, $startDate, $endDate);
        $usageTotals = $this->fetchUsageByType($workspaceId, $startDate, $endDate);
        $usageTypeTrend = $this->usageTypeTrend($workspaceId, $startDate, $endDate);
        $heatmap = $this->usageHeatmap($workspaceId, $startDate, $endDate);
        $anomalies = $this->detectAnomalies($workspaceId);
        $segments = $this->segmentUsers($workspaceId, $startDate, $endDate);
        $drilldown = $this->drilldownResults($workspaceId, $startDate, $endDate, $request);
        $workspaceSettings = $this->settings->getSettings($workspaceId);
        $alertSettings = $workspaceSettings['analytics']['velocity'] ?? [
            'enabled' => false,
            'threshold_percent' => 50,
        ];
        $totalConsumed = 0;
        foreach ($usageTotals as $row) {
            $totalConsumed += (int)($row['credits'] ?? 0);
        }

        $burnRate = $this->calculateBurnRate($workspaceId, $startDate, $endDate);
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
            'start' => $start,
            'end' => $end,
            'usageTypeTrend' => $usageTypeTrend,
            'heatmap' => $heatmap,
            'anomalies' => $anomalies,
            'segments' => $segments,
            'drilldown' => $drilldown,
            'alertSettings' => $alertSettings,
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

        [$startDate, $endDate] = $this->resolveDateRange($request);
        $rows = $this->fetchDailyTrends($workspaceId, $startDate, $endDate, true);
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

    public function updateVelocityAlerts(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $userId = (int)($request->session('user')['id'] ?? 0);
        $workspace = $this->workspaces->ensureCurrentWorkspace($userId);
        if (!$workspace) {
            return Response::redirect('/teams');
        }
        $workspaceId = (int)$workspace['id'];

        $enabled = $request->input('velocity_alert_enabled') === '1';
        $threshold = (int)$request->input('velocity_threshold_percent', 50);
        if ($threshold < 10) {
            $threshold = 10;
        }
        if ($threshold > 500) {
            $threshold = 500;
        }

        $settings = $this->settings->getSettings($workspaceId);
        if (!isset($settings['analytics'])) {
            $settings['analytics'] = [];
        }
        $settings['analytics']['velocity'] = [
            'enabled' => $enabled,
            'threshold_percent' => $threshold,
        ];
        $this->settings->saveSettings($workspaceId, $settings);

        return Response::redirect('/workspace-admin/analytics/credits');
    }

    /**
     * @return array{0:DateTimeImmutable,1:DateTimeImmutable,2:string,3:string}
     */
    private function resolveDateRange(Request $request): array
    {
        $startParam = trim((string)$request->query('start', ''));
        $endParam = trim((string)$request->query('end', ''));
        $endDate = $endParam !== '' ? DateTimeImmutable::createFromFormat('Y-m-d', $endParam) : new DateTimeImmutable('today');
        $startDate = $startParam !== '' ? DateTimeImmutable::createFromFormat('Y-m-d', $startParam) : $endDate->modify('-29 days');

        if (!$endDate) {
            $endDate = new DateTimeImmutable('today');
        }
        if (!$startDate) {
            $startDate = $endDate->modify('-29 days');
        }
        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [
            $startDate->setTime(0, 0, 0),
            $endDate->setTime(23, 59, 59),
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
        ];
    }

    private function fetchDailyTrends(
        int $workspaceId,
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        bool $includeZeros = false
    ): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT DATE(created_at) AS date, COALESCE(usage_type, "unknown") AS usage_type, SUM(-credits) AS credits
             FROM workspace_credit_ledger
             WHERE workspace_id = ? AND change_type = "consume" AND created_at >= ? AND created_at <= ?
             GROUP BY DATE(created_at), usage_type
             ORDER BY DATE(created_at) ASC'
        );
        $stmt->execute([$workspaceId, $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if ($includeZeros) {
            return $rows;
        }
        return array_filter($rows, static fn($row) => (int)($row['credits'] ?? 0) > 0);
    }

    private function fetchUsageByType(int $workspaceId, DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(usage_type, "unknown") AS usage_type, SUM(-credits) AS credits
             FROM workspace_credit_ledger
             WHERE workspace_id = ? AND change_type = "consume" AND created_at >= ? AND created_at <= ?
             GROUP BY usage_type
             ORDER BY credits DESC'
        );
        $stmt->execute([$workspaceId, $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function calculateBurnRate(int $workspaceId, DateTimeImmutable $start, DateTimeImmutable $end): float
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(SUM(-credits), 0) AS credits
             FROM workspace_credit_ledger
             WHERE workspace_id = ? AND change_type = "consume" AND created_at >= ? AND created_at <= ?'
        );
        $stmt->execute([$workspaceId, $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]);
        $total = (int)$stmt->fetchColumn();
        $days = max(1, (int)$end->diff($start)->days + 1);
        return $total / $days;
    }

    private function usageTypeTrend(int $workspaceId, DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $dateExpr = $driver === 'sqlite' ? "strftime('%Y-%m-%d', created_at)" : 'DATE(created_at)';
        $stmt = $this->pdo->prepare(
            "SELECT {$dateExpr} AS date, COALESCE(usage_type, 'unknown') AS usage_type, SUM(-credits) AS credits
             FROM workspace_credit_ledger
             WHERE workspace_id = ? AND change_type = 'consume' AND created_at >= ? AND created_at <= ?
             GROUP BY {$dateExpr}, usage_type
             ORDER BY {$dateExpr} ASC"
        );
        $stmt->execute([$workspaceId, $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function usageHeatmap(int $workspaceId, DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $dayExpr = "CAST(strftime('%w', created_at) AS INTEGER)";
            $hourExpr = "CAST(strftime('%H', created_at) AS INTEGER)";
        } else {
            $dayExpr = 'DAYOFWEEK(created_at) - 1';
            $hourExpr = 'HOUR(created_at)';
        }

        $stmt = $this->pdo->prepare(
            "SELECT {$dayExpr} AS day_of_week, {$hourExpr} AS hour_of_day, SUM(-credits) AS credits
             FROM workspace_credit_ledger
             WHERE workspace_id = ? AND change_type = 'consume' AND created_at >= ? AND created_at <= ?
             GROUP BY day_of_week, hour_of_day"
        );
        $stmt->execute([$workspaceId, $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $grid = array_fill(0, 7, array_fill(0, 24, 0));
        $max = 0;
        foreach ($rows as $row) {
            $day = (int)$row['day_of_week'];
            $hour = (int)$row['hour_of_day'];
            $value = (int)$row['credits'];
            if ($day >= 0 && $day < 7 && $hour >= 0 && $hour < 24) {
                $grid[$day][$hour] = $value;
                if ($value > $max) {
                    $max = $value;
                }
            }
        }

        return [
            'grid' => $grid,
            'max' => $max,
        ];
    }

    private function detectAnomalies(int $workspaceId): array
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $dateExpr = $driver === 'sqlite' ? "strftime('%Y-%m-%d', created_at)" : 'DATE(created_at)';
        $stmt = $this->pdo->prepare(
            "SELECT {$dateExpr} AS date, SUM(-credits) AS credits
             FROM workspace_credit_ledger
             WHERE workspace_id = ? AND change_type = 'consume' AND created_at >= ?
             GROUP BY {$dateExpr}
             ORDER BY {$dateExpr} ASC"
        );
        $since = (new DateTimeImmutable('today'))->modify('-30 days')->format('Y-m-d');
        $stmt->execute([$workspaceId, $since . ' 00:00:00']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $values = array_map(static fn($row): int => (int)$row['credits'], $rows);
        if (count($values) < 5) {
            return [];
        }

        $mean = array_sum($values) / count($values);
        $variance = 0.0;
        foreach ($values as $value) {
            $variance += ($value - $mean) ** 2;
        }
        $std = sqrt($variance / count($values));

        $anomalies = [];
        for ($i = 7; $i < count($rows); $i++) {
            $window = array_slice($values, $i - 7, 7);
            $rolling = array_sum($window) / count($window);
            $current = $values[$i];
            if ($current > max(1, $rolling) * 2 && $current > ($mean + (2 * $std))) {
                $anomalies[] = [
                    'date' => $rows[$i]['date'],
                    'credits' => $current,
                    'rolling_avg' => round($rolling, 2),
                    'mean' => round($mean, 2),
                    'std' => round($std, 2),
                ];
            }
        }

        return $anomalies;
    }

    private function segmentUsers(int $workspaceId, DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT user_id, SUM(-credits) AS credits
             FROM workspace_credit_ledger
             WHERE workspace_id = ? AND change_type = "consume" AND user_id IS NOT NULL
               AND created_at >= ? AND created_at <= ?
             GROUP BY user_id
             ORDER BY credits DESC'
        );
        $stmt->execute([$workspaceId, $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $totalCount = count($rows);
        if ($totalCount === 0) {
            return [
                'high' => ['count' => 0, 'credits' => 0],
                'medium' => ['count' => 0, 'credits' => 0],
                'low' => ['count' => 0, 'credits' => 0],
            ];
        }

        $highCount = (int)ceil($totalCount * 0.2);
        $mediumCount = (int)ceil($totalCount * 0.5);

        $high = array_slice($rows, 0, $highCount);
        $medium = array_slice($rows, $highCount, $mediumCount);
        $low = array_slice($rows, $highCount + $mediumCount);

        return [
            'high' => ['count' => count($high), 'credits' => array_sum(array_column($high, 'credits'))],
            'medium' => ['count' => count($medium), 'credits' => array_sum(array_column($medium, 'credits'))],
            'low' => ['count' => count($low), 'credits' => array_sum(array_column($low, 'credits'))],
        ];
    }

    private function drilldownResults(int $workspaceId, DateTimeImmutable $start, DateTimeImmutable $end, Request $request): array
    {
        $filters = [
            'start' => (string)$request->query('drill_start', $start->format('Y-m-d')),
            'end' => (string)$request->query('drill_end', $end->format('Y-m-d')),
            'usage_type' => trim((string)$request->query('drill_usage_type', '')),
            'user' => trim((string)$request->query('drill_user', '')),
            'page' => max(1, (int)$request->query('drill_page', 1)),
        ];
        $limit = 25;
        $offset = ($filters['page'] - 1) * $limit;

        $conditions = ['wcl.change_type = "consume"', 'wcl.workspace_id = ?'];
        $params = [$workspaceId];
        if ($filters['start'] !== '') {
            $conditions[] = 'wcl.created_at >= ?';
            $params[] = $filters['start'] . ' 00:00:00';
        }
        if ($filters['end'] !== '') {
            $conditions[] = 'wcl.created_at <= ?';
            $params[] = $filters['end'] . ' 23:59:59';
        }
        if ($filters['usage_type'] !== '') {
            $conditions[] = 'wcl.usage_type = ?';
            $params[] = $filters['usage_type'];
        }
        if ($filters['user'] !== '') {
            if (filter_var($filters['user'], FILTER_VALIDATE_EMAIL)) {
                $conditions[] = 'u.email LIKE ?';
                $params[] = '%' . $filters['user'] . '%';
            } else {
                $conditions[] = 'u.name LIKE ?';
                $params[] = '%' . $filters['user'] . '%';
            }
        }

        $where = 'WHERE ' . implode(' AND ', $conditions);
        $countStmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM workspace_credit_ledger wcl
             LEFT JOIN users u ON u.id = wcl.user_id
             {$where}"
        );
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();
        $totalPages = max(1, (int)ceil($total / $limit));
        $filters['page'] = min($filters['page'], $totalPages);
        $offset = ($filters['page'] - 1) * $limit;

        $stmt = $this->pdo->prepare(
            "SELECT wcl.created_at, wcl.usage_type, wcl.credits, wcl.metadata,
                    u.name AS user_name, u.email AS user_email
             FROM workspace_credit_ledger wcl
             LEFT JOIN users u ON u.id = wcl.user_id
             {$where}
             ORDER BY wcl.created_at DESC
             LIMIT {$limit} OFFSET {$offset}"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $usageTypes = $this->pdo->prepare(
            'SELECT DISTINCT usage_type FROM workspace_credit_ledger WHERE workspace_id = ? AND usage_type IS NOT NULL ORDER BY usage_type ASC'
        );
        $usageTypes->execute([$workspaceId]);
        $usageTypeOptions = $usageTypes->fetchAll(PDO::FETCH_COLUMN) ?: [];

        return [
            'filters' => $filters,
            'rows' => $rows,
            'total' => $total,
            'total_pages' => $totalPages,
            'usage_types' => $usageTypeOptions,
        ];
    }
}
