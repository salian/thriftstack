<?php

declare(strict_types=1);

final class PayPalProvider implements BillingProvider
{
    private PaymentGatewaySettingsService $settings;

    public function __construct(PaymentGatewaySettingsService $settings)
    {
        $this->settings = $settings;
    }

    public function name(): string
    {
        return 'paypal';
    }

    public function verifySignature(string $payload, array $headers): bool
    {
        $settings = $this->settings->getProviderSettings('paypal');
        $webhookId = (string)($settings['webhook_id'] ?? '');
        $clientId = (string)($settings['client_id'] ?? '');
        $clientSecret = (string)($settings['client_secret'] ?? '');
        if ($webhookId === '' || $clientId === '' || $clientSecret === '') {
            return false;
        }

        $event = json_decode($payload, true);
        if (!is_array($event)) {
            return false;
        }

        $environment = $this->environment($settings, $clientId, $clientSecret);
        $client = new \PayPalCheckoutSdk\Core\PayPalHttpClient($environment);
        $request = new \PayPalCheckoutSdk\Webhooks\VerifyWebhookSignatureRequest();
        $request->body = [
            'auth_algo' => $this->headerValue($headers, 'PAYPAL-AUTH-ALGO'),
            'cert_url' => $this->headerValue($headers, 'PAYPAL-CERT-URL'),
            'transmission_id' => $this->headerValue($headers, 'PAYPAL-TRANSMISSION-ID'),
            'transmission_sig' => $this->headerValue($headers, 'PAYPAL-TRANSMISSION-SIG'),
            'transmission_time' => $this->headerValue($headers, 'PAYPAL-TRANSMISSION-TIME'),
            'webhook_id' => $webhookId,
            'webhook_event' => $event,
        ];

        try {
            $response = $client->execute($request);
            return (string)($response->result->verification_status ?? '') === 'SUCCESS';
        } catch (Throwable $e) {
            return false;
        }
    }

    public function eventType(array $payload): string
    {
        if (isset($payload['event_type'])) {
            return (string)$payload['event_type'];
        }
        return 'unknown';
    }

    public function createCheckout(array $payload): array
    {
        $settings = $this->settings->getProviderSettings('paypal');
        $clientId = (string)($settings['client_id'] ?? '');
        $clientSecret = (string)($settings['client_secret'] ?? '');
        if ($clientId === '' || $clientSecret === '') {
            throw new RuntimeException('PayPal credentials are not configured.');
        }

        $mode = (string)($payload['mode'] ?? 'subscription');
        if ($mode === 'payment') {
            $plan = $payload['plan'] ?? [];
            $amountCents = (int)($plan['price_cents'] ?? 0);
            $currency = (string)($plan['currency'] ?? 'USD');
            $purchaseId = (int)($payload['purchase_id'] ?? 0);
            if ($amountCents <= 0) {
                throw new RuntimeException('PayPal amount is missing for this top-up.');
            }

            $environment = $this->environment($settings, $clientId, $clientSecret);
            $client = new \PayPalCheckoutSdk\Core\PayPalHttpClient($environment);
            $request = new \PayPalCheckoutSdk\Orders\OrdersCreateRequest();
            $request->prefer('return=representation');
            $request->body = [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'custom_id' => $purchaseId > 0 ? ('purchase:' . $purchaseId) : null,
                    'description' => (string)($plan['name'] ?? 'Top-up'),
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => number_format($amountCents / 100, 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'return_url' => (string)($payload['success_url'] ?? ''),
                    'cancel_url' => (string)($payload['cancel_url'] ?? ''),
                ],
            ];

            $response = $client->execute($request);
            $result = $response->result;
            $approvalUrl = '';
            if (!empty($result->links)) {
                foreach ($result->links as $link) {
                    if (($link->rel ?? '') === 'approve') {
                        $approvalUrl = (string)$link->href;
                        break;
                    }
                }
            }

            return [
                'checkout_url' => $approvalUrl,
                'checkout_id' => isset($result->id) ? (string)$result->id : null,
                'provider_subscription_id' => null,
                'provider_customer_id' => null,
                'provider_status' => isset($result->status) ? (string)$result->status : null,
            ];
        }

        $plan = $payload['plan'] ?? [];
        $planId = (string)($plan['paypal_plan_id'] ?? '');
        if ($planId === '') {
            throw new RuntimeException('PayPal plan id is missing for this plan.');
        }

        $subscriptionId = (int)($payload['subscription_id'] ?? 0);
        $changeId = (int)($payload['change_id'] ?? 0);
        $planId = (int)($plan['id'] ?? 0);
        $customId = $this->buildCustomId($subscriptionId, $changeId, $planId);
        $environment = $this->environment($settings, $clientId, $clientSecret);
        $client = new \PayPalCheckoutSdk\Core\PayPalHttpClient($environment);

        $request = new \PayPalCheckoutSdk\Subscriptions\SubscriptionsCreateRequest();
        $request->body = [
            'plan_id' => $planId,
            'custom_id' => $customId,
            'subscriber' => [
                'email_address' => (string)($payload['customer_email'] ?? ''),
            ],
            'application_context' => [
                'return_url' => (string)($payload['success_url'] ?? ''),
                'cancel_url' => (string)($payload['cancel_url'] ?? ''),
            ],
        ];

        $response = $client->execute($request);
        $result = $response->result;
        $approvalUrl = '';
        if (!empty($result->links)) {
            foreach ($result->links as $link) {
                if (($link->rel ?? '') === 'approve') {
                    $approvalUrl = (string)$link->href;
                    break;
                }
            }
        }

        return [
            'checkout_url' => $approvalUrl,
            'checkout_id' => isset($result->id) ? (string)$result->id : null,
            'provider_subscription_id' => isset($result->id) ? (string)$result->id : null,
            'provider_customer_id' => isset($result->subscriber->payer_id) ? (string)$result->subscriber->payer_id : null,
            'provider_status' => isset($result->status) ? (string)$result->status : null,
        ];
    }

