<?php

declare(strict_types=1);

final class View
{
    public static function render(string $view, array $data = [], string $layout = 'layouts/app'): string
    {
        $viewPath = __DIR__ . '/../../views/' . $view . '.php';
        $layoutPath = __DIR__ . '/../../views/' . $layout . '.php';

        if (!is_readable($viewPath)) {
            return 'View not found.';
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        if (is_readable($layoutPath)) {
            ob_start();
            require $layoutPath;
            return ob_get_clean();
        }

        return $content;
    }
}

if (!function_exists('e')) {
    function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('sanitize_string')) {
    function sanitize_string(string $value): string
    {
        return trim(strip_tags($value));
    }
}
