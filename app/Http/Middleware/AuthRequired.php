<?php

declare(strict_types=1);

final class AuthRequired
{
    public function handle(Request $request, callable $next): Response
    {
        if ($request->session('user') === null) {
            return Response::redirect('/login');
        }

        return $next($request);
    }
}
