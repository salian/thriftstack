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
    ->get('/workspaces', static function (Request $request) use ($workspaceController) {
        return $workspaceController->index($request);
    })
    ->middleware(new AuthRequired())
    ->setName('workspaces');

$router
    ->post('/workspaces', static function (Request $request) use ($workspaceController) {
        return $workspaceController->create($request);
    })
    ->middleware(new AuthRequired());

$router
    ->post('/workspaces/switch', static function (Request $request) use ($workspaceController) {
        return $workspaceController->switch($request);
    })
    ->middleware(new AuthRequired());

$router
    ->post('/workspaces/update', static function (Request $request) use ($workspaceController) {
        return $workspaceController->updateName($request);
    })
    ->middleware(new AuthRequired());

$router
    ->post('/workspaces/members/role', static function (Request $request) use ($workspaceController) {
        return $workspaceController->updateMemberRole($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo, 'Admin'));

$router
    ->post('/workspaces/invites', static function (Request $request) use ($workspaceInviteController) {
        return $workspaceInviteController->create($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo, 'Admin'));

$router
    ->get('/workspaces/invites/accept', static function (Request $request) use ($workspaceInviteController) {
        return $workspaceInviteController->showAccept($request);
    })
    ->setName('workspaces.invites.accept');

$router
    ->post('/workspaces/invites/accept', static function (Request $request) use ($workspaceInviteController) {
        return $workspaceInviteController->accept($request);
    })
    ->middleware(new AuthRequired());

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
    ->get('/admin/users', static function (Request $request) use ($usersController) {
        return $usersController->index($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo, 'Admin'))
    ->middleware(new RequirePermission('users.manage'))
    ->setName('admin.users');

$router
    ->get('/admin/roles', static function (Request $request) use ($rolesController) {
        return $rolesController->index($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo, 'Admin'))
    ->middleware(new RequireRole('Admin'))
    ->setName('admin.roles');

$router
    ->post('/admin/roles', static function (Request $request) use ($rolesController) {
        return $rolesController->create($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo, 'Admin'))
    ->middleware(new RequireRole('Admin'));

$router
    ->post('/admin/roles/permissions', static function (Request $request) use ($rolesController) {
        return $rolesController->updatePermissions($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo, 'Admin'))
    ->middleware(new RequireRole('Admin'));

$router
    ->get('/admin/permissions', static function (Request $request) use ($permissionsController) {
        return $permissionsController->index($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo, 'Admin'))
    ->middleware(new RequireRole('Admin'))
    ->setName('admin.permissions');

$router
    ->post('/admin/permissions', static function (Request $request) use ($permissionsController) {
        return $permissionsController->create($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo, 'Admin'))
    ->middleware(new RequireRole('Admin'));

$router
    ->get('/admin/user-roles', static function (Request $request) use ($userRolesController) {
        return $userRolesController->index($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo, 'Admin'))
    ->middleware(new RequireRole('Admin'))
    ->setName('admin.user_roles');

$router
    ->post('/admin/user-roles', static function (Request $request) use ($userRolesController) {
        return $userRolesController->assign($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo, 'Admin'))
    ->middleware(new RequireRole('Admin'));

$router
    ->get('/admin/audit', static function (Request $request) use ($auditController) {
        return $auditController->index($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireWorkspace($pdo, 'Admin'))
    ->middleware(new RequirePermission('audit.view'))
    ->setName('admin.audit');

return $router;
