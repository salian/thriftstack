<?php

declare(strict_types=1);

$router = new Router();
$config = $GLOBALS['config'] ?? [];
$pdo = DB::connect($config);
$authController = new AuthController($pdo, $config);
$workspaceService = new WorkspaceService($pdo, new Audit($pdo));
$workspaceController = new WorkspaceController($workspaceService);
$workspaceInviteController = new WorkspaceInviteController($workspaceService, $workspaceController, $config);
$settingsService = new SettingsService($pdo);
$settingsController = new SettingsController($pdo, $settingsService);
$workspaceSettingsService = new WorkspaceSettingsService($pdo);
$reportPreferencesController = new ReportPreferencesController($pdo, $workspaceSettingsService, $workspaceService);
$apiTokensController = new ApiTokensController($pdo);
$creditLimitsController = new CreditLimitsController($pdo);
$notificationService = new NotificationService($pdo, $config);
$notificationsController = new NotificationsController($notificationService);
$analyticsController = null;
$billingService = new BillingService($pdo, $config);
$appSettingsService = new AppSettingsService($pdo);
$analyticsController = new AnalyticsController($pdo, $appSettingsService);
$gatewaySettingsService = new PaymentGatewaySettingsService($pdo);
$gatewaySelector = new BillingGatewaySelector($pdo);
$billingProviders = [
    'stripe' => new StripeProvider($gatewaySettingsService),
    'razorpay' => new RazorpayProvider($gatewaySettingsService),
    'paypal' => new PayPalProvider($gatewaySettingsService),
    'lemonsqueezy' => new LemonSqueezyProvider($gatewaySettingsService),
    'dodo' => new DodoProvider($gatewaySettingsService),
    'paddle' => new PaddleProvider($gatewaySettingsService),
];
$billingController = new BillingController(
    $billingService,
    $workspaceService,
    $appSettingsService,
    $gatewaySettingsService,
    $gatewaySelector,
    $billingProviders
);
$webhooksController = new WebhooksController($billingService, $billingProviders);
$consumeController = new ConsumeController($pdo);
// Admin controllers need to be defined before any route handlers that use them.
$workspacePermissionsController = new WorkspacePermissionsController($pdo);
$paymentGatewaysController = new PaymentGatewaysController($pdo);
$userRolesController = new UserRolesController($pdo);
$usersController = new UsersController($pdo);
$auditController = new AuditLogController($pdo);
$uploadController = new UploadController($pdo, __DIR__ . '/../storage');
$workspaceAnalyticsController = new WorkspaceAnalyticsController($pdo, $workspaceSettingsService);
$financialAnalyticsController = new FinancialAnalyticsController($pdo);

$router
    ->get('/', static function (Request $request) use ($config) {
        return View::render('home', ['title' => $config['app']['name'] ?? 'ThriftStack']);
    })
    ->setName('home');

$router
    ->get('/login', static function (Request $request) use ($authController) {
        return $authController->showLogin($request);
    })
    ->setName('login');


$router
    ->post('/login', static function (Request $request) use ($authController) {
        return $authController->login($request);
    });

$router
    ->post('/logout', static function (Request $request) use ($authController) {
        return $authController->logout($request);
    })
    ->middleware(new AuthRequired())
    ->setName('logout');

$router
    ->get('/signup', static function (Request $request) use ($authController) {
        return $authController->showSignup($request);
    })
    ->setName('signup');

$router
    ->post('/signup', static function (Request $request) use ($authController) {
        return $authController->signup($request);
    });

$router
    ->get('/verify', static function (Request $request) use ($authController) {
        return $authController->verify($request);
    })
    ->setName('verify');

$router
    ->get('/forgot', static function (Request $request) use ($authController) {
        return $authController->showForgot($request);
    })
    ->setName('forgot');

$router
    ->post('/forgot', static function (Request $request) use ($authController) {
        return $authController->sendReset($request);
    });

$router
    ->get('/reset', static function (Request $request) use ($authController) {
        return $authController->showReset($request);
    })
    ->setName('reset');

$router
    ->post('/reset', static function (Request $request) use ($authController) {
        return $authController->reset($request);
    });

$router
    ->get('/dashboard', static function (Request $request) {
        return View::render('dashboard', ['title' => 'Dashboard']);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo))
    ->setName('dashboard');

