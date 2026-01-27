<?php

declare(strict_types=1);

final class WebhooksController
{
    private BillingService $billing;
    /** @var array<string, BillingProvider> */
    private array $providers;

    /**
     * @param array<string, BillingProvider> $providers
     */
    public function __construct(BillingService $billing, array $providers)
    {
        $this->billing = $billing;
        $this->providers = $providers;
    }

    public function handle(Request $request): Response
    {
        $providerKey = (string)$request->param('provider', '');
        if ($providerKey === '' || !isset($this->providers[$providerKey])) {
            return Response::notFound('Not Found');
        }

        $payload = (string)file_get_contents('php://input');
        $headers = $this->getHeaders();
        $provider = $this->providers[$providerKey];

        if (!$provider->verifySignature($payload, $headers)) {
            return new Response('Invalid signature', 400, ['Content-Type' => 'text/plain']);
        }

        $data = json_decode($payload, true);
        $eventType = is_array($data) ? $provider->eventType($data) : 'unknown';
        $this->billing->recordWebhookEvent($providerKey, $eventType, $payload);

        if (is_array($data)) {
            $normalized = $provider->parseWebhook($data);
            $purchase = null;
            $purchaseId = (int)($normalized['purchase_id'] ?? 0);
            if ($purchaseId > 0) {
                $purchase = $this->billing->findTopupPurchaseById($purchaseId);
            }
            if (!$purchase && !empty($normalized['provider_checkout_id'])) {
                $purchase = $this->billing->findTopupPurchaseByCheckoutId($providerKey, (string)$normalized['provider_checkout_id']);
            }

            if ($purchase) {
                $alreadyApplied = !empty($purchase['applied_at']) || ($purchase['status'] ?? '') === 'paid';
                $update = [];
                if (!empty($normalized['provider_checkout_id'])) {
                    $update['provider_checkout_id'] = $normalized['provider_checkout_id'];
                }
                if (!empty($normalized['provider_customer_id'])) {
                    $update['provider_customer_id'] = $normalized['provider_customer_id'];
                }
                if (!empty($normalized['provider_status'])) {
                    $update['provider_status'] = $normalized['provider_status'];
                }

                $isPaid = false;
                $gatewayStatus = (string)($normalized['gateway_event_status'] ?? '');
                $invoiceStatus = (string)($normalized['invoice_status'] ?? '');
                $status = (string)($normalized['status'] ?? '');
                if ($gatewayStatus === 'success' || $invoiceStatus === 'paid' || $status === 'active') {
                    $isPaid = true;
                }

                if ($isPaid && !$alreadyApplied) {
                    $update['status'] = 'paid';
                    $update['applied_at'] = date('Y-m-d H:i:s');
                    if (empty($purchase['expires_at'])) {
                        $update['expires_at'] = (new DateTimeImmutable('now'))->modify('+365 days')->format('Y-m-d H:i:s');
                    }
                    $this->billing->applyCreditChange(
                        (int)$purchase['workspace_id'],
                        (int)$purchase['credits'],
                        'topup',
                        'purchase',
                        (int)$purchase['id'],
                        'AI credit top-up'
                    );
                }

                if (!empty($update)) {
                    $this->billing->updateTopupPurchase((int)$purchase['id'], $update);
                }

                return new Response('ok', 200, ['Content-Type' => 'text/plain']);
            }

            $subscription = null;
            $subscriptionId = $normalized['subscription_id'] ?? null;
            if ($subscriptionId) {
                $subscription = $this->billing->findSubscriptionById((int)$subscriptionId);
            }
            if (!$subscription && !empty($normalized['provider_subscription_id'])) {
                $subscription = $this->billing->findSubscriptionByProviderId($providerKey, (string)$normalized['provider_subscription_id']);
            }
            if (!$subscription && !empty($normalized['provider_checkout_id'])) {
                $subscription = $this->billing->findSubscriptionByCheckoutId($providerKey, (string)$normalized['provider_checkout_id']);
            }

            if ($subscription) {
                $update = [];
                if (!empty($normalized['provider_subscription_id'])) {
                    $update['provider_subscription_id'] = $normalized['provider_subscription_id'];
                }
                if (!empty($normalized['provider_customer_id'])) {
                    $update['provider_customer_id'] = $normalized['provider_customer_id'];
                }
                if (!empty($normalized['provider_checkout_id'])) {
                    $update['provider_checkout_id'] = $normalized['provider_checkout_id'];
                }
                if (!empty($normalized['provider_status'])) {
                    $update['provider_status'] = $normalized['provider_status'];
                }
                if (!empty($normalized['status'])) {
                    $update['status'] = $normalized['status'];
                }
                if (!empty($normalized['current_period_start'])) {
                    $update['current_period_start'] = $normalized['current_period_start'];
                }
                if (!empty($normalized['current_period_end'])) {
                    $update['current_period_end'] = $normalized['current_period_end'];
                }
                if (!empty($normalized['trial_ends_at'])) {
                    $update['trial_ends_at'] = $normalized['trial_ends_at'];
                }
                if (!empty($update)) {
                    $this->billing->updateSubscriptionProvider((int)$subscription['id'], $update);
                }
                if (!empty($normalized['status'])) {
                    $this->billing->grantSubscriptionCreditsIfEligible((int)$subscription['id'], (string)$normalized['status']);
                }

                if (!empty($normalized['invoice_id']) && $normalized['invoice_amount'] !== null) {
                    $this->billing->recordInvoice(
                        (int)$subscription['id'],
                        (int)$normalized['invoice_amount'],
                        (string)($normalized['invoice_status'] ?? 'paid'),
                        $providerKey,
                        (string)$normalized['invoice_id']
                    );
                }

                if (!empty($normalized['gateway_event_status'])) {
                    $this->billing->recordGatewayEvent(
                        (int)$subscription['id'],
                        $providerKey,
                        (string)$normalized['gateway_event_status'],
                        $eventType
                    );
                }

                $changeId = (int)($normalized['change_id'] ?? 0);
                if ($changeId > 0) {
                    $change = $this->billing->findSubscriptionChange($changeId);
                    if ($change && ($change['status'] ?? '') === 'pending') {
                        $targetPlan = (int)($change['to_plan_id'] ?? 0);
                        if ($targetPlan > 0 && ($normalized['status'] ?? '') !== 'pending') {
                            $this->billing->applySubscriptionChange(
                                (int)$change['id'],
                                (int)$subscription['id'],
                                $targetPlan,
                                $normalized['current_period_end'] ?? null
                            );
                        }
                    }
                }

                if (($normalized['status'] ?? '') !== 'pending') {
                    $pending = $this->billing->pendingChangesForSubscription((int)$subscription['id']);
                    $now = date('Y-m-d H:i:s');
                    foreach ($pending as $change) {
                        $effectiveAt = (string)($change['effective_at'] ?? '');
                        if ($effectiveAt !== '' && $effectiveAt > $now) {
                            continue;
                        }
                        $targetPlan = (int)($change['to_plan_id'] ?? 0);
                        if ($targetPlan > 0) {
                            $this->billing->applySubscriptionChange(
                                (int)$change['id'],
                                (int)$subscription['id'],
                                $targetPlan,
                                $normalized['current_period_end'] ?? null
                            );
                        }
                    }
                }
            }
        }

        return new Response('ok', 200, ['Content-Type' => 'text/plain']);
    }

    private function getHeaders(): array
    {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers)) {
                return $headers;
            }
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = $value;
            }
        }
        return $headers;
    }
}
