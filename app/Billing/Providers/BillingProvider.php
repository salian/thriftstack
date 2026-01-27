<?php

declare(strict_types=1);

interface BillingProvider
{
    public function name(): string;

    public function verifySignature(string $payload, array $headers): bool;

    public function eventType(array $payload): string;

    /**
     * Create a hosted checkout session.
     *
     * @param array $payload
     * @return array{checkout_url:string, checkout_id:string|null, provider_subscription_id:string|null, provider_customer_id:string|null, provider_status:string|null}
     */
    public function createCheckout(array $payload): array;

    /**
     * Normalize webhook payload into subscription updates.
     *
     * @param array $payload
     * @return array{event_type:string, subscription_id:int|null, change_id:int|null, target_plan_id:int|null, provider_subscription_id:string|null, provider_checkout_id:string|null, provider_customer_id:string|null, provider_status:string|null, status:string|null, current_period_start:string|null, current_period_end:string|null, trial_ends_at:string|null, invoice_amount:int|null, invoice_status:string|null, invoice_id:string|null, gateway_event_status:string|null}
     */
    public function parseWebhook(array $payload): array;
}
