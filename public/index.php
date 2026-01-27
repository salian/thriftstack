<?php

declare(strict_types=1);

require __DIR__ . '/../app/Bootstrap.php';
if (is_file(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
}
require __DIR__ . '/../app/Auth/Auth.php';
require __DIR__ . '/../app/Auth/Password.php';
require __DIR__ . '/../app/Auth/Csrf.php';
require __DIR__ . '/../app/Auth/Rbac.php';
require __DIR__ . '/../app/Audit/Audit.php';
require __DIR__ . '/../app/Billing/BillingService.php';
require __DIR__ . '/../app/Billing/BillingGatewaySelector.php';
require __DIR__ . '/../app/Billing/PaymentGatewaySettingsService.php';
require __DIR__ . '/../app/Billing/Providers/BillingProvider.php';
require __DIR__ . '/../app/Billing/Providers/HmacProvider.php';
require __DIR__ . '/../app/Billing/Providers/RazorpayProvider.php';
require __DIR__ . '/../app/Billing/Providers/StripeProvider.php';
require __DIR__ . '/../app/Billing/Providers/PayPalProvider.php';
require __DIR__ . '/../app/Billing/Providers/LemonSqueezyProvider.php';
require __DIR__ . '/../app/Billing/Providers/DodoProvider.php';
require __DIR__ . '/../app/Billing/Providers/PaddleProvider.php';
require __DIR__ . '/../app/Workspaces/WorkspaceService.php';
require __DIR__ . '/../app/Uploads/Uploader.php';
require __DIR__ . '/../app/Settings/SettingsService.php';
require __DIR__ . '/../app/Settings/AppSettingsService.php';
require __DIR__ . '/../app/Notifications/NotificationDispatcher.php';
require __DIR__ . '/../app/Notifications/NotificationService.php';
require __DIR__ . '/../app/Database/DB.php';
require __DIR__ . '/../app/Logging/Logger.php';
require __DIR__ . '/../app/Logging/LogRotation.php';
require __DIR__ . '/../app/Errors/ErrorHandler.php';
require __DIR__ . '/../app/Security/Headers.php';
require __DIR__ . '/../app/Http/Request.php';
require __DIR__ . '/../app/Http/Response.php';
require __DIR__ . '/../app/Http/Router.php';
require __DIR__ . '/../app/Http/Middleware/AuthRequired.php';
require __DIR__ . '/../app/Http/Middleware/RequireSystemAdmin.php';
require __DIR__ . '/../app/Http/Middleware/RequireSystemAccess.php';
require __DIR__ . '/../app/Http/Middleware/RequireWorkspaceRole.php';
require __DIR__ . '/../app/Http/Middleware/RequireWorkspacePermission.php';
require __DIR__ . '/../app/Mail/Mailer.php';
require __DIR__ . '/../app/Controllers/AuthController.php';
require __DIR__ . '/../app/Controllers/WorkspaceController.php';
require __DIR__ . '/../app/Controllers/WorkspaceInviteController.php';
require __DIR__ . '/../app/Controllers/SettingsController.php';
require __DIR__ . '/../app/Controllers/NotificationsController.php';
require __DIR__ . '/../app/Controllers/BillingController.php';
require __DIR__ . '/../app/Controllers/WebhooksController.php';
require __DIR__ . '/../app/Controllers/Admin/WorkspacePermissionsController.php';
require __DIR__ . '/../app/Controllers/Admin/PaymentGatewaysController.php';
require __DIR__ . '/../app/Controllers/Admin/UserRolesController.php';
require __DIR__ . '/../app/Controllers/Admin/UsersController.php';
require __DIR__ . '/../app/Controllers/Admin/AuditLogController.php';
require __DIR__ . '/../app/Controllers/Admin/AnalyticsController.php';
require __DIR__ . '/../app/Controllers/UploadController.php';
require __DIR__ . '/../app/View/View.php';

$config = Bootstrap::init();

$router = require __DIR__ . '/../routes/web.php';

$request = Request::capture();
$response = $router->dispatch($request);
$response->send();
