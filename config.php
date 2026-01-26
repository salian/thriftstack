<?php

declare(strict_types=1);

$envPath = __DIR__ . '/.env';
if (is_readable($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $parts = explode('=', $line, 2);
        $key = trim($parts[0] ?? '');
        if ($key === '') {
            continue;
        }
        $value = trim($parts[1] ?? '');
        $value = trim($value, " \t\n\r\0\x0B\"");
        $_ENV[$key] = $value;
        putenv($key . '=' . $value);
    }
}

$env = static function (string $key, $default = null) {
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }
    return $value;
};

return [
    'app' => [
        'name' => $env('THRIFTSTACK_APP_NAME', 'ThriftStack'),
        'env' => $env('THRIFTSTACK_APP_ENV', 'local'),
        'debug' => filter_var($env('THRIFTSTACK_APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN),
        'url' => $env('THRIFTSTACK_APP_URL', 'http://localhost'),
        'timezone' => $env('THRIFTSTACK_APP_TIMEZONE', 'UTC'),
        'key' => $env('THRIFTSTACK_APP_KEY', ''),
        'build' => $env('THRIFTSTACK_BUILD_ID', ''),
    ],
    'db' => [
        'driver' => $env('THRIFTSTACK_DB_DRIVER', 'mysql'),
        'host' => $env('THRIFTSTACK_DB_HOST', '127.0.0.1'),
        'name' => $env('THRIFTSTACK_DB_NAME', ''),
        'user' => $env('THRIFTSTACK_DB_USER', ''),
        'pass' => $env('THRIFTSTACK_DB_PASS', ''),
        'path' => $env('THRIFTSTACK_DB_PATH', __DIR__ . '/storage/database.sqlite'),
    ],
    'mail' => [
        'from_name' => $env('THRIFTSTACK_MAIL_FROM_NAME', 'ThriftStack'),
        'from_email' => $env('THRIFTSTACK_MAIL_FROM_EMAIL', 'no-reply@example.com'),
    ],
    'security' => [
        'show_errors_in_prod' => filter_var(
            $env('THRIFTSTACK_SECURITY_SHOW_ERRORS_IN_PROD', 'false'),
            FILTER_VALIDATE_BOOLEAN
        ),
        'show_errors_in_prod_for_admin_only' => filter_var(
            $env('THRIFTSTACK_SECURITY_SHOW_ERRORS_IN_PROD_FOR_ADMIN_ONLY', 'false'),
            FILTER_VALIDATE_BOOLEAN
        ),
    ],
    'auth' => [
        'require_verified' => filter_var($env('THRIFTSTACK_AUTH_REQUIRE_VERIFIED', 'true'), FILTER_VALIDATE_BOOLEAN),
    ],
];
