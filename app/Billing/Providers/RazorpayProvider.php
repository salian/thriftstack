<?php

declare(strict_types=1);

final class RazorpayProvider extends HmacProvider
{
    public function name(): string
    {
        return 'razorpay';
    }

    protected function signatureHeader(): string
    {
        return 'X-Razorpay-Signature';
    }

    public function createCheckout(array $payload): array
    {
        $settings = $this->settings->getProviderSettings('razorpay');
        $keyId = (string)($settings['key_id'] ?? '');
        $keySecret = (string)($settings['key_secret'] ?? '');
        if ($keyId === '' || $keySecret === '') {
            throw new RuntimeException('Razorpay keys are not configured.');
        }

        $mode = (string)($payload['mode'] ?? 'subscription');
        if ($mode === 'payment') {
            $plan = $payload['plan'] ?? [];
            $amount = (int)($plan['price_cents'] ?? 0);
            $currency = (string)($plan['currency'] ?? 'USD');
            $purchaseId = (int)($payload['purchase_id'] ?? 0);
            if ($amount <= 0) {
                throw new RuntimeException('Razorpay amount is missing for this top-up.');
            }

            $api = new \Razorpay\Api\Api($keyId, $keySecret);
            $link = $api->paymentLink->create([
                'amount' => $amount,
                'currency' => $currency,
                'description' => (string)($plan['name'] ?? 'Top-up'),
                'customer' => [
                    'email' => (string)($payload['customer_email'] ?? ''),
                ],
                'notify' => [
                    'email' => true,
                ],
                'notes' => [
                    'purchase_id' => (string)$purchaseId,
                    'plan_id' => (string)($plan['id'] ?? ''),
                ],
                'callback_url' => (string)($payload['success_url'] ?? ''),
                'callback_method' => 'get',
            ]);

            return [
                'checkout_url' => (string)($link['short_url'] ?? ''),
                'checkout_id' => (string)($link['id'] ?? ''),
                'provider_subscription_id' => null,
                'provider_customer_id' => null,
                'provider_status' => isset($link['status']) ? (string)$link['status'] : null,
            ];
        }

        $plan = $payload['plan'] ?? [];
        $planId = (string)($plan['razorpay_plan_id'] ?? '');
        if ($planId === '') {
            throw new RuntimeException('Razorpay plan id is missing for this plan.');
        }

        $subscriptionId = (int)($payload['subscription_id'] ?? 0);
        $purchaseId = (int)($payload['purchase_id'] ?? 0);
        $workspace = $payload['workspace'] ?? [];
        $trialDays = (int)($payload['trial_days'] ?? 0);
        $startAt = $trialDays > 0 ? time() + ($trialDays * 86400) : null;

        $api = new \Razorpay\Api\Api($keyId, $keySecret);
        $params = [
            'plan_id' => $planId,
            'customer_notify' => 1,
            'total_count' => 120,
            'notes' => [
                'subscription_id' => (string)$subscriptionId,
                'workspace_id' => (string)($workspace['id'] ?? ''),
                'plan_id' => (string)($plan['id'] ?? ''),
                'change_id' => (string)($payload['change_id'] ?? ''),
                'purchase_id' => $purchaseId > 0 ? (string)$purchaseId : '',
            ],
        ];
        if ($startAt !== null) {
            $params['start_at'] = $startAt;
        }

        $subscription = $api->subscription->create($params);

        return [
            'checkout_url' => (string)($subscription['short_url'] ?? ''),
            'checkout_id' => (string)($subscription['id'] ?? ''),
            'provider_subscription_id' => (string)($subscription['id'] ?? ''),
            'provider_customer_id' => isset($subscription['customer_id']) ? (string)$subscription['customer_id'] : null,
            'provider_status' => isset($subscription['status']) ? (string)$subscription['status'] : null,
        ];
    }

    public function parseWebhook(array $payload): array
    {
        $eventType = $this->eventType($payload);
        $subscription = $payload['payload']['subscription']['entity'] ?? [];
        $paymentLink = $payload['payload']['payment_link']['entity'] ?? [];
        $payment = $payload['payload']['payment']['entity'] ?? [];
        $notes = is_array($subscription['notes'] ?? null) ? $subscription['notes'] : [];
        if (empty($notes) && is_array($paymentLink['notes'] ?? null)) {
            $notes = $paymentLink['notes'];
        }

        $result = [
            'event_type' => $eventType,
            'subscription_id' => $this->toInt($notes['subscription_id'] ?? null),
            'change_id' => $this->toInt($notes['change_id'] ?? null),
            'target_plan_id' => $this->toInt($notes['plan_id'] ?? null),
            'purchase_id' => $this->toInt($notes['purchase_id'] ?? null),
            'provider_subscription_id' => isset($subscription['id']) ? (string)$subscription['id'] : null,
            'provider_checkout_id' => isset($subscription['id']) ? (string)$subscription['id'] : (isset($paymentLink['id']) ? (string)$paymentLink['id'] : null),
            'provider_customer_id' => isset($subscription['customer_id']) ? (string)$subscription['customer_id'] : null,
            'provider_status' => isset($subscription['status']) ? (string)$subscription['status'] : null,
            'status' => $this->mapStatus((string)($subscription['status'] ?? '')),
            'current_period_start' => $this->timestampToString($subscription['current_start'] ?? null),
            'current_period_end' => $this->timestampToString($subscription['current_end'] ?? null),
            'trial_ends_at' => $this->timestampToString($subscription['start_at'] ?? null),
            'invoice_amount' => isset($payment['amount']) ? (int)$payment['amount'] : null,
            'invoice_status' => isset($payment['status']) ? (string)$payment['status'] : null,
            'invoice_id' => isset($payment['id']) ? (string)$payment['id'] : null,
            'gateway_event_status' => $eventType === 'payment.failed' ? 'failed' : null,
        ];

        if ($eventType === 'subscription.activated') {
            $result['status'] = 'active';
        }
        if ($eventType === 'subscription.cancelled') {
            $result['status'] = 'canceled';
        }

        if ($eventType === 'payment.failed') {
            $result['status'] = 'past_due';
            $result['invoice_status'] = 'failed';
        }

        if (str_contains($eventType, 'payment_link')) {
            $linkStatus = (string)($paymentLink['status'] ?? '');
            $result['provider_status'] = $linkStatus !== '' ? $linkStatus : $result['provider_status'];
            if (str_contains($eventType, 'payment_link.paid')) {
                $result['gateway_event_status'] = 'success';
                $result['invoice_status'] = 'paid';
                $result['status'] = 'active';
            }
            if (str_contains($eventType, 'payment_link.failed')) {
                $result['gateway_event_status'] = 'failed';
                $result['invoice_status'] = 'failed';
                $result['status'] = 'past_due';
            }
        }

        return $result;
    }

    private function mapStatus(string $status): ?string
    {
        return match ($status) {
            'active' => 'active',
            'authenticated', 'created' => 'pending',
            'paused', 'halted' => 'past_due',
            'cancelled', 'completed' => 'canceled',
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
