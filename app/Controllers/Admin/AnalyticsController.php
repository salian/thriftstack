<?php

declare(strict_types=1);

final class AnalyticsController
{
    private PDO $pdo;
    private AppSettingsService $settings;

    public function __construct(PDO $pdo, AppSettingsService $settings)
    {
        $this->pdo = $pdo;
        $this->settings = $settings;
    }

    public function index(Request $request): Response
    {
        $user = $request->session('user');
        $isAdmin = is_array($user) ? (int)($user['is_system_admin'] ?? 0) === 1 : false;

        [$startDate, $endDate] = $this->dateRange($request);
        $usageTypeTrend = $this->usageTypeTrend($startDate, $endDate);
        $heatmap = $this->usageHeatmap($startDate, $endDate);
        $anomalies = $this->detectAnomalies();
        $segments = $this->segmentUsage($startDate, $endDate);
        $drilldown = $this->drilldownResults($request, $startDate, $endDate);
        $alertSettings = [
            'enabled' => $this->settings->get('analytics.velocity.enabled', '0') === '1',
            'threshold_percent' => (int)$this->settings->get('analytics.velocity.threshold_percent', '50'),
        ];

        $kpis = [
            ['label' => 'Monthly Active Users', 'value' => '1,284', 'delta' => '+8%'],
            ['label' => 'Active Workspaces', 'value' => '214', 'delta' => '+3%'],
            ['label' => 'MRR', 'value' => '$12,480', 'delta' => '+5%'],
            ['label' => 'Churn', 'value' => '2.4%', 'delta' => '-0.4%'],
        ];

        $charts = [
            [
                'title' => 'Usage over time',
                'description' => 'Active users and sessions by week.',
            ],
            [
                'title' => 'Revenue by plan',
                'description' => 'MRR split by plan tier and trial conversions.',
            ],
            [
                'title' => 'Workspace retention',
                'description' => 'New vs retained workspaces by cohort.',
            ],
        ];

        if (!$isAdmin) {
            $kpis = array_values(array_filter($kpis, static function (array $kpi): bool {
                return $kpi['label'] !== 'MRR';
            }));
            $charts = array_values(array_filter($charts, static function (array $chart): bool {
                return $chart['title'] !== 'Revenue by plan';
            }));
        }

        $futureSources = [
            'Billing providers (Stripe, Razorpay, PayPal, Lemon Squeezy)',
            'Workspace activity events and feature usage',
            'Audit logs and notification delivery metrics',
        ];

        return Response::html(View::render('admin/analytics/index', [
            'title' => 'App Analytics',
            'kpis' => $kpis,
            'charts' => $charts,
            'futureSources' => $futureSources,
            'showRevenue' => $isAdmin,
            'start' => $startDate,
            'end' => $endDate,
            'usageTypeTrend' => $usageTypeTrend,
            'heatmap' => $heatmap,
            'anomalies' => $anomalies,
            'segments' => $segments,
            'drilldown' => $drilldown,
            'alertSettings' => $alertSettings,
        ]));
    }

    /**
     * @return array{0:string,1:string}
     */
    private function dateRange(Request $request): array
    {
        $startParam = trim((string)$request->query('start', ''));
        $endParam = trim((string)$request->query('end', ''));
        $end = $endParam !== '' ? DateTimeImmutable::createFromFormat('Y-m-d', $endParam) : new DateTimeImmutable('today');
        $start = $startParam !== '' ? DateTimeImmutable::createFromFormat('Y-m-d', $startParam) : $end->modify('-29 days');
        if (!$end) {
            $end = new DateTimeImmutable('today');
        }
        if (!$start) {
            $start = $end->modify('-29 days');
        }
        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        return [$start->format('Y-m-d'), $end->format('Y-m-d')];
    }

    private function usageTypeTrend(string $start, string $end): array
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $dateExpr = $driver === 'sqlite' ? "strftime('%Y-%m-%d', created_at)" : 'DATE(created_at)';
        $stmt = $this->pdo->prepare(
            "SELECT {$dateExpr} AS date, COALESCE(usage_type, 'unknown') AS usage_type, SUM(-credits) AS credits
             FROM workspace_credit_ledger
             WHERE change_type = 'consume' AND created_at >= ? AND created_at <= ?
             GROUP BY {$dateExpr}, usage_type
             ORDER BY {$dateExpr} ASC"
        );
        $stmt->execute([$start . ' 00:00:00', $end . ' 23:59:59']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function usageHeatmap(string $start, string $end): array
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
             WHERE change_type = 'consume' AND created_at >= ? AND created_at <= ?
             GROUP BY day_of_week, hour_of_day"
        );
        $stmt->execute([$start . ' 00:00:00', $end . ' 23:59:59']);
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

