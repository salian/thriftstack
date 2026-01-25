<?php

declare(strict_types=1);

final class RoleRequired
{
    /** @var string[] */
    private array $roles;

    public function __construct(array $roles)
    {
        $this->roles = $roles;
    }

    public function handle(Request $request, callable $next)
    {
        $user = $request->session('user');
        $role = is_array($user) ? ($user['role'] ?? null) : null;

        if ($role === null || !in_array($role, $this->roles, true)) {
            $body = View::render('403', ['title' => 'Forbidden']);
            return Response::forbidden($body);
        }

        return $next($request);
    }
}
