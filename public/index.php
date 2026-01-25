<?php

declare(strict_types=1);

require __DIR__ . '/../app/Bootstrap.php';
require __DIR__ . '/../app/Auth/Auth.php';
require __DIR__ . '/../app/Auth/Password.php';
require __DIR__ . '/../app/Auth/Csrf.php';
require __DIR__ . '/../app/Auth/Rbac.php';
require __DIR__ . '/../app/Audit/Audit.php';
require __DIR__ . '/../app/Workspaces/WorkspaceService.php';
require __DIR__ . '/../app/Uploads/Uploader.php';
require __DIR__ . '/../app/Settings/SettingsService.php';
require __DIR__ . '/../app/Database/DB.php';
require __DIR__ . '/../app/Logging/Logger.php';
require __DIR__ . '/../app/Logging/LogRotation.php';
require __DIR__ . '/../app/Errors/ErrorHandler.php';
require __DIR__ . '/../app/Security/Headers.php';
require __DIR__ . '/../app/Http/Request.php';
require __DIR__ . '/../app/Http/Response.php';
require __DIR__ . '/../app/Http/Router.php';
require __DIR__ . '/../app/Http/Middleware/AuthRequired.php';
require __DIR__ . '/../app/Http/Middleware/RoleRequired.php';
require __DIR__ . '/../app/Http/Middleware/PermissionRequired.php';
require __DIR__ . '/../app/Http/Middleware/RequireRole.php';
require __DIR__ . '/../app/Http/Middleware/RequirePermission.php';
require __DIR__ . '/../app/Http/Middleware/RequireWorkspace.php';
require __DIR__ . '/../app/Mail/Mailer.php';
require __DIR__ . '/../app/Controllers/AuthController.php';
require __DIR__ . '/../app/Controllers/WorkspaceController.php';
require __DIR__ . '/../app/Controllers/WorkspaceInviteController.php';
require __DIR__ . '/../app/Controllers/SettingsController.php';
require __DIR__ . '/../app/Controllers/Admin/RolesController.php';
require __DIR__ . '/../app/Controllers/Admin/PermissionsController.php';
require __DIR__ . '/../app/Controllers/Admin/UserRolesController.php';
require __DIR__ . '/../app/Controllers/Admin/UsersController.php';
require __DIR__ . '/../app/Controllers/Admin/AuditLogController.php';
require __DIR__ . '/../app/Controllers/UploadController.php';
require __DIR__ . '/../app/View/View.php';

$config = Bootstrap::init();

$router = require __DIR__ . '/../routes/web.php';

$request = Request::capture();
$response = $router->dispatch($request);
$response->send();
