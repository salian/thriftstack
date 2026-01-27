<?php

declare(strict_types=1);

final class BillingController
{
    private BillingService $billing;
    private WorkspaceService $workspaces;
    private AppSettingsService $appSettings;
    private PaymentGatewaySettingsService $gatewaySettings;
    private BillingGatewaySelector $gatewaySelector;
    /** @var array<string, BillingProvider> */
    private array $providers;

    /**
     * @param array<string, BillingProvider> $providers
     */
    public function __construct(
        BillingService $billing,
        WorkspaceService $workspaces,
        AppSettingsService $appSettings,
        PaymentGatewaySettingsService $gatewaySettings,
        BillingGatewaySelector $gatewaySelector,
        array $providers
    ) {
        $this->billing = $billing;
        $this->workspaces = $workspaces;
        $this->appSettings = $appSettings;
        $this->gatewaySettings = $gatewaySettings;
        $this->gatewaySelector = $gatewaySelector;
        $this->providers = $providers;
    }

    public function index(Request $request): Response
    {
        $userId = (int)($request->session('user')['id'] ?? 0);
        $workspace = $this->resolveBillingWorkspace($userId);
        if (!$workspace) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }
        $workspaceId = (int)$workspace['id'];

        $subscription = $workspaceId > 0 ? $this->billing->getSubscriptionForWorkspace($workspaceId) : null;
        if ($subscription) {
            $this->applyDueChanges($subscription);
            $subscription = $this->billing->getSubscriptionForWorkspace($workspaceId);
        }
        $invoices = $subscription ? $this->billing->listInvoices((int)$subscription['id']) : [];
        $plans = $this->billing->listPlans();
        $topupPlans = $this->billing->listTopupPlans();
        $pendingChanges = $subscription ? $this->billing->pendingChangesForSubscription((int)$subscription['id']) : [];
        $allPlans = $subscription ? $this->billing->listPlans(true) : [];
        if ($subscription && (int)($subscription['plan_id'] ?? 0) > 0) {
            foreach ($allPlans as $plan) {
                if ((int)$plan['id'] === (int)$subscription['plan_id'] && (int)$plan['is_active'] !== 1) {
                    $plans[] = $plan;
                    break;
                }
            }
        }

        $topupPurchases = $workspaceId > 0 ? $this->billing->listTopupPurchases($workspaceId) : [];