    private function detectAnomalies(): array
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $dateExpr = $driver === 'sqlite' ? "strftime('%Y-%m-%d', created_at)" : 'DATE(created_at)';
        $stmt = $this->pdo->prepare(
            "SELECT {$dateExpr} AS date, SUM(-credits) AS credits
             FROM workspace_credit_ledger
             WHERE change_type = 'consume' AND created_at >= ?
             GROUP BY {$dateExpr}
             ORDER BY {$dateExpr} ASC"
        );
        $since = (new DateTimeImmutable('today'))->modify('-30 days')->format('Y-m-d');
        $stmt->execute([$since . ' 00:00:00']);
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

    private function segmentUsage(string $start, string $end): array
    {
        $userSegments = $this->segmentByEntity(
            'user_id',
            'users',
            'u.id = wcl.user_id',
            $start,
            $end
        );

        $workspaceSegments = $this->segmentByEntity(
            'workspace_id',
            'workspaces',
            'w.id = wcl.workspace_id',
            $start,
            $end
        );

        return [
            'users' => $userSegments,
            'workspaces' => $workspaceSegments,
        ];
    }

    private function segmentByEntity(string $field, string $table, string $join, string $start, string $end): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT wcl.{$field} AS entity_id, SUM(-wcl.credits) AS credits
             FROM workspace_credit_ledger wcl
             JOIN {$table} AS w ON {$join}
             WHERE wcl.change_type = 'consume' AND wcl.{$field} IS NOT NULL
               AND wcl.created_at >= ? AND wcl.created_at <= ?
             GROUP BY wcl.{$field}
             ORDER BY credits DESC"
        );
        $stmt->execute([$start . ' 00:00:00', $end . ' 23:59:59']);
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
        $lowCount = max(0, $totalCount - $highCount - $mediumCount);

        $high = array_slice($rows, 0, $highCount);
        $medium = array_slice($rows, $highCount, $mediumCount);
        $low = array_slice($rows, $highCount + $mediumCount);

        return [
            'high' => ['count' => count($high), 'credits' => array_sum(array_column($high, 'credits'))],
            'medium' => ['count' => count($medium), 'credits' => array_sum(array_column($medium, 'credits'))],
            'low' => ['count' => count($low), 'credits' => array_sum(array_column($low, 'credits'))],
        ];
    }

    private function drilldownResults(Request $request, string $start, string $end): array
    {
        $filters = [
            'start' => (string)$request->query('drill_start', $start),
            'end' => (string)$request->query('drill_end', $end),
            'usage_type' => trim((string)$request->query('drill_usage_type', '')),
            'workspace' => trim((string)$request->query('drill_workspace', '')),
            'user' => trim((string)$request->query('drill_user', '')),
            'page' => max(1, (int)$request->query('drill_page', 1)),
        ];
        $limit = 25;
        $offset = ($filters['page'] - 1) * $limit;

        $conditions = ['wcl.change_type = "consume"'];
        $params = [];
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
        if ($filters['workspace'] !== '') {
            $conditions[] = 'w.name LIKE ?';
            $params[] = '%' . $filters['workspace'] . '%';
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
             LEFT JOIN workspaces w ON w.id = wcl.workspace_id
             {$where}"
        );
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();
        $totalPages = max(1, (int)ceil($total / $limit));
        $filters['page'] = min($filters['page'], $totalPages);
        $offset = ($filters['page'] - 1) * $limit;

        $stmt = $this->pdo->prepare(
            "SELECT wcl.created_at, wcl.usage_type, wcl.credits, wcl.metadata,
                    w.name AS workspace_name, u.name AS user_name, u.email AS user_email
             FROM workspace_credit_ledger wcl
             LEFT JOIN users u ON u.id = wcl.user_id
             LEFT JOIN workspaces w ON w.id = wcl.workspace_id
             {$where}
             ORDER BY wcl.created_at DESC
             LIMIT {$limit} OFFSET {$offset}"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $usageTypes = $this->pdo->query('SELECT DISTINCT usage_type FROM workspace_credit_ledger WHERE usage_type IS NOT NULL ORDER BY usage_type ASC');
        $usageTypeOptions = $usageTypes ? array_values(array_filter($usageTypes->fetchAll(PDO::FETCH_COLUMN) ?: [])) : [];

        return [
            'filters' => $filters,
            'rows' => $rows,
            'total' => $total,
            'total_pages' => $totalPages,
            'usage_types' => $usageTypeOptions,
        ];
    }
}
