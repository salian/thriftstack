# Billing Providers

This project supports hosted checkout providers via the `BillingProvider` interface.
To add another provider, follow this checklist.

## 1) Install the SDK
- Add the SDK to `composer.json`.
- Ensure deploy runs `composer install` (already in `.github/workflows/deploy.yml`).

## 2) Implement a provider class
- Create `app/Billing/Providers/<ProviderName>Provider.php`.
- Implement `BillingProvider`:
  - `createCheckout(array $payload): array`
  - `verifySignature(string $payload, array $headers): bool`
  - `parseWebhook(array $payload): array`
  - `eventType(array $payload): string`
- Use metadata/custom fields to include:
  - `subscription_id`
  - `change_id` (if a plan change)
  - `plan_id`

## 3) Wire it into the app
- Register the provider in `routes/web.php`:
  - `$billingProviders['provider_key'] = new ProviderClass($gatewaySettingsService);`
- Add it to `BillingController::providerPlanKey()` and anywhere provider names are enumerated.
- Add to `BillingGatewaySelector` priority defaults (and seeder).

## 4) Add admin configuration UI
- Add a tab in `views/admin/payment_gateways/index.php`.
- Update `PaymentGatewaysController::$allowedKeys` to accept the new settings.
- Include a “How to set up…” helper with:
  - Required keys
  - Webhook URL (use `${APP_URL}/webhooks/<provider>`)

## 5) Store plan/provider identifiers
- Add a plan mapping field (e.g., `provider_plan_id`) to `plans` in:
  - `app/Database/Migrations/0001_initial.php`
  - `app/Database/Seeder.php`
- Update the billing plans modal in `views/admin/billing_plans/index.php`
  - Add input
  - Add data attribute for edit mode
  - Update `public/assets/js/ui.js` bindings
  - For Dodo and Paddle, this repo uses `dodo_price_id` and `paddle_price_id`. For Dodo, the value is passed as the checkout `productID`.

## 6) Webhook handling
- Ensure `parseWebhook()` returns:
  - `provider_subscription_id`, `provider_customer_id`, `provider_status`
  - `status`, `current_period_end`, `trial_ends_at`
  - Optional invoice info (`invoice_id`, `invoice_amount`, `invoice_status`)
  - `gateway_event_status` (`success` or `failed`) for failure‑rate routing
- Verify webhook signature using provider docs.

## 7) Tests and docs
- Update docs in `docs/installation/SETUP_GUIDE.md`.
- Add any relevant manual test steps in `docs/testing/MANUAL_TEST_PLAN.md`.
- Update `CHANGELOG.md`.
