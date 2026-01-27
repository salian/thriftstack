<?php

declare(strict_types=1);

final class RequireSystemAdmin
{
    public function handle(Request $request, callable $next)
    {
        $user = $request->session('user');
        $isAdmin = is_array($user) ? (int)($user['is_system_admin'] ?? 0) === 1 : false;

        if (!$isAdmin) {
            $body = View::render('403', ['title' => 'Forbidden']);
            return Response::forbidden($body);
        }

        return $next($request);
    }
}
