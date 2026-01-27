<?php

declare(strict_types=1);

final class BillingGatewaySelector
{
    private PDO $pdo;
    private PaymentGatewaySettingsService $gateways;
    private AppSettingsService $appSettings;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->gateways = new PaymentGatewaySettingsService($pdo);
        $this->appSettings = new AppSettingsService($pdo);
    }

    public function selectProvider(): ?string
    {
        $enabled = $this->gateways->enabledProviders();
        if (empty($enabled)) {
            return null;
        }

        $rule = $this->appSettings->get('billing.gateway_rule', 'priority');
        $priority = $this->appSettings->getJson('billing.gateway_priority', $enabled);
        $priority = array_values(array_filter($priority, static fn ($item) => in_array($item, $enabled, true)));

        if ($rule === 'random') {
            return $enabled[array_rand($enabled)];
        }

        if ($rule === 'failure_rate') {
            return $this->pickLowestFailure($enabled, $priority) ?? $enabled[0];
        }

        if ($rule === 'geo') {
            // Geo routing is stubbed for now; fall back to priority.
            $rule = 'priority';
        }

        foreach ($priority as $provider) {
            if (in_array($provider, $enabled, true)) {
                return $provider;
            }
        }

        return $enabled[0];
    }

    private function pickLowestFailure(array $enabled, array $priority): ?string
    {
        $since = (new DateTimeImmutable('now'))->modify('-24 hours')->format('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'SELECT provider, COUNT(*) AS failures
             FROM payment_gateway_events
             WHERE status = ? AND created_at >= ?
             GROUP BY provider'
        );
        $stmt->execute(['failed', $since]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $failureMap = [];
        foreach ($rows as $row) {
            $failureMap[(string)$row['provider']] = (int)$row['failures'];
        }

        $best = null;
        $bestCount = null;
        foreach ($enabled as $provider) {
            $count = $failureMap[$provider] ?? 0;
            if ($bestCount === null || $count < $bestCount) {
                $bestCount = $count;
                $best = $provider;
            } elseif ($count === $bestCount && $best !== null && !empty($priority)) {
                $bestIndex = array_search($best, $priority, true);
                $candidateIndex = array_search($provider, $priority, true);
                if ($candidateIndex !== false && ($bestIndex === false || $candidateIndex < $bestIndex)) {
                    $best = $provider;
                }
            }
        }

        return $best;
    }
}
