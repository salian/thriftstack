<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? (string)config('app.name', 'ThriftStack')) ?></title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/site.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script defer src="/assets/js/theme.js"></script>
    <script defer src="/assets/js/ui.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body>
    <div class="page <?= Auth::check() ? 'has-sidebar' : '' ?>">
        <?php if (Auth::check()) : ?>
            <aside class="sidebar" aria-label="Primary sidebar">
                <a class="brand sidebar-brand" href="/dashboard" aria-label="<?= e((string)config('app.name', 'ThriftStack')) ?>">
                    <svg class="brand-mark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M7.657 6.247c.11-.33.576-.33.686 0l.645 1.937a2.89 2.89 0 0 0 1.829 1.828l1.936.645c.33.11.33.576 0 .686l-1.937.645a2.89 2.89 0 0 0-1.828 1.829l-.645 1.936a.361.361 0 0 1-.686 0l-.645-1.937a2.89 2.89 0 0 0-1.828-1.828l-1.937-.645a.361.361 0 0 1 0-.686l1.937-.645a2.89 2.89 0 0 0 1.828-1.828zM3.794 1.148a.217.217 0 0 1 .412 0l.387 1.162c.173.518.579.924 1.097 1.097l1.162.387a.217.217 0 0 1 0 .412l-1.162.387A1.73 1.73 0 0 0 4.593 5.69l-.387 1.162a.217.217 0 0 1-.412 0L3.407 5.69A1.73 1.73 0 0 0 2.31 4.593l-1.162-.387a.217.217 0 0 1 0-.412l1.162-.387A1.73 1.73 0 0 0 3.407 2.31zM10.863.099a.145.145 0 0 1 .274 0l.258.774c.115.346.386.617.732.732l.774.258a.145.145 0 0 1 0 .274l-.774.258a1.16 1.16 0 0 0-.732.732l-.258.774a.145.145 0 0 1-.274 0l-.258-.774a1.16 1.16 0 0 0-.732-.732L9.1 2.137a.145.145 0 0 1 0-.274l.774-.258c.346-.115.617-.386.732-.732z"/>
                    </svg>
                    <span class="brand-text"><?= e((string)config('app.name', 'ThriftStack')) ?></span>
                </a>
                <?php
                $workspaceService = new WorkspaceService(DB::connect($GLOBALS['config'] ?? []));
                $workspaceList = $workspaceService->listForUser((int)(Auth::user()['id'] ?? 0));
                $currentWorkspaceId = $workspaceService->currentWorkspaceId();
                $canAccessBilling = false;
                $canAccessWorkspaceAdmin = false;
                $hasWorkspaceBillingPermission = false;
                $workspacePermissionCache = [];

                foreach ($workspaceList as $workspace) {
                    $role = (string)($workspace['role'] ?? '');
                    if ($role === '') {
                        continue;
                    }
                    if ($workspaceService->isRoleAtLeast($role, 'Workspace Admin')) {
                        $canAccessWorkspaceAdmin = true;
                    }
                    if (!array_key_exists($role, $workspacePermissionCache)) {
                        $workspacePermissionCache[$role] = $workspaceService->workspacePermissionsForRole($role);
                    }
                    if (in_array('billing.manage', $workspacePermissionCache[$role], true)) {
                        $hasWorkspaceBillingPermission = true;
                        break;
                    }
                }

                $canAccessBilling = $hasWorkspaceBillingPermission;
                $hasSystemAccess = (int)(Auth::user()['is_system_admin'] ?? 0) === 1
                    || (int)(Auth::user()['is_system_staff'] ?? 0) === 1;
                ?>
                <div class="sidebar-workspace">
                    <form method="post" action="/teams/switch" class="form-inline">
                        <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                        <input type="hidden" name="return_to" value="<?= e($_SERVER['REQUEST_URI'] ?? '/teams') ?>">
                        <select name="workspace_id" aria-label="Switch workspace" data-auto-submit data-create-url="/teams">
                            <?php foreach ($workspaceList as $workspace) : ?>
                                <option value="<?= e((string)$workspace['id']) ?>" <?= ($currentWorkspaceId == $workspace['id']) ? 'selected' : '' ?>>
                                    <?= e($workspace['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="__create__">+ Create workspace</option>
                        </select>
                    </form>
                </div>
                <a class="sidebar-workspace-toggle" href="/teams?tab=workspaces" aria-label="Switch workspace" data-tooltip="Switch Workspace">
                    <i class="fa-solid fa-building-user sidebar-icon" aria-hidden="true"></i>
                </a>
                <div class="sidebar-scroll">
                    <nav class="sidebar-nav" aria-label="Sidebar">
                        <a href="/dashboard" aria-label="Dashboard" data-tooltip="Dashboard">
                            <i class="fa-solid fa-gauge sidebar-icon" aria-hidden="true"></i>
                            <span class="sidebar-label">Dashboard</span>
                        </a>
                        <a href="/tasks" aria-label="Tasks" data-tooltip="Tasks">
                            <i class="fa-solid fa-list-check sidebar-icon" aria-hidden="true"></i>
                            <span class="sidebar-label">Tasks</span>
                        </a>
                        <a href="/reports" aria-label="Reports" data-tooltip="Reports">
                            <i class="fa-solid fa-chart-column sidebar-icon" aria-hidden="true"></i>
                            <span class="sidebar-label">Reports</span>
                        </a>
                        <?php if ($canAccessWorkspaceAdmin || $hasSystemAccess) : ?>
                            <a href="/workspace-admin/users" aria-label="Admin" data-tooltip="Admin">
                                <i class="fa-solid fa-shield-halved sidebar-icon" aria-hidden="true"></i>
                                <span class="sidebar-label">Admin</span>
                            </a>
                        <?php endif; ?>
                        <div class="sidebar-divider"></div>
                        <a href="/settings" aria-label="Settings" data-tooltip="Settings">
                            <i class="fa-solid fa-gear sidebar-icon" aria-hidden="true"></i>
                            <span class="sidebar-label">Settings</span>
                        </a>
                        <a href="/support" aria-label="Support" data-tooltip="Support">
                            <i class="fa-solid fa-life-ring sidebar-icon" aria-hidden="true"></i>
                            <span class="sidebar-label">Support</span>
                        </a>
                    <button type="button" class="theme-toggle" data-theme-toggle data-tooltip="Theme" aria-label="Toggle theme">
                        <span class="toggle-switch" aria-hidden="true">
                            <i class="fa-solid fa-moon toggle-icon toggle-icon-left" aria-hidden="true"></i>
                            <span class="toggle-knob"></span>
                            <i class="fa-solid fa-sun toggle-icon toggle-icon-right" aria-hidden="true"></i>
                        </span>
                    </button>
                    </nav>
                </div>
            </aside>
        <?php endif; ?>
        <div class="content-area">
            <header class="site-header">
                <div class="content-inner">
                    <?php if (Auth::check()) : ?>
                        <div class="header-controls">
                            <button type="button" class="icon-button" data-sidebar-toggle aria-pressed="false"
                                aria-label="Toggle sidebar">
                                <i class="fa-solid fa-angles-left icon-collapse" aria-hidden="true"></i>
                                <i class="fa-solid fa-angles-right icon-expand" aria-hidden="true"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                    <nav class="nav" aria-label="Primary">
                        <?php if (Auth::check()) : ?>
                            <a href="/notifications" class="nav-icon" aria-label="Notifications">
                                <i class="fa-solid fa-bell" aria-hidden="true"></i>
                                <span class="nav-icon-dot" aria-hidden="true"></span>
                            </a>
                            <?php
                            $userName = Auth::user()['name'] ?? 'User';
                            $parts = preg_split('/\s+/', trim($userName));
                            $initials = '';
                            foreach ($parts as $part) {
                                if ($part === '') {
                                    continue;
                                }
                                $initials .= strtoupper($part[0]);
                                if (strlen($initials) >= 2) {
                                    break;
                                }
                            }
                            if ($initials === '') {
                                $initials = 'U';
                            }
                            $settings = new AppSettingsService(DB::connect($GLOBALS['config'] ?? []));
                            $profileImagesEnabled = $settings->get('profile.images.enabled', '0') === '1';
                            $profileImageAvailable = false;
                            if ($profileImagesEnabled) {
                                $stmt = DB::connect($GLOBALS['config'] ?? [])->prepare(
                                    'SELECT id FROM uploads WHERE user_id = ? AND type = "profile" ORDER BY created_at DESC LIMIT 1'
                                );
                                $stmt->execute([(int)(Auth::user()['id'] ?? 0)]);
                                $profileImageAvailable = (bool)$stmt->fetchColumn();
                            }
                            ?>
                            <details class="nav-user-menu">
                                <summary>
                                    <span class="avatar">
                                        <?php if ($profileImagesEnabled && $profileImageAvailable) : ?>
                                            <img src="/profile/image" alt="Profile image">
                                        <?php else : ?>
                                            <?= e($initials) ?>
                                        <?php endif; ?>
                                    </span>
                                    <span class="nav-user-name"><?= e($userName) ?></span>
                                    <span class="nav-chevron"></span>
                                </summary>
                                <div class="nav-menu-panel">
                                    <a href="/teams">Teams</a>
                                    <a href="/settings">Settings</a>
                                    <a href="/notifications">Notifications</a>
                                    <?php if ($canAccessBilling) : ?>
                                        <a href="/billing">Billing</a>
                                    <?php endif; ?>
                                    <a href="/profile">Profile</a>
                                    <?php if ($canAccessWorkspaceAdmin || $hasSystemAccess) : ?>
                                        <div class="nav-menu-divider"></div>
                                        <a href="/workspace-admin/users">Workspace Admin</a>
                                        <?php if ($hasSystemAccess) : ?>
                                            <a href="/super-admin/analytics">System Admin</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <div class="nav-menu-divider"></div>
                                    <form method="post" action="/logout" class="nav-menu-form">
                                        <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                                        <button type="submit">Logout</button>
                                    </form>
                                </div>
                            </details>
                        <?php else : ?>
                            <a href="/login">Login</a>
                            <a href="/signup">Sign up</a>
                        <?php endif; ?>
                    </nav>
                </div>
            </header>
            <main class="content-inner main-content">
                <?php
                $flash = $_SESSION['flash'] ?? null;
                if ($flash) {
                    unset($_SESSION['flash']);
                }
                ?>
                <?php if (!empty($flash['message'])) : ?>
                    <?php $flashType = (string)($flash['type'] ?? 'success'); ?>
                    <div class="alert <?= $flashType === 'error' ? 'alert-error' : 'alert-success' ?> flash" data-flash>
                        <?= e((string)$flash['message']) ?>
                    </div>
                <?php endif; ?>
                <?= $content ?>
            </main>
            <footer class="site-footer">
                <div class="content-inner">
                    <div class="footer-content">
                        <?php $buildId = trim((string)config('app.build', '')); ?>
                        <span>© <?= e(date('Y')) ?> <?= e((string)config('app.name', 'ThriftStack')) ?>. All rights reserved.</span>
                        <?php if ($buildId !== '') : ?>
                            <span class="footer-build">Build <?= e($buildId) ?></span>
                        <?php endif; ?>
                        <div class="footer-links">
                            <a href="/privacy">Privacy</a>
                            <a href="/terms">Terms</a>
                            <a href="/support">Support</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
</body>
</html>
