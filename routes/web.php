<?php

declare(strict_types=1);

$router = new Router();
$config = $GLOBALS['config'] ?? [];
$pdo = DB::connect($config);
$authController = new AuthController($pdo, $config);

$router
    ->get('/', static function (Request $request) {
        return View::render('home', ['title' => 'Thriftstack']);
    })
    ->name('home');

$router
    ->get('/login', static function (Request $request) use ($authController) {
        return $authController->showLogin($request);
    })
    ->name('login');

$router
    ->post('/login', static function (Request $request) use ($authController) {
        return $authController->login($request);
    });

$router
    ->post('/logout', static function (Request $request) use ($authController) {
        return $authController->logout($request);
    })
    ->middleware(new AuthRequired())
    ->name('logout');

$router
    ->get('/signup', static function (Request $request) use ($authController) {
        return $authController->showSignup($request);
    })
    ->name('signup');

$router
    ->post('/signup', static function (Request $request) use ($authController) {
        return $authController->signup($request);
    });

$router
    ->get('/verify', static function (Request $request) use ($authController) {
        return $authController->verify($request);
    })
    ->name('verify');

$router
    ->get('/forgot', static function (Request $request) use ($authController) {
        return $authController->showForgot($request);
    })
    ->name('forgot');

$router
    ->post('/forgot', static function (Request $request) use ($authController) {
        return $authController->sendReset($request);
    });

$router
    ->get('/reset', static function (Request $request) use ($authController) {
        return $authController->showReset($request);
    })
    ->name('reset');

$router
    ->post('/reset', static function (Request $request) use ($authController) {
        return $authController->reset($request);
    });

$router
    ->get('/dashboard', static function (Request $request) {
        return View::render('dashboard', ['title' => 'Dashboard']);
    })
    ->middleware(new AuthRequired())
    ->name('dashboard');

$rolesController = new RolesController($pdo);
$permissionsController = new PermissionsController($pdo);
$userRolesController = new UserRolesController($pdo);
$usersController = new UsersController($pdo);
$auditController = new AuditLogController($pdo);
$uploadController = new UploadController($pdo, __DIR__ . '/../storage');

$router
    ->get('/uploads', static function (Request $request) use ($uploadController) {
        return $uploadController->show($request);
    })
    ->middleware(new AuthRequired())
    ->name('uploads');

$router
    ->post('/uploads/profile', static function (Request $request) use ($uploadController) {
        return $uploadController->uploadProfile($request);
    })
    ->middleware(new AuthRequired());

$router
    ->post('/uploads/attachment', static function (Request $request) use ($uploadController) {
        return $uploadController->uploadAttachment($request);
    })
    ->middleware(new AuthRequired());

$router
    ->get('/uploads/attachment/{id}', static function (Request $request) use ($uploadController) {
        return $uploadController->download($request);
    })
    ->middleware(new AuthRequired())
    ->name('uploads.download');

$router
    ->get('/admin/users', static function (Request $request) use ($usersController) {
        return $usersController->index($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequirePermission('users.manage'))
    ->name('admin.users');

$router
    ->get('/admin/roles', static function (Request $request) use ($rolesController) {
        return $rolesController->index($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireRole('Admin'))
    ->name('admin.roles');

$router
    ->post('/admin/roles', static function (Request $request) use ($rolesController) {
        return $rolesController->create($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireRole('Admin'));

$router
    ->post('/admin/roles/permissions', static function (Request $request) use ($rolesController) {
        return $rolesController->updatePermissions($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireRole('Admin'));

$router
    ->get('/admin/permissions', static function (Request $request) use ($permissionsController) {
        return $permissionsController->index($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireRole('Admin'))
    ->name('admin.permissions');

$router
    ->post('/admin/permissions', static function (Request $request) use ($permissionsController) {
        return $permissionsController->create($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireRole('Admin'));

$router
    ->get('/admin/user-roles', static function (Request $request) use ($userRolesController) {
        return $userRolesController->index($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireRole('Admin'))
    ->name('admin.user_roles');

$router
    ->post('/admin/user-roles', static function (Request $request) use ($userRolesController) {
        return $userRolesController->assign($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequireRole('Admin'));

$router
    ->get('/admin/audit', static function (Request $request) use ($auditController) {
        return $auditController->index($request);
    })
    ->middleware(new AuthRequired())
    ->middleware(new RequirePermission('audit.view'))
    ->name('admin.audit');

return $router;
