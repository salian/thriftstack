<?php

declare(strict_types=1);

final class RequirePermission
{
    private string $permission;

    public function __construct(string $permission)
    {
        $this->permission = $permission;
    }

    public function handle(Request $request, callable $next): Response
    {
        $user = $request->session('user');
        $permissions = is_array($user) ? ($user['permissions'] ?? []) : [];

        if (!in_array($this->permission, $permissions, true)) {
            $body = View::render('403', ['title' => 'Forbidden']);
            return Response::forbidden($body);
        }

        return $next($request);
    }
}
