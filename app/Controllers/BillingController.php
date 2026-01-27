<?php

declare(strict_types=1);

final class BillingController
{
    private BillingService $billing;
    private WorkspaceService $workspaces;

    public function __construct(BillingService $billing, WorkspaceService $workspaces)
    {
        $this->billing = $billing;
        $this->workspaces = $workspaces;
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
        $invoices = $subscription ? $this->billing->listInvoices((int)$subscription['id']) : [];
        $plans = $this->billing->listPlans();

        return Response::html(View::render('billing/index', [
            'title' => 'Billing',
            'workspace' => $workspace,
            'subscription' => $subscription,
            'plans' => $plans,
            'invoices' => $invoices,
            'trialDays' => $this->billing->trialDays(),
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
            return Response::redirect('/billing');
        }

        $trialPlan = $this->billing->findPlanByCode('trial');
        if (!$trialPlan) {
            $_SESSION['flash']['message'] = 'Trial plan is not configured yet.';
            return Response::redirect('/billing');
        }

        $this->billing->createTrialSubscription($workspaceId, (int)$trialPlan['id'], $this->billing->trialDays());
        $_SESSION['flash']['message'] = 'Trial started.';

        return Response::redirect('/billing');
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
        if (!$plan || (int)$plan['is_active'] !== 1) {
            $_SESSION['flash']['message'] = 'Select an active plan.';
            return Response::redirect('/billing');
        }

        $subscription = $this->billing->getSubscriptionForWorkspace($workspaceId);
        $status = ((int)$plan['price_cents'] === 0) ? 'active' : 'pending';
        $periodEnd = $status === 'active'
            ? (new DateTimeImmutable('now'))->modify('+30 days')->format('Y-m-d H:i:s')
            : null;

        if ($subscription) {
            $this->billing->updateSubscriptionPlan((int)$subscription['id'], $planId, $status, $periodEnd);
        } else {
            $this->billing->createSubscription($workspaceId, $planId, $status, $periodEnd);
        }

        $_SESSION['flash']['message'] = 'Plan updated. Complete payment in the provider dashboard.';

        return Response::redirect('/billing');
    }

    public function createPlan(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        if ((Auth::user()['role'] ?? null) !== 'App Super Admin') {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $code = trim((string)$request->input('code', ''));
        $name = trim((string)$request->input('name', ''));
        $price = (int)$request->input('price_cents', 0);
        $duration = trim((string)$request->input('duration', 'monthly'));
        $isActive = $request->input('is_active') === '1';

        if ($code === '' || $name === '') {
            $_SESSION['flash']['message'] = 'Plan code and name are required.';
            return Response::redirect('/billing');
        }

        $this->billing->createPlan($code, $name, $price, $duration, $isActive);
        $_SESSION['flash']['message'] = 'Plan created.';

        return Response::redirect('/billing');
    }

    public function updatePlan(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        if ((Auth::user()['role'] ?? null) !== 'App Super Admin') {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $planId = (int)$request->input('plan_id', 0);
        $name = trim((string)$request->input('name', ''));
        $price = (int)$request->input('price_cents', 0);
        $duration = trim((string)$request->input('duration', 'monthly'));
        $isActive = $request->input('is_active') === '1';

        if ($planId <= 0 || $name === '') {
            $_SESSION['flash']['message'] = 'Plan name is required.';
            return Response::redirect('/billing');
        }

        $this->billing->updatePlan($planId, $name, $price, $duration, $isActive);
        $_SESSION['flash']['message'] = 'Plan updated.';

        return Response::redirect('/billing');
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
}
