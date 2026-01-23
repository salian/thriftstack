<?php

declare(strict_types=1);

$routes = require __DIR__ . '/../routes/web.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rtrim($path, '/') ?: '/';

$view = $routes[$path] ?? null;

http_response_code($view ? 200 : 404);

$title = $view ? 'Thriftstack' : 'Not Found';

ob_start();
if ($view) {
    require __DIR__ . '/../views/' . $view;
} else {
    require __DIR__ . '/../views/404.php';
}
$content = ob_get_clean();

require __DIR__ . '/../views/layouts/app.php';
