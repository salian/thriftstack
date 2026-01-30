<?php

declare(strict_types=1);

final class FinancialAnalyticsController
{
    private PDO $pdo;
    private AppSettingsService $settings;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->settings = new AppSettingsService($pdo);
    }

    public function index(Request $request): Response
    {
        [$months, $start, $end] = $this->dateRange($request);
        [$compareEnabled, $prevStart, $prevEnd] = $this->comparisonRange($request, $start, $end);
        $mrrTrend = [];
        $topupTrend = [];

        foreach ($months as $month) {
            $startAt = $month['start'];
            $endAt = $month['end'];
            $mrrTrend[] = [
                'month' => $month['label'],
                'mrr_cents' => $this->mrrForPeriod($startAt, $endAt),
            ];
            $topupTrend[] = [
                'month' => $month['label'],
                'topup_cents' => $this->topupForPeriod($startAt, $endAt),
            ];
        }

        $currentMrr = $this->mrrForPeriod($months[count($months) - 1]['start'], $months[count($months) - 1]['end']);
        $currentTopup = $topupTrend[count($topupTrend) - 1]['topup_cents'] ?? 0;
        $churnRate = $this->churnRateForPeriod($start, $end);
        $ltv = $this->ltvEstimate($currentMrr);

        $comparison = null;
        if ($compareEnabled && $prevStart && $prevEnd) {
            $prevMrr = $this->mrrForPeriod($prevStart . ' 00:00:00', $prevEnd . ' 23:59:59');
            $prevTopup = $this->topupForPeriod($prevStart . ' 00:00:00', $prevEnd . ' 23:59:59');
            $prevChurn = $this->churnRateForPeriod($prevStart, $prevEnd);
            $prevLtv = $this->ltvEstimate($prevMrr);
            $comparison = [
                'enabled' => true,
                'prevStart' => $prevStart,
                'prevEnd' => $prevEnd,
                'mrr' => $this->metricComparison($currentMrr, $prevMrr),
                'topup' => $this->metricComparison($currentTopup, $prevTopup),
                'churn' => $this->metricComparison($churnRate, $prevChurn),
                'ltv' => $this->metricComparison($ltv, $prevLtv),
            ];
        }

        return Response::html(View::render('admin/analytics/financial', [
            'title' => 'Financial Analytics',
            'mrrTrend' => $mrrTrend,
            'topupTrend' => $topupTrend,
            'currentMrr' => $currentMrr,
            'currentTopup' => $currentTopup,
            'churnRate' => $churnRate,
            'ltv' => $ltv,
            'start' => $start,
            'end' => $end,
            'comparison' => $comparison,
            'compareEnabled' => $compareEnabled,
        ]));
    }

    public function export(Request $request): Response
    {
        [$months] = $this->dateRange($request);
        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['month', 'mrr_cents', 'topup_revenue_cents', 'churn_rate_percent', 'ltv_cents']);

        $churnRate = $this->churnRate();
        $currentMrr = $this->mrrForPeriod($months[count($months) - 1]['start'], $months[count($months) - 1]['end']);
        $ltv = $this->ltvEstimate($currentMrr);

        foreach ($months as $month) {
            $mrr = $this->mrrForPeriod($month['start'], $month['end']);
            $topup = $this->topupForPeriod($month['start'], $month['end']);
            fputcsv($output, [$month['label'], $mrr, $topup, $churnRate, $ltv]);
        }

        rewind($output);
        $csv = stream_get_contents($output) ?: '';
        fclose($output);

        return new Response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="financial-analytics.csv"',
        ]);
    }

    private function mrrForPeriod(string $start, string $end): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.plan_id, s.status, s.created_at, s.canceled_at, p.price_cents, p.duration
             FROM subscriptions s
             JOIN plans p ON p.id = s.plan_id
             WHERE s.created_at <= ? AND (s.canceled_at IS NULL OR s.canceled_at >= ?)
               AND s.status IN ("active","trialing")'
        );
        $stmt->execute([$end, $start]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $total = 0.0;
        foreach ($rows as $row) {
            $price = (int)$row['price_cents'];
            $duration = strtolower((string)($row['duration'] ?? 'monthly'));
            $multiplier = in_array($duration, ['annual', 'yearly'], true) ? (1 / 12) : 1;
            $total += $price * $multiplier;
        }

        return (int)round($total);
    }

    private function topupForPeriod(string $start, string $end): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(SUM(amount_cents), 0)
             FROM ai_credit_purchases
             WHERE status = "paid" AND created_at >= ? AND created_at <= ?'
        );
        $stmt->execute([$start, $end]);
        return (int)$stmt->fetchColumn();
    }

    private function churnRateForPeriod(string $start, string $end): float
    {
        $cancelledStmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM subscriptions WHERE status = "canceled" AND canceled_at >= ? AND canceled_at <= ?'
        );
        $cancelledStmt->execute([$start . ' 00:00:00', $end . ' 23:59:59']);
        $cancelled = (int)$cancelledStmt->fetchColumn();

        $activeStmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM subscriptions WHERE status = "active" AND created_at <= ?'
        );
        $activeStmt->execute([$end . ' 23:59:59']);
        $active = (int)$activeStmt->fetchColumn();

        $denominator = $active + $cancelled;
        if ($denominator === 0) {
            return 0.0;
        }

        return round(($cancelled / $denominator) * 100, 2);
    }

    private function ltvEstimate(int $currentMrr): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT created_at, canceled_at, status FROM subscriptions'
        );
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if (empty($rows)) {
            return 0;
        }

        $totalMonths = 0.0;
        foreach ($rows as $row) {
            $start = new DateTimeImmutable((string)$row['created_at']);
            $end = !empty($row['canceled_at']) ? new DateTimeImmutable((string)$row['canceled_at']) : new DateTimeImmutable('now');
            $days = max(1, (int)$end->diff($start)->days);
            $totalMonths += $days / 30;
        }

        $avgMonths = $totalMonths / count($rows);
        $activeCountStmt = $this->pdo->prepare('SELECT COUNT(*) FROM subscriptions WHERE status = "active"');
        $activeCountStmt->execute();
        $activeCount = (int)$activeCountStmt->fetchColumn();
        $arpu = $activeCount > 0 ? $currentMrr / $activeCount : 0;

        return (int)round($avgMonths * $arpu);
    }

    /**
     * @return array{0:array<int,array{label:string,start:string,end:string}>,1:string,2:string}
     */
    private function dateRange(Request $request): array
    {
        $startParam = (string)$request->query('start', '');
        $endParam = (string)$request->query('end', '');
        $end = $this->parseDateParam($endParam) ?? new DateTimeImmutable('first day of this month');
        $start = $this->parseDateParam($startParam) ?? $end->modify('-11 months');
        if (!$end) {
            $end = new DateTimeImmutable('first day of this month');
        }
        if (!$start) {
            $start = $end->modify('-11 months');
        }

        $months = [];
        $cursor = $start;
        while ($cursor <= $end) {
            $label = $cursor->format('Y-m');
            $startAt = $cursor->format('Y-m-01 00:00:00');
            $endAt = $cursor->modify('last day of this month')->setTime(23, 59, 59)->format('Y-m-d H:i:s');
            $months[] = ['label' => $label, 'start' => $startAt, 'end' => $endAt];
            $cursor = $cursor->modify('first day of next month');
        }

        return [$months, $start->format('Y-m-d'), $end->format('Y-m-d')];
    }

    private function parseDateParam(string $value): ?DateTimeImmutable
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if ($date instanceof DateTimeImmutable) {
            return $date;
        }
        $date = DateTimeImmutable::createFromFormat('Y-m', $value);
        return $date instanceof DateTimeImmutable ? $date : null;
    }

    /**
     * @return array{0:bool,1:?string,2:?string}
     */
    private function comparisonRange(Request $request, string $start, string $end): array
    {
        $enabled = $request->query('compare', '') === '1';
        if (!$enabled) {
            return [false, null, null];
        }
        $prevStart = (string)$request->query('prev_start', '');
        $prevEnd = (string)$request->query('prev_end', '');
        if ($prevStart !== '' && $prevEnd !== '') {
            return [true, $prevStart, $prevEnd];
        }

        $startDate = DateTimeImmutable::createFromFormat('Y-m-d', $start) ?: new DateTimeImmutable($start);
        $endDate = DateTimeImmutable::createFromFormat('Y-m-d', $end) ?: new DateTimeImmutable($end);
        $rangeDays = max(1, (int)$endDate->diff($startDate)->days + 1);
        $prevEndDate = $startDate->modify('-1 day');
        $prevStartDate = $prevEndDate->modify('-' . ($rangeDays - 1) . ' days');

        return [true, $prevStartDate->format('Y-m-d'), $prevEndDate->format('Y-m-d')];
    }

    /**
     * @param float|int $current
     * @param float|int $previous
     * @return array{current:float,previous:float,delta:float,pct:?float,trend:string}
     */
    private function metricComparison($current, $previous): array
    {
        $currentVal = (float)$current;
        $previousVal = (float)$previous;
        $delta = $currentVal - $previousVal;
        $pct = $previousVal != 0.0 ? ($delta / $previousVal) * 100 : null;
        $trend = $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat');
        return [
            'current' => $currentVal,
            'previous' => $previousVal,
            'delta' => $delta,
            'pct' => $pct !== null ? round($pct, 2) : null,
            'trend' => $trend,
        ];
    }
}
