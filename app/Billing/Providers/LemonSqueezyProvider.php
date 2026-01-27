<?php

declare(strict_types=1);

final class LemonSqueezyProvider extends HmacProvider
{
    public function name(): string
    {
        return 'lemonsqueezy';
    }

    protected function signatureHeader(): string
    {
        return 'X-Signature';
    }

    public function createCheckout(array $payload): array
    {
        $settings = $this->settings->getProviderSettings('lemonsqueezy');
        $apiKey = (string)($settings['api_key'] ?? '');
        $storeId = (string)($settings['store_id'] ?? '');
        if ($apiKey === '' || $storeId === '') {
            throw new RuntimeException('Lemon Squeezy credentials are not configured.');
        }

        $plan = $payload['plan'] ?? [];
        $variantId = (string)($plan['lemonsqueezy_variant_id'] ?? '');
        if ($variantId === '') {
            throw new RuntimeException('Lemon Squeezy variant id is missing for this plan.');
        }

        if (!class_exists(\LemonSqueezy\LemonSqueezy::class)) {
            throw new RuntimeException('Lemon Squeezy SDK is not installed.');
        }

        \LemonSqueezy\LemonSqueezy::setApiKey($apiKey);

        $subscriptionId = (int)($payload['subscription_id'] ?? 0);
        $workspace = $payload['workspace'] ?? [];
        $metadata = [
            'subscription_id' => (string)$subscriptionId,
            'workspace_id' => (string)($workspace['id'] ?? ''),
            'plan_id' => (string)($plan['id'] ?? ''),
            'change_id' => (string)($payload['change_id'] ?? ''),
        ];

        $response = \LemonSqueezy\LemonSqueezy::createCheckout($storeId, $variantId, [
            'checkout_data' => [
                'email' => (string)($payload['customer_email'] ?? ''),
                'custom' => $metadata,
            ],
            'checkout_options' => [
                'success_url' => (string)($payload['success_url'] ?? ''),
                'cancel_url' => (string)($payload['cancel_url'] ?? ''),
            ],
        ]);

        $checkout = is_array($response) ? ($response['data'] ?? []) : [];
        $attributes = is_array($checkout['attributes'] ?? null) ? $checkout['attributes'] : [];

        return [
            'checkout_url' => (string)($attributes['url'] ?? ''),
            'checkout_id' => isset($checkout['id']) ? (string)$checkout['id'] : null,
            'provider_subscription_id' => null,
            'provider_customer_id' => null,
            'provider_status' => null,
        ];
    }

    public function parseWebhook(array $payload): array
    {
        $eventType = $this->eventType($payload);
        $data = $payload['data'] ?? [];
        $attributes = is_array($data['attributes'] ?? null) ? $data['attributes'] : [];
        $meta = is_array($attributes['custom_data'] ?? null) ? $attributes['custom_data'] : [];

        $result = [
            'event_type' => $eventType,
            'subscription_id' => $this->toInt($meta['subscription_id'] ?? null),
            'change_id' => $this->toInt($meta['change_id'] ?? null),
            'target_plan_id' => $this->toInt($meta['plan_id'] ?? null),
            'provider_subscription_id' => isset($attributes['subscription_id']) ? (string)$attributes['subscription_id'] : null,
            'provider_checkout_id' => isset($data['id']) ? (string)$data['id'] : null,
            'provider_customer_id' => isset($attributes['customer_id']) ? (string)$attributes['customer_id'] : null,
            'provider_status' => isset($attributes['status']) ? (string)$attributes['status'] : null,
            'status' => $this->mapStatus((string)($attributes['status'] ?? '')),
            'current_period_start' => $this->timestampToString($attributes['current_period_start_at'] ?? null),
            'current_period_end' => $this->timestampToString($attributes['current_period_end_at'] ?? null),
            'trial_ends_at' => $this->timestampToString($attributes['trial_ends_at'] ?? null),
            'invoice_amount' => null,
            'invoice_status' => null,
            'invoice_id' => null,
            'gateway_event_status' => null,
        ];

        if (str_contains($eventType, 'payment_failed')) {
            $result['gateway_event_status'] = 'failed';
            $result['status'] = 'past_due';
        }

        return $result;
    }

    private function mapStatus(string $status): ?string
    {
        return match ($status) {
            'active' => 'active',
            'on_trial', 'trialing' => 'trialing',
            'past_due', 'unpaid' => 'past_due',
            'cancelled', 'canceled', 'expired' => 'canceled',
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
