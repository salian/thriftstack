<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Thriftstack') ?></title>
    <link rel="stylesheet" href="/assets/css/site.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body>
    <div class="page">
        <header class="site-header">
            <div class="container">
                <div class="brand"><?= e('Thriftstack') ?></div>
                <nav class="nav" aria-label="Primary">
                    <a href="/">Home</a>
                    <a href="/dashboard">Dashboard</a>
                    <?php if (Auth::check()) : ?>
                        <span class="nav-user">Hi, <?= e(Auth::user()['name'] ?? 'User') ?></span>
                        <form method="post" action="/logout">
                            <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                            <button type="submit" class="button button-ghost">Logout</button>
                        </form>
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
        <main class="container">
            <?= $content ?>
        </main>
        <footer class="site-footer">
            <div class="container">
                <span>Starter skeleton ready.</span>
            </div>
        </footer>
    </div>
</body>
</html>
