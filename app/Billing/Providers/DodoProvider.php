<?php

declare(strict_types=1);

final class DodoProvider implements BillingProvider
{
    private PaymentGatewaySettingsService $settings;

    public function __construct(PaymentGatewaySettingsService $settings)
    {
        $this->settings = $settings;
    }

    public function name(): string
    {
        return 'dodo';
    }

    public function verifySignature(string $payload, array $headers): bool
    {
        $settings = $this->settings->getProviderSettings('dodo');
        $secret = (string)($settings['webhook_secret'] ?? '');
        if ($secret === '') {
            return false;
        }

        $id = $this->headerValue($headers, 'webhook-id');
        $timestamp = $this->headerValue($headers, 'webhook-timestamp');
        $signature = $this->headerValue($headers, 'webhook-signature');

        if ($id === null || $timestamp === null || $signature === null) {
            return false;
        }

        $signature = $this->extractSignature($signature);
        if ($signature === '') {
            return false;
        }

        $signed = $id . '.' . $timestamp . '.' . $payload;
        $expected = hash_hmac('sha256', $signed, $secret);

        return hash_equals($expected, $signature);
    }

    public function eventType(array $payload): string
    {
        if (isset($payload['type'])) {
            return (string)$payload['type'];
        }
        if (isset($payload['event'])) {
            return (string)$payload['event'];
        }
        return 'unknown';
    }

    public function createCheckout(array $payload): array
    {
        $settings = $this->settings->getProviderSettings('dodo');
        $apiKey = (string)($settings['api_key'] ?? '');
        if ($apiKey === '') {
            throw new RuntimeException('Dodo Payments API key is not configured.');
        }

        $environment = strtolower((string)($settings['environment'] ?? 'live_mode'));
        $baseUrl = $environment === 'test_mode' || $environment === 'test' || $environment === 'sandbox'
            ? 'https://test.dodopayments.com'
            : 'https://live.dodopayments.com';

        $plan = $payload['plan'] ?? [];
        $priceId = (string)($plan['dodo_price_id'] ?? '');
        if ($priceId === '') {
            throw new RuntimeException('Dodo price id is missing for this plan.');
        }

        $subscriptionId = (int)($payload['subscription_id'] ?? 0);
        $purchaseId = (int)($payload['purchase_id'] ?? 0);
        $workspace = $payload['workspace'] ?? [];

        $client = new \Dodopayments\Client(
            bearerToken: $apiKey,
            baseUrl: $baseUrl
        );

        $extra = [
            'metadata' => [
                'subscription_id' => (string)$subscriptionId,
                'workspace_id' => (string)($workspace['id'] ?? ''),
                'plan_id' => (string)($plan['id'] ?? ''),
                'change_id' => (string)($payload['change_id'] ?? ''),
                'purchase_id' => $purchaseId > 0 ? (string)$purchaseId : '',
            ],
        ];

        $trialDays = (int)($payload['trial_days'] ?? 0);
        if ($trialDays > 0) {
            $extra['subscriptionData'] = [
                'trialPeriodDays' => $trialDays,
            ];
        }

        $requestOptions = \Dodopayments\RequestOptions::with(
            extraBodyParams: $extra
        );

        $session = $client->checkoutSessions->create(
            productCart: [
                ['productID' => $priceId, 'quantity' => 1],
            ],
            returnUrl: (string)($payload['success_url'] ?? ''),
            requestOptions: $requestOptions
        );

        return [
            'checkout_url' => (string)($session->url ?? ''),
            'checkout_id' => (string)($session->session_id ?? ''),
            'provider_subscription_id' => null,
            'provider_customer_id' => null,
            'provider_status' => null,
        ];
    }

    public function parseWebhook(array $payload): array
    {
        $eventType = $this->eventType($payload);
        $data = $payload['data'] ?? [];
        $payloadType = (string)($data['payload_type'] ?? ($payload['payload_type'] ?? ''));
        $metadata = $data['metadata'] ?? [];
        if (is_array($data['subscription'] ?? null) && isset($data['subscription']['metadata'])) {
            $metadata = $data['subscription']['metadata'];
        }

        $subscriptionData = $data;
        if (is_array($data['subscription'] ?? null)) {
            $subscriptionData = $data['subscription'];
        }

        $subscriptionId = $this->toInt($metadata['subscription_id'] ?? null);
        $changeId = $this->toInt($metadata['change_id'] ?? null);
        $planId = $this->toInt($metadata['plan_id'] ?? null);

        $result = [
            'event_type' => $eventType,
            'subscription_id' => $subscriptionId,
            'change_id' => $changeId,
            'target_plan_id' => $planId,
            'purchase_id' => $this->toInt($metadata['purchase_id'] ?? null),
            'provider_subscription_id' => isset($subscriptionData['id']) ? (string)$subscriptionData['id'] : null,
            'provider_checkout_id' => null,
            'provider_customer_id' => isset($subscriptionData['customer_id']) ? (string)$subscriptionData['customer_id'] : null,
            'provider_status' => isset($subscriptionData['status']) ? (string)$subscriptionData['status'] : null,
            'status' => $this->mapStatus((string)($subscriptionData['status'] ?? '')),
            'current_period_start' => $subscriptionData['current_period_start'] ?? null,
            'current_period_end' => $subscriptionData['current_period_end'] ?? null,
            'trial_ends_at' => $subscriptionData['trial_ends_at'] ?? null,
            'invoice_amount' => null,
            'invoice_status' => null,
            'invoice_id' => null,
            'gateway_event_status' => null,
        ];

        if ($payloadType === 'Payment') {
            $amount = $data['amount'] ?? null;
            $result['invoice_amount'] = is_numeric($amount) ? (int)$amount : null;
            $result['invoice_id'] = isset($data['id']) ? (string)$data['id'] : null;
            if (str_contains($eventType, 'payment.succeeded')) {
                $result['invoice_status'] = 'paid';
                $result['gateway_event_status'] = 'success';
            }
            if (str_contains($eventType, 'payment.failed')) {
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

    private function extractSignature(string $headerValue): string
    {
        $parts = preg_split('/[;,]/', $headerValue);
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            if (str_starts_with($part, 'v1=')) {
                return substr($part, 3);
            }
            if (str_starts_with($part, 'v1')) {
                return trim(substr($part, 2), '=');
            }
            if (!str_contains($part, '=')) {
                return $part;
            }
        }
        return '';
    }

    private function mapStatus(string $status): ?string
    {
        $status = strtolower($status);
        return match ($status) {
            'active' => 'active',
            'trialing', 'trial' => 'trialing',
            'past_due', 'unpaid', 'delinquent' => 'past_due',
            'cancelled', 'canceled', 'expired' => 'canceled',
            default => null,
        };
    }

    private function toInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int)$value;
    }
}
