<?php

declare(strict_types=1);

final class RateLimiter
{
    private PDO $pdo;
    /** @var Redis|null */
    private $redis;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->redis = $this->connectRedis();
    }

    /**
     * @return array{ok:bool,error:?string,remaining_daily:?int,remaining_monthly:?int}
     */
    public function checkLimit(int $workspaceId, int $credits): array
    {
        if ($workspaceId <= 0 || $credits <= 0) {
            return ['ok' => false, 'error' => 'Invalid request.', 'remaining_daily' => null, 'remaining_monthly' => null];
        }

        $limits = $this->fetchLimits($workspaceId);
        if (!$limits) {
            return ['ok' => true, 'error' => null, 'remaining_daily' => null, 'remaining_monthly' => null];
        }

        $dailyLimit = (int)($limits['daily_limit'] ?? 0);
        $monthlyLimit = (int)($limits['monthly_limit'] ?? 0);
        $remainingDaily = $dailyLimit > 0 ? $dailyLimit - $this->usageForPeriod($workspaceId, 'daily') : null;
        $remainingMonthly = $monthlyLimit > 0 ? $monthlyLimit - $this->usageForPeriod($workspaceId, 'monthly') : null;

        if ($dailyLimit > 0 && $remainingDaily !== null && $remainingDaily < $credits) {
            return ['ok' => false, 'error' => 'Daily limit exceeded.', 'remaining_daily' => $remainingDaily, 'remaining_monthly' => $remainingMonthly];
        }
        if ($monthlyLimit > 0 && $remainingMonthly !== null && $remainingMonthly < $credits) {
            return ['ok' => false, 'error' => 'Monthly limit exceeded.', 'remaining_daily' => $remainingDaily, 'remaining_monthly' => $remainingMonthly];
        }

        if ($this->redis) {
            $this->incrementRedis($workspaceId, 'daily', $credits, $dailyLimit);
            $this->incrementRedis($workspaceId, 'monthly', $credits, $monthlyLimit);
        }

        return ['ok' => true, 'error' => null, 'remaining_daily' => $remainingDaily, 'remaining_monthly' => $remainingMonthly];
    }

    public function usageForPeriod(int $workspaceId, string $period): int
    {
        if ($this->redis) {
            $cached = $this->getRedisUsage($workspaceId, $period);
            if ($cached !== null) {
                return $cached;
            }
        }

        $start = $this->periodStart($period);
        if ($start === null) {
            return 0;
        }

        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(SUM(-credits), 0) FROM workspace_credit_ledger
             WHERE workspace_id = ? AND change_type = "consume" AND created_at >= ?'
        );
        $stmt->execute([$workspaceId, $start]);
        $usage = (int)$stmt->fetchColumn();

        if ($this->redis) {
            $this->setRedisUsage($workspaceId, $period, $usage);
        }

        return $usage;
    }

    private function fetchLimits(int $workspaceId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT daily_limit, monthly_limit, alert_threshold_percent, last_alert_sent_at
             FROM workspace_credit_limits
             WHERE workspace_id = ?
             LIMIT 1'
        );
        $stmt->execute([$workspaceId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function periodStart(string $period): ?string
    {
        $now = new DateTimeImmutable('now');
        if ($period === 'daily') {
            return $now->format('Y-m-d 00:00:00');
        }
        if ($period === 'monthly') {
            return $now->format('Y-m-01 00:00:00');
        }
        return null;
    }

    private function incrementRedis(int $workspaceId, string $period, int $credits, int $limit): void
    {
        if ($limit <= 0 || !$this->redis) {
            return;
        }
        $key = $this->redisKey($workspaceId, $period);
        $value = (int)$this->redis->incrBy($key, $credits);
        if ($value === $credits) {
            $ttl = $this->periodTtl($period);
            if ($ttl > 0) {
                $this->redis->expire($key, $ttl);
            }
        }
        if ($value > $limit) {
            $this->redis->decrBy($key, $credits);
        }
    }

    private function getRedisUsage(int $workspaceId, string $period): ?int
    {
        if (!$this->redis) {
            return null;
        }
        $key = $this->redisKey($workspaceId, $period);
        $value = $this->redis->get($key);
        if ($value === false || $value === null) {
            return null;
        }
        return (int)$value;
    }

    private function setRedisUsage(int $workspaceId, string $period, int $usage): void
    {
        if (!$this->redis) {
            return;
        }
        $key = $this->redisKey($workspaceId, $period);
        $this->redis->set($key, (string)$usage);
        $ttl = $this->periodTtl($period);
        if ($ttl > 0) {
            $this->redis->expire($key, $ttl);
        }
    }

    private function periodTtl(string $period): int
    {
        $now = new DateTimeImmutable('now');
        if ($period === 'daily') {
            $end = $now->setTime(23, 59, 59);
            return max(60, (int)($end->getTimestamp() - $now->getTimestamp()));
        }
        if ($period === 'monthly') {
            $end = $now->modify('last day of this month')->setTime(23, 59, 59);
            return max(60, (int)($end->getTimestamp() - $now->getTimestamp()));
        }
        return 0;
    }

    private function redisKey(int $workspaceId, string $period): string
    {
        return 'workspace:rate:' . $workspaceId . ':' . $period;
    }

    private function connectRedis()
    {
        if (!extension_loaded('redis') || !class_exists('Redis')) {
            return null;
        }

        $redis = new Redis();
        try {
            $connected = @$redis->connect('127.0.0.1', 6379, 1.0);
            if (!$connected) {
                return null;
            }
        } catch (Throwable $e) {
            return null;
        }

        return $redis;
    }
}