$router
    ->get('/tasks', static function (Request $request) {
        return View::render('tasks', ['title' => 'Tasks']);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo))
    ->setName('tasks');

$router
    ->get('/reports', static function (Request $request) {
        return View::render('reports', ['title' => 'Reports']);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo))
    ->setName('reports');

$router
    ->get('/billing', static function (Request $request) use ($billingController) {
        return $billingController->index($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo))
    ->middleware(new RequireWorkspacePermission($pdo, 'billing.manage'))
    ->setName('billing');

$router
    ->post('/billing/trial', static function (Request $request) use ($billingController) {
        return $billingController->startTrial($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo))
    ->middleware(new RequireWorkspacePermission($pdo, 'billing.manage'));

$router
    ->post('/billing/subscribe', static function (Request $request) use ($billingController) {
        return $billingController->selectPlan($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo))
    ->middleware(new RequireWorkspacePermission($pdo, 'billing.manage'));

$router
    ->post('/billing/topups', static function (Request $request) use ($billingController) {
        return $billingController->purchaseTopup($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo))
    ->middleware(new RequireWorkspacePermission($pdo, 'billing.manage'));

$router
    ->post('/api/credits/consume', static function (Request $request) use ($consumeController) {
        return $consumeController->consume($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo));

$router
    ->get('/api/credits/balance', static function (Request $request) use ($consumeController) {
        return $consumeController->balance($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo));

$router
    ->get('/billing/paddle-checkout', static function (Request $request) use ($billingController) {
        return $billingController->paddleCheckout($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo))
    ->middleware(new RequireWorkspacePermission($pdo, 'billing.manage'));

$router
    ->post('/billing/plans', static function (Request $request) use ($billingController) {
        return $billingController->createPlan($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'))
    ->middleware(new RequireSystemAccess());

$router
    ->post('/billing/plans/update', static function (Request $request) use ($billingController) {
        return $billingController->updatePlan($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'))
    ->middleware(new RequireSystemAccess());

$router
    ->get('/super-admin', static function () {
        return Response::redirect('/super-admin/analytics');
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'))
    ->middleware(new RequireSystemAccess())
    ->setName('super_admin');

$router
    ->get('/super-admin/analytics', static function (Request $request) use ($analyticsController) {
        return $analyticsController->index($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'))
    ->middleware(new RequireSystemAccess())
    ->setName('super_admin.analytics');

$router
    ->post('/super-admin/analytics/alerts', static function (Request $request) use ($appSettingsService) {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }
        $enabled = $request->input('velocity_alert_enabled') === '1';
        $threshold = (int)$request->input('velocity_threshold_percent', 50);
        if ($threshold < 10) {
            $threshold = 10;
        }
        if ($threshold > 500) {
            $threshold = 500;
        }
        $appSettingsService->set('analytics.velocity.enabled', $enabled ? '1' : '0');
        $appSettingsService->set('analytics.velocity.threshold_percent', (string)$threshold);
        return Response::redirect('/super-admin/analytics');
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'))
    ->middleware(new RequireSystemAdmin());

$router
    ->get('/super-admin/analytics/financial', static function (Request $request) use ($financialAnalyticsController) {
        return $financialAnalyticsController->index($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'))
    ->middleware(new RequireSystemAdmin())
    ->setName('super_admin.analytics_financial');

$router
    ->get('/super-admin/analytics/financial/export', static function (Request $request) use ($financialAnalyticsController) {
        return $financialAnalyticsController->export($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'))
    ->middleware(new RequireSystemAdmin())
    ->setName('super_admin.analytics_financial_export');

$router
    ->get('/super-admin/usage', static function (Request $request) use ($pdo) {
        $countSuper = $pdo->query('SELECT COUNT(*) FROM users WHERE is_system_admin = 1');
        $superAdminCount = $countSuper ? (int)$countSuper->fetchColumn() : 0;

        $search = trim((string)$request->query('search', ''));
        $selectedAccess = (string)$request->query('system_access', 'all');
        $startParam = trim((string)$request->query('start', ''));
        $endParam = trim((string)$request->query('end', ''));
        $startDate = $startParam !== '' ? DateTimeImmutable::createFromFormat('Y-m-d', $startParam) : null;
        $endDate = $endParam !== '' ? DateTimeImmutable::createFromFormat('Y-m-d', $endParam) : null;
        if ($startDate && $endDate && $startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }
        $page = max(1, (int)$request->query('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $conditions = [];
        $params = [];
        if ($search !== '') {
            $conditions[] = '(u.name LIKE ? OR u.email LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        if ($selectedAccess !== '' && $selectedAccess !== 'all') {
            if ($selectedAccess === 'admin') {
                $conditions[] = 'u.is_system_admin = 1';
            } elseif ($selectedAccess === 'staff') {
                $conditions[] = 'u.is_system_staff = 1 AND u.is_system_admin = 0';
            } elseif ($selectedAccess === 'standard') {
                $conditions[] = 'u.is_system_admin = 0 AND u.is_system_staff = 0';
            }
        }
        if ($startDate) {
            $conditions[] = 'u.created_at >= ?';
            $params[] = $startDate->setTime(0, 0, 0)->format('Y-m-d H:i:s');
        }
        if ($endDate) {
            $conditions[] = 'u.created_at <= ?';
            $params[] = $endDate->setTime(23, 59, 59)->format('Y-m-d H:i:s');
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $countSql = 'SELECT COUNT(*) FROM users u ' . $where;
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();
        $totalPages = max(1, (int)ceil($total / $limit));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $limit;

        $sql = 'SELECT u.id, u.name, u.email, u.status, u.is_system_admin, u.is_system_staff
            FROM users u
            ' . $where . '
            ORDER BY u.name ASC
            LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return View::render('admin/usage/index', [
            'title' => 'App Usage',
            'users' => $users,
            'search' => $search,
            'selectedAccess' => $selectedAccess,
            'start' => $startDate ? $startDate->format('Y-m-d') : '',
            'end' => $endDate ? $endDate->format('Y-m-d') : '',
            'page' => $page,
            'total' => $total,
            'totalPages' => $totalPages,
            'limit' => $limit,
            'superAdminCount' => $superAdminCount,
        ]);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'))
    ->middleware(new RequireSystemAccess())
    ->setName('super_admin.usage');

$router
    ->get('/super-admin/billing-plans', static function () use ($billingService) {
        return View::render('admin/billing_plans/index', [
            'title' => 'Billing Plans',
            'plans' => $billingService->listPlans(true),
        ]);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'))
    ->middleware(new RequireSystemAccess())
    ->setName('super_admin.billing_plans');

$router
    ->get('/super-admin/payment-gateways', static function (Request $request) use ($paymentGatewaysController) {
        return $paymentGatewaysController->index($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'))
    ->middleware(new RequireSystemAdmin())
    ->setName('super_admin.payment_gateways');

$router
    ->get('/super-admin/settings', static function () use ($pdo) {
        $rbac = new Rbac($pdo);
        $workspaceRoles = ['Workspace Owner', 'Workspace Admin', 'Workspace Member'];
        $workspacePermissions = $rbac->workspacePermissions();
        $workspacePermissionsByRole = $rbac->workspacePermissionsByRole();
        $appSettings = new AppSettingsService($pdo);

        return View::render('admin/site_settings/index', [
            'title' => 'Site Settings',
            'workspaceRoles' => $workspaceRoles,
            'workspacePermissions' => $workspacePermissions,
            'workspacePermissionsByRole' => $workspacePermissionsByRole,
            'appSettings' => $appSettings,
        ]);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'))
    ->middleware(new RequireSystemAccess())
    ->setName('super_admin.settings');

$router
    ->post('/webhooks/{provider}', static function (Request $request) use ($webhooksController) {
        return $webhooksController->handle($request);
    });

$router
    ->get('/notifications', static function (Request $request) use ($notificationsController) {
        return $notificationsController->index($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo))
    ->setName('notifications');

$router
    ->post('/notifications/read', static function (Request $request) use ($notificationsController) {
        return $notificationsController->markRead($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo));

$router
    ->get('/settings', static function (Request $request) use ($settingsController) {
        return $settingsController->index($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo))
    ->setName('settings');

$router
    ->get('/settings/reports', static function (Request $request) use ($reportPreferencesController) {
        return $reportPreferencesController->index($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'))
    ->setName('settings.reports');

$router
    ->post('/settings/reports', static function (Request $request) use ($reportPreferencesController) {
        return $reportPreferencesController->update($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'));

$router
    ->post('/profile/update', static function (Request $request) use ($settingsController) {
        return $settingsController->updateProfile($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo));

$router
    ->get('/profile/image', static function (Request $request) use ($uploadController) {
        return $uploadController->profileImage($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo));


$router
    ->post('/settings/preferences', static function (Request $request) use ($settingsController) {
        return $settingsController->updatePreferences($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo));

$router
    ->get('/settings/credit-limits', static function (Request $request) use ($creditLimitsController) {
        return $creditLimitsController->index($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo))
    ->middleware(new RequireWorkspacePermission($pdo, 'billing.manage'));

$router
    ->post('/settings/credit-limits', static function (Request $request) use ($creditLimitsController) {
        return $creditLimitsController->save($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo))
    ->middleware(new RequireWorkspacePermission($pdo, 'billing.manage'));

$router
    ->get('/settings/api-tokens', static function (Request $request) use ($apiTokensController) {
        return $apiTokensController->index($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'));

$router
    ->post('/settings/api-tokens', static function (Request $request) use ($apiTokensController) {
        return $apiTokensController->create($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'));

$router
    ->post('/settings/api-tokens/revoke', static function (Request $request) use ($apiTokensController) {
        return $apiTokensController->revoke($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'));

$router
    ->get('/teams', static function (Request $request) use ($workspaceController) {
        return $workspaceController->index($request);
    })
    ->middleware(new AuthRequired())
    ->setName('teams');

$router
    ->post('/teams', static function (Request $request) use ($workspaceController) {
        return $workspaceController->create($request);
    })
    ->middleware(new AuthRequired());

$router
    ->post('/teams/switch', static function (Request $request) use ($workspaceController) {
        return $workspaceController->switch($request);
    })
    ->middleware(new AuthRequired());

$router
    ->post('/teams/update', static function (Request $request) use ($workspaceController) {
        return $workspaceController->updateName($request);
    })
    ->middleware(new AuthRequired());

$router
    ->post('/teams/members/role', static function (Request $request) use ($workspaceController) {
        return $workspaceController->updateMemberRole($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'));

$router
    ->post('/teams/invites', static function (Request $request) use ($workspaceInviteController) {
        return $workspaceInviteController->create($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'));

$router
    ->get('/teams/invites/accept', static function (Request $request) use ($workspaceInviteController) {
        return $workspaceInviteController->showAccept($request);
    })
    ->setName('teams.invites.accept');

$router
    ->post('/teams/invites/accept', static function (Request $request) use ($workspaceInviteController) {
        return $workspaceInviteController->accept($request);
    })
    ->middleware(new AuthRequired());

$router
    ->post('/teams/invites/resend', static function (Request $request) use ($workspaceInviteController) {
        return $workspaceInviteController->resend($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'));

$router->get('/privacy', static function () {
    return View::render('legal/privacy', ['title' => 'Privacy Policy']);
});

$router->get('/terms', static function () {
    return View::render('legal/terms', ['title' => 'Terms of Service']);
});

$router->get('/support', static function () {
    return View::render('support', ['title' => 'Support']);
});

$router
    ->get('/profile', static function (Request $request) use ($uploadController) {
        return $uploadController->show($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo))
    ->setName('profile');

$router
    ->post('/profile/password', static function (Request $request) use ($uploadController) {
        return $uploadController->updatePassword($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo));

$router
    ->post('/profile/deactivate', static function (Request $request) use ($uploadController) {
        return $uploadController->deactivateAccount($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo));

$router
    ->get('/uploads', static function () {
        return Response::redirect('/profile');
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo))
    ->setName('uploads');

$router
    ->post('/uploads/profile', static function (Request $request) use ($uploadController) {
        return $uploadController->uploadProfile($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo));

$router
    ->post('/uploads/attachment', static function (Request $request) use ($uploadController) {
        return $uploadController->uploadAttachment($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo));

$router
    ->get('/uploads/attachment/{id}', static function (Request $request) use ($uploadController) {
        return $uploadController->download($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo))
    ->setName('uploads.download');

$router
    ->get('/workspace-admin/users', static function (Request $request) use ($usersController) {
        return $usersController->index($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'))
    ->setName('workspace_admin.users');

$router
    ->post('/super-admin/workspace-permissions', static function (Request $request) use ($workspacePermissionsController) {
        return $workspacePermissionsController->create($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'))
    ->middleware(new RequireSystemAccess());

$router
    ->post('/super-admin/invoice-setup', static function (Request $request) use ($pdo) {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }
        if ((int)(Auth::user()['is_system_admin'] ?? 0) !== 1) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $settings = new AppSettingsService($pdo);
        $fields = [
            'invoice_company_name' => 'invoice.company_name',
            'invoice_company_address' => 'invoice.company_address',
            'invoice_company_city' => 'invoice.company_city',
            'invoice_company_state' => 'invoice.company_state',
            'invoice_company_postal_code' => 'invoice.company_postal_code',
            'invoice_company_country' => 'invoice.company_country',
            'invoice_tax_id' => 'invoice.tax_id',
            'invoice_support_email' => 'invoice.support_email',
        ];

        foreach ($fields as $inputKey => $settingKey) {
            $settings->set($settingKey, trim((string)$request->input($inputKey, '')));
        }

        $_SESSION['flash']['message'] = 'Invoice setup saved.';
        return Response::redirect('/super-admin/settings');
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'))
    ->middleware(new RequireSystemAccess());

$router
    ->post('/super-admin/feature-flags', static function (Request $request) use ($pdo) {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }
        if ((int)(Auth::user()['is_system_admin'] ?? 0) !== 1) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $settings = new AppSettingsService($pdo);
        $profileImages = $request->input('profile_images_enabled') === '1';
        $settings->set('profile.images.enabled', $profileImages ? '1' : '0');

        $_SESSION['flash']['message'] = 'Feature flags saved.';
        return Response::redirect('/super-admin/settings');
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'))
    ->middleware(new RequireSystemAccess());

$router
    ->post('/super-admin/workspace-roles/permissions', static function (Request $request) use ($workspacePermissionsController) {
        return $workspacePermissionsController->updateRolePermissions($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'))
    ->middleware(new RequireSystemAccess());

$router
    ->post('/super-admin/payment-gateways/{provider}', static function (Request $request) use ($paymentGatewaysController) {
        $provider = (string)($request->param('provider') ?? '');
        return $paymentGatewaysController->save($request, $provider);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'))
    ->middleware(new RequireSystemAdmin());

$router
    ->post('/super-admin/payment-gateways/rules', static function (Request $request) use ($paymentGatewaysController) {
        return $paymentGatewaysController->saveRules($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'))
    ->middleware(new RequireSystemAdmin());

$router
    ->get('/super-admin/user-roles', static function () {
        return Response::redirect('/super-admin/settings?tab=user-roles');
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'))
    ->middleware(new RequireSystemAccess())
    ->setName('super_admin.user_roles');

$router
    ->post('/super-admin/user-roles', static function (Request $request) use ($userRolesController) {
        return $userRolesController->assign($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'))
    ->middleware(new RequireSystemAdmin());

$router
    ->post('/super-admin/users/status', static function (Request $request) use ($userRolesController) {
        return $userRolesController->updateStatus($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'))
    ->middleware(new RequireSystemAdmin());

$router
    ->get('/workspace-admin/audit', static function (Request $request) use ($auditController) {
        return $auditController->index($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'))
    ->setName('workspace_admin.audit');

$router
    ->get('/workspace-admin/analytics/credits', static function (Request $request) use ($workspaceAnalyticsController) {
        return $workspaceAnalyticsController->credits($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'))
    ->setName('workspace_admin.analytics_credits');

$router
    ->post('/workspace-admin/analytics/credits/alerts', static function (Request $request) use ($workspaceAnalyticsController) {
        return $workspaceAnalyticsController->updateVelocityAlerts($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'));

$router
    ->get('/workspace-admin/analytics/credits/export', static function (Request $request) use ($workspaceAnalyticsController) {
        return $workspaceAnalyticsController->exportCreditsUsage($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspaceRole($pdo, 'Workspace Admin'))
    ->setName('workspace_admin.analytics_credits_export');

return $router;
