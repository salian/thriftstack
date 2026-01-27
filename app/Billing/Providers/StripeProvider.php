<?php

declare(strict_types=1);

final class StripeProvider implements BillingProvider
{
    private PaymentGatewaySettingsService $settings;

    public function __construct(PaymentGatewaySettingsService $settings)
    {
        $this->settings = $settings;
    }

    public function name(): string
    {
        return 'stripe';
    }

    public function verifySignature(string $payload, array $headers): bool
    {
        $secret = $this->webhookSecret();
        if ($secret === '') {
            return false;
        }
        $signatureHeader = $this->headerValue($headers, 'Stripe-Signature');
        if ($signatureHeader === null) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $part) {
            $pair = explode('=', trim($part), 2);
            if (count($pair) === 2) {
                $parts[$pair[0]] = $pair[1];
            }
        }

        if (empty($parts['t']) || empty($parts['v1'])) {
            return false;
        }

        $signedPayload = $parts['t'] . '.' . $payload;
        $expected = hash_hmac('sha256', $signedPayload, $secret);

        return hash_equals($expected, $parts['v1']);
    }

    public function eventType(array $payload): string
    {
        if (isset($payload['type'])) {
            return (string)$payload['type'];
        }
        return 'unknown';
    }

    public function createCheckout(array $payload): array
    {
        $settings = $this->settings->getProviderSettings('stripe');
        $secretKey = (string)($settings['secret_key'] ?? '');
        if ($secretKey === '') {
            throw new RuntimeException('Stripe secret key is not configured.');
        }

        $plan = $payload['plan'] ?? [];
        $priceId = (string)($plan['stripe_price_id'] ?? '');
        if ($priceId === '') {
            throw new RuntimeException('Stripe price id is missing for this plan.');
        }

        $subscriptionId = (int)($payload['subscription_id'] ?? 0);
        $workspace = $payload['workspace'] ?? [];
        $metadata = [
            'subscription_id' => (string)$subscriptionId,
            'workspace_id' => (string)($workspace['id'] ?? ''),
            'plan_id' => (string)($plan['id'] ?? ''),
            'change_id' => (string)($payload['change_id'] ?? ''),
        ];

        $params = [
            'mode' => 'subscription',
            'line_items' => [
                ['price' => $priceId, 'quantity' => 1],
            ],
            'success_url' => (string)($payload['success_url'] ?? ''),
            'cancel_url' => (string)($payload['cancel_url'] ?? ''),
            'client_reference_id' => (string)$subscriptionId,
            'customer_email' => (string)($payload['customer_email'] ?? ''),
            'metadata' => $metadata,
            'subscription_data' => [
                'metadata' => $metadata,
            ],
        ];

        $trialDays = (int)($payload['trial_days'] ?? 0);
        if ($trialDays > 0) {
            $params['subscription_data']['trial_period_days'] = $trialDays;
        }

        $client = new \Stripe\StripeClient($secretKey);
        $session = $client->checkout->sessions->create($params);

        return [
            'checkout_url' => (string)($session->url ?? ''),
            'checkout_id' => (string)($session->id ?? ''),
            'provider_subscription_id' => isset($session->subscription) ? (string)$session->subscription : null,
            'provider_customer_id' => isset($session->customer) ? (string)$session->customer : null,
            'provider_status' => isset($session->status) ? (string)$session->status : null,
        ];
    }

    public function parseWebhook(array $payload): array
    {
        $eventType = $this->eventType($payload);
        $data = $payload['data']['object'] ?? [];
        $metadata = is_array($data['metadata'] ?? null) ? $data['metadata'] : [];

        $result = [
            'event_type' => $eventType,
            'subscription_id' => $this->toInt($metadata['subscription_id'] ?? null),
            'change_id' => $this->toInt($metadata['change_id'] ?? null),
            'target_plan_id' => $this->toInt($metadata['plan_id'] ?? null),
            'provider_subscription_id' => null,
            'provider_checkout_id' => null,
            'provider_customer_id' => null,
            'provider_status' => null,
            'status' => null,
            'current_period_start' => null,
            'current_period_end' => null,
            'trial_ends_at' => null,
            'invoice_amount' => null,
            'invoice_status' => null,
            'invoice_id' => null,
            'gateway_event_status' => null,
        ];

        if ($eventType === 'checkout.session.completed') {
            $result['provider_checkout_id'] = isset($data['id']) ? (string)$data['id'] : null;
            $result['provider_subscription_id'] = isset($data['subscription']) ? (string)$data['subscription'] : null;
            $result['provider_customer_id'] = isset($data['customer']) ? (string)$data['customer'] : null;
            $result['provider_status'] = isset($data['status']) ? (string)$data['status'] : null;
            $paymentStatus = (string)($data['payment_status'] ?? '');
            $result['status'] = in_array($paymentStatus, ['paid', 'no_payment_required'], true) ? 'active' : 'pending';
        }

        if ($eventType === 'customer.subscription.updated' || $eventType === 'customer.subscription.created') {
            $result['provider_subscription_id'] = isset($data['id']) ? (string)$data['id'] : null;
            $result['provider_customer_id'] = isset($data['customer']) ? (string)$data['customer'] : null;
            $result['provider_status'] = isset($data['status']) ? (string)$data['status'] : null;
            $result['status'] = $this->mapStripeStatus((string)($data['status'] ?? ''));
            $result['current_period_start'] = $this->timestampToString($data['current_period_start'] ?? null);
            $result['current_period_end'] = $this->timestampToString($data['current_period_end'] ?? null);
            $result['trial_ends_at'] = $this->timestampToString($data['trial_end'] ?? null);
        }

        if ($eventType === 'customer.subscription.deleted') {
            $result['provider_subscription_id'] = isset($data['id']) ? (string)$data['id'] : null;
            $result['provider_customer_id'] = isset($data['customer']) ? (string)$data['customer'] : null;
            $result['provider_status'] = 'canceled';
            $result['status'] = 'canceled';
            $result['current_period_end'] = $this->timestampToString($data['current_period_end'] ?? null);
        }

        if ($eventType === 'invoice.payment_succeeded' || $eventType === 'invoice.payment_failed') {
            $result['provider_subscription_id'] = isset($data['subscription']) ? (string)$data['subscription'] : null;
            $result['invoice_id'] = isset($data['id']) ? (string)$data['id'] : null;
            $result['invoice_amount'] = isset($data['amount_paid']) ? (int)$data['amount_paid'] : null;
            $result['invoice_status'] = $eventType === 'invoice.payment_succeeded' ? 'paid' : 'failed';
            $result['gateway_event_status'] = $eventType === 'invoice.payment_succeeded' ? 'success' : 'failed';
            if ($eventType === 'invoice.payment_failed') {
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

    private function webhookSecret(): string
    {
        $settings = $this->settings->getProviderSettings('stripe');
        return (string)($settings['webhook_secret'] ?? '');
    }

    private function mapStripeStatus(string $status): ?string
    {
        return match ($status) {
            'active' => 'active',
            'trialing' => 'trialing',
            'past_due', 'unpaid' => 'past_due',
            'canceled' => 'canceled',
            default => null,
        };
    }

    private function timestampToString($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return date('Y-m-d H:i:s', (int)$value);
        }
        return null;
    }

    private function toInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int)$value;
    }
}
