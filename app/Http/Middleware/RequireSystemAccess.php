<?php

declare(strict_types=1);

final class RequireSystemAccess
{
    public function handle(Request $request, callable $next)
    {
        $user = $request->session('user');
        $isAdmin = is_array($user) ? (int)($user['is_system_admin'] ?? 0) === 1 : false;
        $isStaff = is_array($user) ? (int)($user['is_system_staff'] ?? 0) === 1 : false;

        if (!$isAdmin && !$isStaff) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        return $next($request);
    }
}
