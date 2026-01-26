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
$notificationService = new NotificationService($pdo, $config);
$notificationsController = new NotificationsController($notificationService);
$analyticsController = new AnalyticsController();

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
    ->middleware(new RequireWorkspace($pdo))
    ->setName('dashboard');

$router
    ->get('/super-admin', static function () {
        return Response::redirect('/super-admin/analytics');
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo, 'Workspace Admin'))
    ->middleware(new RequireRole('Super Admin'))
    ->setName('super_admin');

$router
    ->get('/super-admin/analytics', static function (Request $request) use ($analyticsController) {
        return $analyticsController->index($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo, 'Workspace Admin'))
    ->middleware(new RequireRole('Super Admin'))
    ->setName('super_admin.analytics');

$router
    ->get('/super-admin/usage', static function () {
        return View::render('admin/usage/index', ['title' => 'Global Usage']);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo, 'Workspace Admin'))
    ->middleware(new RequireRole('Super Admin'))
    ->setName('super_admin.usage');

$router
    ->get('/super-admin/settings', static function (Request $request) use ($pdo) {
        $rbac = new Rbac($pdo);
        $roles = $rbac->roles();
        $permissions = $rbac->permissions();
        $permissionsByRole = $rbac->permissionsByRole();
        $superAdminCount = 0;
        $countSuper = $pdo->prepare(
            'SELECT COUNT(DISTINCT u.id)
             FROM users u
             INNER JOIN user_roles ur ON ur.user_id = u.id
             INNER JOIN roles r ON r.id = ur.role_id
             WHERE r.name = ?'
        );
        $countSuper->execute(['Super Admin']);
        $superAdminCount = (int)$countSuper->fetchColumn();

        $search = trim((string)$request->query('search', ''));
        $selectedRole = (string)$request->query('role_id', 'all');
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
        if ($selectedRole !== '' && $selectedRole !== 'all') {
            if ($selectedRole === 'unassigned') {
                $conditions[] = 'ur.role_id IS NULL';
            } else {
                $conditions[] = 'r.id = ?';
                $params[] = (int)$selectedRole;
            }
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $countSql = 'SELECT COUNT(*) FROM users u LEFT JOIN user_roles ur ON ur.user_id = u.id LEFT JOIN roles r ON r.id = ur.role_id ' . $where;
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();
        $totalPages = max(1, (int)ceil($total / $limit));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $limit;

        $sql = 'SELECT u.id, u.name, u.email, u.status, r.name AS role_name
            FROM users u
            LEFT JOIN user_roles ur ON ur.user_id = u.id
            LEFT JOIN roles r ON r.id = ur.role_id
            ' . $where . '
            ORDER BY u.name ASC
            LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return View::render('admin/site_settings/index', [
            'title' => 'Site Settings',
            'roles' => $roles,
            'permissions' => $permissions,
            'permissionsByRole' => $permissionsByRole,
            'users' => $users,
            'search' => $search,
            'selectedRole' => $selectedRole,
            'page' => $page,
            'total' => $total,
            'totalPages' => $totalPages,
            'limit' => $limit,
            'superAdminCount' => $superAdminCount,
        ]);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo, 'Workspace Admin'))
    ->middleware(new RequireRole('Super Admin'))
    ->setName('super_admin.settings');

$router
    ->get('/notifications', static function (Request $request) use ($notificationsController) {
        return $notificationsController->index($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo))
    ->setName('notifications');

$router
    ->post('/notifications/read', static function (Request $request) use ($notificationsController) {
        return $notificationsController->markRead($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo));

$router
    ->get('/settings', static function (Request $request) use ($settingsController) {
        return $settingsController->index($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo))
    ->setName('settings');

$router
    ->post('/profile/update', static function (Request $request) use ($settingsController) {
        return $settingsController->updateProfile($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo));


$router
    ->post('/settings/preferences', static function (Request $request) use ($settingsController) {
        return $settingsController->updatePreferences($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo));

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
    ->middleware(new RequireWorkspace($pdo, 'Workspace Admin'));

$router
    ->post('/teams/invites', static function (Request $request) use ($workspaceInviteController) {
        return $workspaceInviteController->create($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo, 'Workspace Admin'));

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
    ->middleware(new RequireWorkspace($pdo, 'Workspace Admin'));

$router->get('/privacy', static function () {
    return View::render('legal/privacy', ['title' => 'Privacy Policy']);
});

$router->get('/terms', static function () {
    return View::render('legal/terms', ['title' => 'Terms of Service']);
});

$router->get('/support', static function () {
    return View::render('support', ['title' => 'Support']);
});

$rolesController = new RolesController($pdo);
$permissionsController = new PermissionsController($pdo);
$userRolesController = new UserRolesController($pdo);
$usersController = new UsersController($pdo);
$auditController = new AuditLogController($pdo);
$uploadController = new UploadController($pdo, __DIR__ . '/../storage');

$router
    ->get('/profile', static function (Request $request) use ($uploadController) {
        return $uploadController->show($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo))
    ->setName('profile');

$router
    ->post('/profile/password', static function (Request $request) use ($uploadController) {
        return $uploadController->updatePassword($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo));

$router
    ->post('/profile/deactivate', static function (Request $request) use ($uploadController) {
        return $uploadController->deactivateAccount($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo));

$router
    ->get('/uploads', static function () {
        return Response::redirect('/profile');
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo))
    ->setName('uploads');

$router
    ->post('/uploads/profile', static function (Request $request) use ($uploadController) {
        return $uploadController->uploadProfile($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo));

$router
    ->post('/uploads/attachment', static function (Request $request) use ($uploadController) {
        return $uploadController->uploadAttachment($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo));

$router
    ->get('/uploads/attachment/{id}', static function (Request $request) use ($uploadController) {
        return $uploadController->download($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo))
    ->setName('uploads.download');

$router
    ->get('/workspace-admin/users', static function (Request $request) use ($usersController) {
        return $usersController->index($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo, 'Workspace Admin'))
    ->middleware(new RequirePermission('users.manage'))
    ->setName('workspace_admin.users');

$router
    ->post('/super-admin/roles', static function (Request $request) use ($rolesController) {
        return $rolesController->create($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo, 'Workspace Admin'))
    ->middleware(new RequireRole('Super Admin'));

$router
    ->post('/super-admin/roles/permissions', static function (Request $request) use ($rolesController) {
        return $rolesController->updatePermissions($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo, 'Workspace Admin'))
    ->middleware(new RequireRole('Super Admin'));

$router
    ->post('/super-admin/permissions', static function (Request $request) use ($permissionsController) {
        return $permissionsController->create($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo, 'Workspace Admin'))
    ->middleware(new RequireRole('Super Admin'));

$router
    ->get('/super-admin/user-roles', static function () {
        return Response::redirect('/super-admin/settings?tab=user-roles');
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo, 'Workspace Admin'))
    ->middleware(new RequireRole('Super Admin'))
    ->setName('super_admin.user_roles');

$router
    ->post('/super-admin/user-roles', static function (Request $request) use ($userRolesController) {
        return $userRolesController->assign($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo, 'Workspace Admin'))
    ->middleware(new RequireRole('Super Admin'));

$router
    ->post('/super-admin/users/status', static function (Request $request) use ($userRolesController) {
        return $userRolesController->updateStatus($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo, 'Workspace Admin'))
    ->middleware(new RequireRole('Super Admin'));

$router
    ->get('/workspace-admin/audit', static function (Request $request) use ($auditController) {
        return $auditController->index($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo, 'Workspace Admin'))
    ->middleware(new RequirePermission('audit.view'))
    ->setName('workspace_admin.audit');

return $router;
