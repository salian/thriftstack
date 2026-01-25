<?php

declare(strict_types=1);

final class AuthRequired
{
    public function handle(Request $request, callable $next)
    {
        if ($request->session('user') === null) {
            return Response::redirect('/login');
        }

        return $next($request);
    }
}
