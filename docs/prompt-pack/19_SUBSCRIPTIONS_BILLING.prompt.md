# 19_SUBSCRIPTIONS_BILLING — Subscriptions and billing

Implement subscriptions with trials, plans, and webhooks.

## Requirements
- Plans: Free/Trial/Pro/Business (editable).
- Subscriptions per workspace.
- Trial period with expiry.
- Webhooks for Razorpay, Stripe, PayPal, Lemon Squeezy.
- Payment provider abstraction (future SMTP/API style).
- Secure webhook signature verification.

## Create/update
- /app/Billing/BillingService.php
- /app/Billing/Providers/*.php
- /app/Controllers/BillingController.php
- /app/Controllers/WebhooksController.php
- /app/Database/Migrations/0006_billing.php
- /views/billing/*.php
- /routes/web.php

## Data model
- plans: id, code, name, price_cents, interval, is_active
- subscriptions: id, workspace_id, plan_id, status, trial_ends_at, current_period_end, created_at
- invoices: id, subscription_id, amount_cents, status, provider, external_id, created_at
- webhook_events: id, provider, event_type, payload, received_at

## Deliverable
All file contents.
