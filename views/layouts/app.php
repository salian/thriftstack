<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'Thriftstack', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/assets/css/site.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body>
    <div class="page">
        <header class="site-header">
            <div class="container">
                <div class="brand">Thriftstack</div>
                <nav class="nav">
                    <a href="/">Home</a>
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
