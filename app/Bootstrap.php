<?php

declare(strict_types=1);

final class Bootstrap
{
    public static function init(): array
    {
        $config = require __DIR__ . '/../config.php';
        $GLOBALS['config'] = $config;

        date_default_timezone_set($config['app']['timezone'] ?? 'UTC');

        self::configureLogging($config);
        Headers::apply($config);
        self::configureErrors($config);
        self::startSession();

        return $config;
    }

    private static function configureErrors(array $config): void
    {
        $appEnv = $config['app']['env'] ?? 'production';
        $appDebug = (bool)($config['app']['debug'] ?? false);
        $security = $config['security'] ?? [];

        $showErrors = $appDebug || $appEnv !== 'production';
        if (!$showErrors && !empty($security['show_errors_in_prod'])) {
            $showErrors = true;
        }
        if (!$showErrors && !empty($security['show_errors_in_prod_for_admin_only'])) {
            $showErrors = self::isAdminSession();
        }

        error_reporting(E_ALL);
        ini_set('display_errors', $showErrors ? '1' : '0');
        ini_set('log_errors', '1');

        $logger = self::logger();
        $handler = new ErrorHandler($logger, $showErrors);
        $handler->register();
    }

    private static function configureLogging(array $config): void
    {
        $logFile = __DIR__ . '/../storage/logs/app.log';
        LogRotation::rotate($logFile);

        $logger = self::logger();
        $logger->info('bootstrap', ['env' => $config['app']['env'] ?? 'unknown']);
    }

    private static function logger(): Logger
    {
        static $logger = null;
        if ($logger instanceof Logger) {
            return $logger;
        }

        $logger = new Logger(__DIR__ . '/../storage/logs/app.log');
        return $logger;
    }

    private static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        session_name('thriftstack_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');

        session_start();
    }

    private static function isAdminSession(): bool
    {
        if (empty($_SESSION['user'])) {
            return false;
        }

        $user = $_SESSION['user'];
        $role = is_array($user) ? ($user['role'] ?? null) : null;

        return $role === 'Admin' || ($user['is_admin'] ?? false) === true;
    }
}
