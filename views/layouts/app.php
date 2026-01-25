<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'ThriftStack') ?></title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
                <a class="brand sidebar-brand" href="/dashboard" aria-label="ThriftStack">
                    <svg class="brand-mark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M7.657 6.247c.11-.33.576-.33.686 0l.645 1.937a2.89 2.89 0 0 0 1.829 1.828l1.936.645c.33.11.33.576 0 .686l-1.937.645a2.89 2.89 0 0 0-1.828 1.829l-.645 1.936a.361.361 0 0 1-.686 0l-.645-1.937a2.89 2.89 0 0 0-1.828-1.828l-1.937-.645a.361.361 0 0 1 0-.686l1.937-.645a2.89 2.89 0 0 0 1.828-1.828zM3.794 1.148a.217.217 0 0 1 .412 0l.387 1.162c.173.518.579.924 1.097 1.097l1.162.387a.217.217 0 0 1 0 .412l-1.162.387A1.73 1.73 0 0 0 4.593 5.69l-.387 1.162a.217.217 0 0 1-.412 0L3.407 5.69A1.73 1.73 0 0 0 2.31 4.593l-1.162-.387a.217.217 0 0 1 0-.412l1.162-.387A1.73 1.73 0 0 0 3.407 2.31zM10.863.099a.145.145 0 0 1 .274 0l.258.774c.115.346.386.617.732.732l.774.258a.145.145 0 0 1 0 .274l-.774.258a1.16 1.16 0 0 0-.732.732l-.258.774a.145.145 0 0 1-.274 0l-.258-.774a1.16 1.16 0 0 0-.732-.732L9.1 2.137a.145.145 0 0 1 0-.274l.774-.258c.346-.115.617-.386.732-.732z"/>
                    </svg>
                    <span class="brand-text"><?= e('ThriftStack') ?></span>
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
                        <a href="/admin/users" aria-label="Admin" data-tooltip="Admin">
                            <i class="fa-solid fa-shield-halved sidebar-icon" aria-hidden="true"></i>
                            <span class="sidebar-label">Admin</span>
                        </a>
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
                            ?>
                            <details class="nav-user-menu">
                                <summary>
                                    <span class="avatar"><?= e($initials) ?></span>
                                    <span class="nav-user-name"><?= e($userName) ?></span>
                                    <span class="nav-chevron"></span>
                                </summary>
                                <div class="nav-menu-panel">
                                    <a href="/workspaces">Workspaces</a>
                                    <a href="/settings">Settings</a>
                                    <a href="/notifications">Notifications</a>
                                    <a href="/billing">Billing</a>
                                    <a href="/profile">Profile</a>
                                    <?php if ((Auth::user()['role'] ?? null) === 'Admin') : ?>
                                        <div class="nav-menu-divider"></div>
                                        <a href="/admin/users">Admin</a>
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
                <?= $content ?>
            </main>
            <footer class="site-footer">
                <div class="content-inner">
                    <span>Starter skeleton ready.</span>
                </div>
            </footer>
        </div>
    </div>
</body>
</html>
