<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'ThriftStack') ?></title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="/assets/css/site.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body>
    <div class="page">
        <header class="site-header">
            <div class="container">
                <div class="brand">
                    <img src="/assets/img/ai-stars.svg" alt="AI magic stars" class="brand-mark">
                    <?= e('ThriftStack') ?>
                </div>
                <nav class="nav" aria-label="Primary">
                    <a href="/">Home</a>
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
                                <div class="nav-menu-divider"></div>
                                <form method="post" action="/logout" class="nav-menu-form">
                                    <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                                    <button type="submit">Logout</button>
                                </form>
                            </div>
                        </details>
                        <a href="/uploads">Uploads</a>
                        <?php if ((Auth::user()['role'] ?? null) === 'Admin') : ?>
                            <a href="/admin/users">Admin</a>
                        <?php endif; ?>
                    <?php else : ?>
                        <a href="/login">Login</a>
                        <a href="/signup">Sign up</a>
                    <?php endif; ?>
                </nav>
            </div>
        </header>
        <main class="container main-layout">
            <aside class="sidebar" aria-label="Primary sidebar">
                <a href="/dashboard">Dashboard</a>
                <a href="/tasks">Tasks</a>
                <a href="/reports">Reports</a>
                <?php if (Auth::check()) : ?>
                    <a href="/admin/users">Admin</a>
                <?php endif; ?>
            </aside>
            <div class="main-content">
                <?= $content ?>
            </div>
        </main>
        <footer class="site-footer">
            <div class="container">
                <span>Starter skeleton ready.</span>
            </div>
        </footer>
    </div>
</body>
</html>
