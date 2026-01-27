<?php

declare(strict_types=1);

final class PaddleProvider implements BillingProvider
{
    private PaymentGatewaySettingsService $settings;

    public function __construct(PaymentGatewaySettingsService $settings)
    {
        $this->settings = $settings;
    }

    public function name(): string
    {
        return 'paddle';
    }

    public function verifySignature(string $payload, array $headers): bool
    {
        $settings = $this->settings->getProviderSettings('paddle');
        $secret = (string)($settings['endpoint_secret'] ?? '');
        if ($secret === '') {
            return false;
        }

        $signatureHeader = $this->headerValue($headers, 'Paddle-Signature');
        if ($signatureHeader === null) {
            return false;
        }

        $parts = $this->parseSignature($signatureHeader);
        if (empty($parts['ts']) || empty($parts['h1'])) {
            return false;
        }

        $signedPayload = $parts['ts'] . ':' . $payload;
        $expected = hash_hmac('sha256', $signedPayload, $secret);

        return hash_equals($expected, $parts['h1']);
    }

    public function eventType(array $payload): string
    {
        if (isset($payload['event_type'])) {
            return (string)$payload['event_type'];
        }
        if (isset($payload['type'])) {
            return (string)$payload['type'];
        }
        return 'unknown';
    }

    public function createCheckout(array $payload): array
    {
        return [
            'checkout_url' => (string)($payload['checkout_url'] ?? ''),
            'checkout_id' => null,
            'provider_subscription_id' => null,
            'provider_customer_id' => null,
            'provider_status' => null,
        ];
    }

    public function parseWebhook(array $payload): array
    {
        $eventType = $this->eventType($payload);
        $data = $payload['data'] ?? [];
        $custom = $data['custom_data'] ?? ($data['customData'] ?? []);

        $subscriptionId = $this->toInt($custom['subscription_id'] ?? null);
        $changeId = $this->toInt($custom['change_id'] ?? null);
        $planId = $this->toInt($custom['plan_id'] ?? null);

        $providerSubscriptionId = $data['subscription_id'] ?? ($data['id'] ?? null);
        if (isset($data['subscription']) && is_array($data['subscription'])) {
            $providerSubscriptionId = $data['subscription']['id'] ?? $providerSubscriptionId;
        }

        $status = $this->mapStatus((string)($data['status'] ?? ''));
        $currentPeriodEnd = $data['billing_period']['ends_at'] ?? ($data['current_billing_period']['ends_at'] ?? null);
        $currentPeriodStart = $data['billing_period']['starts_at'] ?? ($data['current_billing_period']['starts_at'] ?? null);

        $result = [
            'event_type' => $eventType,
            'subscription_id' => $subscriptionId,
            'change_id' => $changeId,
            'target_plan_id' => $planId,
            'provider_subscription_id' => $providerSubscriptionId ? (string)$providerSubscriptionId : null,
            'provider_checkout_id' => isset($data['id']) ? (string)$data['id'] : null,
            'provider_customer_id' => isset($data['customer_id']) ? (string)$data['customer_id'] : null,
            'provider_status' => isset($data['status']) ? (string)$data['status'] : null,
            'status' => $status,
            'current_period_start' => $this->timestampToString($currentPeriodStart),
            'current_period_end' => $this->timestampToString($currentPeriodEnd),
            'trial_ends_at' => $this->timestampToString($data['trial_end'] ?? null),
            'invoice_amount' => null,
            'invoice_status' => null,
            'invoice_id' => null,
            'gateway_event_status' => null,
        ];

        if (str_starts_with($eventType, 'transaction.')) {
            $result['invoice_id'] = isset($data['id']) ? (string)$data['id'] : null;
            $amount = $data['totals']['total'] ?? null;
            if (is_numeric($amount)) {
                $result['invoice_amount'] = (int)round(((float)$amount) * 100);
            }
            if ($eventType === 'transaction.completed') {
                $result['invoice_status'] = 'paid';
                $result['gateway_event_status'] = 'success';
            }
            if ($eventType === 'transaction.payment_failed') {
                $result['invoice_status'] = 'failed';
                $result['gateway_event_status'] = 'failed';
                $result['status'] = 'past_due';
            }
        }

        return $result;
    }

    private function headerValue(array $headers, string $name): ?string
    {
        $target = strtolower($name);
        foreach ($headers as $key => $value) {
            if (strtolower($key) === $target) {
                return is_array($value) ? (string)($value[0] ?? '') : (string)$value;
            }
        }
        return null;
    }

    private function parseSignature(string $header): array
    {
        $parts = [];
        foreach (explode(';', $header) as $pair) {
            $pair = trim($pair);
            if ($pair === '') {
                continue;
            }
            $kv = explode('=', $pair, 2);
            if (count($kv) === 2) {
                $parts[$kv[0]] = $kv[1];
            }
        }
        return $parts;
    }

    private function mapStatus(string $status): ?string
    {
        return match (strtolower($status)) {
            'active' => 'active',
            'trialing', 'trial' => 'trialing',
            'past_due', 'unpaid' => 'past_due',
            'canceled', 'cancelled' => 'canceled',
            default => null,
        };
    }

    private function timestampToString($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $timestamp = strtotime((string)$value);
        if ($timestamp === false) {
            return null;
        }
        return date('Y-m-d H:i:s', $timestamp);
    }

    private function toInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int)$value;
    }
}
