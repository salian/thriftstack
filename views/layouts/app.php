<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'ThriftStack') ?></title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="/assets/css/site.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script defer src="/assets/js/theme.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body>
    <div class="page" x-data="{
            sidebarCollapsed: false,
            theme: document.documentElement.getAttribute('data-theme') || 'light',
            toggleTheme() {
                this.theme = this.theme === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', this.theme);
                localStorage.setItem('thriftstack_theme', this.theme);
            }
        }" :class="sidebarCollapsed ? 'sidebar-collapsed' : ''">
        <aside class="sidebar" aria-label="Primary sidebar">
            <a class="brand sidebar-brand" href="/dashboard" aria-label="ThriftStack">
                <img src="/assets/img/ai-stars.svg" alt="AI magic stars" class="brand-mark">
                <span class="brand-text"><?= e('ThriftStack') ?></span>
            </a>
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
                <?php if (Auth::check()) : ?>
                    <a href="/admin/users" aria-label="Admin" data-tooltip="Admin">
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
                <button type="button" class="theme-toggle" @click="toggleTheme" data-tooltip="Theme" aria-label="Toggle theme">
                    <i class="fa-solid fa-moon sidebar-icon" aria-hidden="true"></i>
                    <span class="sidebar-label" x-text="theme === 'dark' ? 'Light mode' : 'Dark mode'"></span>
                    <span class="toggle-switch" aria-hidden="true">
                        <span class="toggle-knob" :class="theme === 'dark' ? 'is-on' : ''"></span>
                    </span>
                    <i class="fa-solid fa-sun sidebar-icon" aria-hidden="true"></i>
                </button>
            </nav>
        </aside>
        <div class="content-area">
            <header class="site-header">
                <div class="content-inner">
                    <div class="header-controls">
                        <button type="button" class="icon-button" @click="sidebarCollapsed = !sidebarCollapsed"
                            :aria-pressed="sidebarCollapsed.toString()" aria-label="Toggle sidebar">
                            <i class="fa-solid fa-angles-left" x-show="!sidebarCollapsed" aria-hidden="true"></i>
                            <i class="fa-solid fa-angles-right" x-show="sidebarCollapsed" aria-hidden="true"></i>
                        </button>
                    </div>
                    <nav class="nav" aria-label="Primary">
                        <?php if (Auth::check()) : ?>
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