        return Response::html(View::render('billing/index', [
            'title' => 'Billing',
            'workspace' => $workspace,
            'subscription' => $subscription,
            'plans' => $plans,
            'topupPlans' => $topupPlans,
            'pendingChanges' => $pendingChanges,
            'planIndex' => $this->indexPlansById($allPlans),
            'invoices' => $invoices,
            'trialDays' => $this->billing->trialDays(),
            'topupPurchases' => $topupPurchases,
        ]));
    }

    public function startTrial(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $userId = (int)($request->session('user')['id'] ?? 0);
        $workspace = $this->resolveBillingWorkspace($userId);
        if (!$workspace) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }
        $workspaceId = (int)$workspace['id'];

        $existing = $this->billing->getSubscriptionForWorkspace($workspaceId);
        if ($existing) {
            $_SESSION['flash']['message'] = 'Subscription already exists for this workspace.';
            return Response::redirect('/super-admin/billing-plans');
        }

        $trialPlan = $this->billing->findPlanByCode('trial');
        if (!$trialPlan) {
            $_SESSION['flash']['message'] = 'Trial plan is not configured yet.';
            return Response::redirect('/super-admin/billing-plans');
        }
        if (($trialPlan['plan_type'] ?? 'subscription') !== 'subscription') {
            $_SESSION['flash']['message'] = 'Trial plan must be a subscription plan.';
            return Response::redirect('/billing');
        }

        return $this->beginCheckout($workspace, $trialPlan, $request, $this->billing->trialDays());
    }

    public function selectPlan(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $userId = (int)($request->session('user')['id'] ?? 0);
        $workspace = $this->resolveBillingWorkspace($userId);
        if (!$workspace) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }
        $workspaceId = (int)$workspace['id'];

        $planId = (int)$request->input('plan_id', 0);
        $plan = $planId > 0 ? $this->billing->findPlan($planId) : null;
        if (!$plan) {
            $_SESSION['flash']['message'] = 'Select a valid plan.';
            return Response::redirect('/billing');
        }
        if (($plan['plan_type'] ?? 'subscription') !== 'subscription') {
            $_SESSION['flash']['message'] = 'Select a subscription plan.';
            return Response::redirect('/billing');
        }

        $subscription = $this->billing->getSubscriptionForWorkspace($workspaceId);
        if ($subscription && (int)$subscription['plan_id'] === $planId) {
            $_SESSION['flash']['message'] = 'This plan is already active for your workspace.';
            return Response::redirect('/billing');
        }

        $isActive = (int)($plan['is_active'] ?? 0) === 1;
        $isGrandfathered = (int)($plan['is_grandfathered'] ?? 0) === 1;
        if (!$isActive && !($isGrandfathered && $subscription && (int)$subscription['plan_id'] === $planId)) {
            $_SESSION['flash']['message'] = 'Select an active plan.';
            return Response::redirect('/billing');
        }

        return $this->beginCheckout($workspace, $plan, $request, null);
    }

    public function purchaseTopup(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $userId = (int)($request->session('user')['id'] ?? 0);
        $workspace = $this->resolveBillingWorkspace($userId);
        if (!$workspace) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $planId = (int)$request->input('plan_id', 0);
        $plan = $planId > 0 ? $this->billing->findPlan($planId) : null;
        if (!$plan || ($plan['plan_type'] ?? '') !== 'topup') {
            $_SESSION['flash']['message'] = 'Select a valid top-up plan.';
            return Response::redirect('/billing');
        }
        if ((int)($plan['is_active'] ?? 0) !== 1) {
            $_SESSION['flash']['message'] = 'Select an active top-up plan.';
            return Response::redirect('/billing');
        }

        return $this->beginTopupCheckout($workspace, $plan, $request);
    }

    public function createPlan(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        if ((int)(Auth::user()['is_system_admin'] ?? 0) !== 1) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $code = trim((string)$request->input('code', ''));
        $name = trim((string)$request->input('name', ''));
        $price = (int)$request->input('price_cents', 0);
        $duration = trim((string)$request->input('duration', 'monthly'));
        $planType = trim((string)$request->input('plan_type', 'subscription'));
        $aiCredits = (int)$request->input('ai_credits', 0);
        if (!in_array($planType, ['subscription', 'topup'], true)) {
            $planType = 'subscription';
        }
        $isActive = $request->input('is_active') === '1';
        $isGrandfathered = $request->input('is_grandfathered') === '1';
        $providerIds = [
            'stripe' => trim((string)$request->input('stripe_price_id', '')),
            'razorpay' => trim((string)$request->input('razorpay_plan_id', '')),
            'paypal' => trim((string)$request->input('paypal_plan_id', '')),
            'lemonsqueezy' => trim((string)$request->input('lemonsqueezy_variant_id', '')),
            'dodo' => trim((string)$request->input('dodo_price_id', '')),
            'paddle' => trim((string)$request->input('paddle_price_id', '')),
        ];

        if ($code === '' || $name === '') {
            $_SESSION['flash']['message'] = 'Plan code and name are required.';
            return Response::redirect('/billing');
        }

        $currency = (string)$this->appSettings->get('billing.currency', 'USD');
        $this->billing->createPlan($code, $name, $price, $currency, $duration, $planType, $aiCredits, $isActive, $providerIds, $isGrandfathered);
        $_SESSION['flash']['message'] = 'Plan created.';

        return Response::redirect('/super-admin/billing-plans');
    }

    public function updatePlan(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        if ((int)(Auth::user()['is_system_admin'] ?? 0) !== 1) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $planId = (int)$request->input('plan_id', 0);
        $name = trim((string)$request->input('name', ''));
        $price = (int)$request->input('price_cents', 0);
        $duration = trim((string)$request->input('duration', 'monthly'));
        $planType = trim((string)$request->input('plan_type', 'subscription'));
        $aiCredits = (int)$request->input('ai_credits', 0);
        if (!in_array($planType, ['subscription', 'topup'], true)) {
            $planType = 'subscription';
        }
        $isActive = $request->input('is_active') === '1';
        $isGrandfathered = $request->input('is_grandfathered') === '1';
        $providerIds = [
            'stripe' => trim((string)$request->input('stripe_price_id', '')),
            'razorpay' => trim((string)$request->input('razorpay_plan_id', '')),
            'paypal' => trim((string)$request->input('paypal_plan_id', '')),
            'lemonsqueezy' => trim((string)$request->input('lemonsqueezy_variant_id', '')),
            'dodo' => trim((string)$request->input('dodo_price_id', '')),
            'paddle' => trim((string)$request->input('paddle_price_id', '')),
        ];

        if ($planId <= 0 || $name === '') {
            $_SESSION['flash']['message'] = 'Plan name is required.';
            return Response::redirect('/super-admin/billing-plans');
        }

        $this->billing->updatePlan($planId, $name, $price, $duration, $planType, $aiCredits, $isActive, $providerIds, $isGrandfathered);
        $_SESSION['flash']['message'] = 'Plan updated.';

        return Response::redirect('/super-admin/billing-plans');
    }

    public function paddleCheckout(Request $request): Response
    {
        $checkout = $_SESSION['paddle_checkout'] ?? null;
        if (!is_array($checkout)) {
            $_SESSION['flash']['message'] = 'Checkout session expired.';
            return Response::redirect('/billing');
        }

        unset($_SESSION['paddle_checkout']);

        $settings = $this->gatewaySettings->getProviderSettings('paddle');
        $clientToken = (string)($settings['client_token'] ?? '');
        $environment = (string)($settings['environment'] ?? 'live');
        if ($clientToken === '') {
            $_SESSION['flash']['message'] = 'Paddle client token is missing.';
            return Response::redirect('/billing');
        }

        return Response::html(View::render('billing/paddle_checkout', [
            'title' => 'Paddle Checkout',
            'clientToken' => $clientToken,
            'environment' => $environment,
            'priceId' => (string)($checkout['price_id'] ?? ''),
            'subscriptionId' => (int)($checkout['subscription_id'] ?? 0),
            'changeId' => (int)($checkout['change_id'] ?? 0),
            'planId' => (int)($checkout['plan_id'] ?? 0),
            'purchaseId' => (int)($checkout['purchase_id'] ?? 0),
            'mode' => (string)($checkout['mode'] ?? 'subscription'),
            'successUrl' => (string)($checkout['success_url'] ?? ''),
            'cancelUrl' => (string)($checkout['cancel_url'] ?? ''),
            'customerEmail' => (string)($checkout['customer_email'] ?? ''),
        ]));
    }

    private function resolveBillingWorkspace(int $userId): ?array
    {
        $currentId = $this->workspaces->currentWorkspaceId();
        if ($currentId) {
            $role = $this->workspaces->membershipRole($userId, $currentId);
            if ($role !== null && in_array('billing.manage', $this->workspaces->workspacePermissionsForRole($role), true)) {
                $current = $this->workspaces->getWorkspace($currentId);
                if ($current) {
                    $current['role'] = $role;
                    return $current;
                }
            }
        }

        $workspaces = $this->workspaces->listForUser($userId);
        foreach ($workspaces as $workspace) {
            $role = (string)($workspace['role'] ?? '');
            if ($role !== '' && in_array('billing.manage', $this->workspaces->workspacePermissionsForRole($role), true)) {
                return $workspace;
            }
        }

        return null;
    }

    private function beginCheckout(array $workspace, array $plan, Request $request, ?int $trialDaysOverride): Response
    {
        $workspaceId = (int)$workspace['id'];
        $subscription = $this->billing->getSubscriptionForWorkspace($workspaceId);
        $currentPlanId = $subscription ? (int)$subscription['plan_id'] : null;
        $currentPrice = $subscription ? (int)($subscription['price_cents'] ?? 0) : 0;
        $newPrice = (int)($plan['price_cents'] ?? 0);

        $changeType = null;
        if ($subscription) {
            $changeType = $newPrice > $currentPrice ? 'upgrade' : 'downgrade';
        }

        if ($subscription && $changeType === 'downgrade') {
            $effectiveAt = $subscription['current_period_end'] ?? null;
            if ($effectiveAt === null || $effectiveAt === '') {
                $effectiveAt = $this->periodEndFromDuration((string)($subscription['duration'] ?? 'monthly'));
            }
            $this->billing->createSubscriptionChange(
                (int)$subscription['id'],
                $currentPlanId,
                (int)$plan['id'],
                'downgrade',
                'pending',
                $effectiveAt,
                null,
                null
            );
            $_SESSION['flash']['message'] = 'Downgrade scheduled for the end of the current billing period.';
            return Response::redirect('/billing');
        }

        $provider = $this->gatewaySelector->selectProvider();
        if ($provider === null || !isset($this->providers[$provider])) {
            $_SESSION['flash']['message'] = 'No active payment gateways available.';
            return Response::redirect('/billing');
        }

        $providerSettings = $this->gatewaySettings->getProviderSettings($provider);
        if (($providerSettings['enabled'] ?? '') !== '1') {
            $_SESSION['flash']['message'] = 'Selected payment gateway is not enabled.';
            return Response::redirect('/billing');
        }

        $planKey = $this->providerPlanKey($provider);
        if ($planKey !== null && empty($plan[$planKey])) {
            $_SESSION['flash']['message'] = 'Plan is missing provider configuration for ' . ucfirst($provider) . '.';
            return Response::redirect('/billing');
        }

        $trialDays = $trialDaysOverride ?? 0;
        if ($trialDays === 0 && ($plan['code'] ?? '') === 'trial') {
            $trialDays = $this->billing->trialDays();
        }

        $appUrl = (string)($GLOBALS['config']['app']['url'] ?? '');
        $successUrl = $appUrl . '/billing?status=success';
        $cancelUrl = $appUrl . '/billing?status=cancelled';
        $user = $request->session('user') ?? [];
        $customerEmail = (string)($user['email'] ?? '');

        $changeId = null;
        if ($subscription) {
            $changeId = $this->billing->createSubscriptionChange(
                (int)$subscription['id'],
                $currentPlanId,
                (int)$plan['id'],
                'upgrade',
                'pending',
                date('Y-m-d H:i:s'),
                $provider,
                null
            );
        }

        if ($newPrice === 0 && $trialDays === 0) {
            if ($subscription) {
                $this->billing->updateSubscriptionPlan((int)$subscription['id'], (int)$plan['id'], 'active', null);
                $this->billing->grantSubscriptionCreditsIfEligible((int)$subscription['id'], 'active');
            } else {
                $subscriptionId = $this->billing->createSubscription($workspaceId, (int)$plan['id'], 'active', null);
                $this->billing->grantSubscriptionCreditsIfEligible($subscriptionId, 'active');
            }
            $_SESSION['flash']['message'] = 'Plan updated.';
            return Response::redirect('/billing');
        }

        $subscriptionId = $subscription ? (int)$subscription['id'] : $this->billing->createPendingSubscription(
            $workspaceId,
            (int)$plan['id'],
            $provider,
            (string)($plan['currency'] ?? 'USD'),
            'recurring',
            null,
            'initiated'
        );

        if ($provider === 'paddle') {
            $paddleSettings = $this->gatewaySettings->getProviderSettings('paddle');
            $clientToken = (string)($paddleSettings['client_token'] ?? '');
            if ($clientToken === '') {
                $_SESSION['flash']['message'] = 'Paddle client token is missing.';
                return Response::redirect('/billing');
            }

            $this->billing->recordGatewayEvent($subscriptionId, $provider, 'pending', 'checkout.initiated');
            $this->billing->updateSubscriptionProvider($subscriptionId, [
                'provider' => $provider,
            ]);
            if ($changeId !== null) {
                $this->billing->updateSubscriptionChange($changeId, [
                    'provider' => $provider,
                ]);
            }

            $_SESSION['paddle_checkout'] = [
                'subscription_id' => $subscriptionId,
                'change_id' => $changeId,
                'plan_id' => (int)$plan['id'],
                'price_id' => (string)$plan['paddle_price_id'],
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'customer_email' => $customerEmail,
            ];

            return Response::redirect('/billing/paddle-checkout');
        }

        try {
            $checkout = $this->providers[$provider]->createCheckout([
                'plan' => $plan,
                'workspace' => $workspace,
                'subscription_id' => $subscriptionId,
                'customer_email' => $customerEmail,
                'trial_days' => $trialDays,
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'change_id' => $changeId,
            ]);
        } catch (Throwable $e) {
            $this->billing->recordGatewayEvent($subscriptionId, $provider, 'failed', 'checkout.failed');
            $_SESSION['flash']['message'] = 'Unable to start checkout with the selected provider.';
            return Response::redirect('/billing');
        }

        $this->billing->recordGatewayEvent($subscriptionId, $provider, 'pending', 'checkout.initiated');
        $this->billing->updateSubscriptionProvider($subscriptionId, [
            'provider' => $provider,
            'provider_checkout_id' => $checkout['checkout_id'] ?? null,
            'provider_subscription_id' => $checkout['provider_subscription_id'] ?? null,
            'provider_customer_id' => $checkout['provider_customer_id'] ?? null,
            'provider_status' => $checkout['provider_status'] ?? null,
        ]);

        if ($changeId !== null) {
            $this->billing->updateSubscriptionChange($changeId, [
                'provider' => $provider,
                'provider_checkout_id' => $checkout['checkout_id'] ?? null,
            ]);
        }

        if (empty($checkout['checkout_url'])) {
            $_SESSION['flash']['message'] = 'Unable to start checkout with the selected provider.';
            return Response::redirect('/billing');
        }

        return Response::redirect($checkout['checkout_url']);
    }

    private function beginTopupCheckout(array $workspace, array $plan, Request $request): Response
    {
        $supportedProviders = ['stripe', 'razorpay', 'paypal', 'lemonsqueezy', 'dodo', 'paddle'];
        $provider = $this->gatewaySelector->selectProviderFor($supportedProviders);
        if ($provider === null) {
            $_SESSION['flash']['message'] = 'No payment gateway is available for top-ups.';
            return Response::redirect('/billing');
        }

        $planKey = $this->providerPlanKey($provider);
        $needsPriceId = in_array($provider, ['stripe', 'lemonsqueezy', 'dodo', 'paddle'], true);
        if ($needsPriceId && $planKey !== null && empty($plan[$planKey])) {
            $_SESSION['flash']['message'] = 'Selected payment gateway is missing a price id for this top-up.';
            return Response::redirect('/billing');
        }

        $workspaceId = (int)($workspace['id'] ?? 0);
        $credits = (int)($plan['ai_credits'] ?? 0);
        $amount = (int)($plan['price_cents'] ?? 0);
        $currency = (string)($plan['currency'] ?? 'USD');

        $purchaseId = $this->billing->createTopupPurchase(
            $workspaceId,
            (int)$plan['id'],
            $credits,
            $amount,
            $currency,
            $provider,
            null,
            'initiated'
        );

        $successUrl = rtrim((string)config('app.url', ''), '/') . '/billing?status=success';
        $cancelUrl = rtrim((string)config('app.url', ''), '/') . '/billing?status=cancelled';
        $customerEmail = (string)($request->session('user')['email'] ?? '');

        if ($provider === 'paddle') {
            $paddleSettings = $this->gatewaySettings->getProviderSettings('paddle');
            $clientToken = (string)($paddleSettings['client_token'] ?? '');
            if ($clientToken === '') {
                $_SESSION['flash']['message'] = 'Paddle client token is missing.';
                return Response::redirect('/billing');
            }

            $this->billing->updateTopupPurchase($purchaseId, [
                'provider' => $provider,
            ]);

            $_SESSION['paddle_checkout'] = [
                'purchase_id' => $purchaseId,
                'plan_id' => (int)$plan['id'],
                'price_id' => (string)$plan['paddle_price_id'],
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'customer_email' => $customerEmail,
                'mode' => 'topup',
            ];

            return Response::redirect('/billing/paddle-checkout');
        }

        try {
            $checkout = $this->providers[$provider]->createCheckout([
                'plan' => $plan,
                'workspace' => $workspace,
                'customer_email' => $customerEmail,
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'purchase_id' => $purchaseId,
                'mode' => 'payment',
            ]);
        } catch (Throwable $e) {
            $this->billing->recordGatewayEvent(null, $provider, 'failed', 'topup.checkout.failed', $purchaseId);
            $_SESSION['flash']['message'] = 'Unable to start checkout for this top-up.';
            return Response::redirect('/billing');
        }

        $this->billing->recordGatewayEvent(null, $provider, 'pending', 'topup.checkout.initiated', $purchaseId);
        $this->billing->updateTopupPurchase($purchaseId, [
            'provider_checkout_id' => $checkout['checkout_id'] ?? null,
            'provider_customer_id' => $checkout['provider_customer_id'] ?? null,
            'provider_status' => $checkout['provider_status'] ?? null,
        ]);

        if (empty($checkout['checkout_url'])) {
            $_SESSION['flash']['message'] = 'Unable to start checkout for this top-up.';
            return Response::redirect('/billing');
        }

        return Response::redirect($checkout['checkout_url']);
    }

    private function providerPlanKey(string $provider): ?string
    {
        return match ($provider) {
            'stripe' => 'stripe_price_id',
            'razorpay' => 'razorpay_plan_id',
            'paypal' => 'paypal_plan_id',
            'lemonsqueezy' => 'lemonsqueezy_variant_id',
            'dodo' => 'dodo_price_id',
            'paddle' => 'paddle_price_id',
            default => null,
        };
    }

    private function periodEndFromDuration(string $duration): string
    {
        $base = new DateTimeImmutable('now');
        return match ($duration) {
            'yearly' => $base->modify('+1 year')->format('Y-m-d H:i:s'),
            'weekly' => $base->modify('+1 week')->format('Y-m-d H:i:s'),
            default => $base->modify('+1 month')->format('Y-m-d H:i:s'),
        };
    }

    private function applyDueChanges(array $subscription): void
    {
        $subscriptionId = (int)($subscription['id'] ?? 0);
        if ($subscriptionId <= 0) {
            return;
        }
        $pending = $this->billing->pendingChangesForSubscription($subscriptionId);
        if (empty($pending)) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        foreach ($pending as $change) {
            $effectiveAt = (string)($change['effective_at'] ?? '');
            if ($effectiveAt !== '' && $effectiveAt > $now) {
                continue;
            }
            $targetPlan = (int)($change['to_plan_id'] ?? 0);
            if ($targetPlan > 0) {
                $this->billing->applySubscriptionChange((int)$change['id'], $subscriptionId, $targetPlan, $effectiveAt ?: null);
            }
        }
    }

    private function indexPlansById(array $plans): array
    {
        $index = [];
        foreach ($plans as $plan) {
            $id = (int)($plan['id'] ?? 0);
            if ($id > 0) {
                $index[$id] = $plan;
            }
        }
        return $index;
    }
}