    public function parseWebhook(array $payload): array
    {
        $eventType = $this->eventType($payload);
        $resource = $payload['resource'] ?? [];
        $customId = $resource['custom_id'] ?? null;
        $purchaseId = $this->parsePurchaseId((string)$customId);
        $customParts = $this->parseCustomId((string)$customId);
        if ($purchaseId === null && isset($resource['purchase_units'][0]['custom_id'])) {
            $purchaseId = $this->parsePurchaseId((string)$resource['purchase_units'][0]['custom_id']);
        }

        $result = [
            'event_type' => $eventType,
            'subscription_id' => $customParts['subscription_id'],
            'change_id' => $customParts['change_id'],
            'target_plan_id' => $customParts['plan_id'],
            'purchase_id' => $purchaseId,
            'provider_subscription_id' => isset($resource['id']) ? (string)$resource['id'] : null,
            'provider_checkout_id' => null,
            'provider_customer_id' => isset($resource['subscriber']['payer_id']) ? (string)$resource['subscriber']['payer_id'] : null,
            'provider_status' => isset($resource['status']) ? (string)$resource['status'] : null,
            'status' => $this->mapStatus((string)($resource['status'] ?? '')),
            'current_period_start' => $this->timestampToString($resource['start_time'] ?? null),
            'current_period_end' => $this->timestampToString($resource['billing_info']['next_billing_time'] ?? null),
            'trial_ends_at' => null,
            'invoice_amount' => null,
            'invoice_status' => null,
            'invoice_id' => null,
            'gateway_event_status' => null,
        ];

        if ($eventType === 'PAYMENT.SALE.COMPLETED' || $eventType === 'PAYMENT.SALE.DENIED') {
            $result['provider_subscription_id'] = isset($resource['billing_agreement_id']) ? (string)$resource['billing_agreement_id'] : null;
            $result['invoice_id'] = isset($resource['id']) ? (string)$resource['id'] : null;
            $result['invoice_amount'] = isset($resource['amount']['total']) ? (int)round(((float)$resource['amount']['total']) * 100) : null;
            $result['invoice_status'] = $eventType === 'PAYMENT.SALE.COMPLETED' ? 'paid' : 'failed';
            $result['gateway_event_status'] = $eventType === 'PAYMENT.SALE.COMPLETED' ? 'success' : 'failed';
        }

        if ($eventType === 'CHECKOUT.ORDER.APPROVED' || $eventType === 'PAYMENT.CAPTURE.COMPLETED') {
            $result['provider_checkout_id'] = isset($resource['id']) ? (string)$resource['id'] : null;
            $amount = $resource['amount']['value'] ?? ($resource['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? null);
            if (is_numeric($amount)) {
                $result['invoice_amount'] = (int)round(((float)$amount) * 100);
            }
            if ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
                $result['invoice_status'] = 'paid';
                $result['gateway_event_status'] = 'success';
                $result['status'] = 'active';
            }
        }

        return $result;
    }

    private function environment(array $settings, string $clientId, string $clientSecret): \PayPalCheckoutSdk\Core\PayPalEnvironment
    {
        $mode = strtolower((string)($settings['mode'] ?? 'live'));
        if ($mode === 'sandbox') {
            return new \PayPalCheckoutSdk\Core\SandboxEnvironment($clientId, $clientSecret);
        }
        return new \PayPalCheckoutSdk\Core\LiveEnvironment($clientId, $clientSecret);
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

    private function mapStatus(string $status): ?string
    {
        return match (strtoupper($status)) {
            'ACTIVE' => 'active',
            'APPROVAL_PENDING' => 'pending',
            'SUSPENDED', 'PAST_DUE' => 'past_due',
            'CANCELLED', 'CANCELED' => 'canceled',
            default => null,
        };
    }

    private function parsePurchaseId(string $value): ?int
    {
        if ($value === '') {
            return null;
        }
        if (str_starts_with($value, 'purchase:')) {
            return (int)substr($value, 9);
        }
        return null;
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

    private function buildCustomId(int $subscriptionId, int $changeId, int $planId): string
    {
        if ($changeId > 0 || $planId > 0) {
            return $subscriptionId . ':' . $changeId . ':' . $planId;
        }
        return (string)$subscriptionId;
    }

    private function parseCustomId(string $customId): array
    {
        $parts = explode(':', $customId);
        return [
            'subscription_id' => isset($parts[0]) ? (int)$parts[0] : null,
            'change_id' => isset($parts[1]) && $parts[1] !== '' ? (int)$parts[1] : null,
            'plan_id' => isset($parts[2]) && $parts[2] !== '' ? (int)$parts[2] : null,
        ];
    }
}
