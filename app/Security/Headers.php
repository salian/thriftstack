<?php

declare(strict_types=1);

final class Headers
{
    public static function apply(array $config = []): void
    {
        $csp = "default-src 'self'; "
            . "script-src 'self' https://cdn.jsdelivr.net; "
            . "style-src 'self' https://cdnjs.cloudflare.com https://fonts.googleapis.com; "
            . "img-src 'self' data:; "
            . "font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; "
            . "connect-src 'self'; "
            . "frame-ancestors 'none'; "
            . "base-uri 'self'; "
            . "form-action 'self'";

        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header('Content-Security-Policy: ' . $csp);
    }
}
