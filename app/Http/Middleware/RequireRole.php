<?php

declare(strict_types=1);

final class RequireRole
{
    private string $role;

    public function __construct(string $role)
    {
        $this->role = $role;
    }

    public function handle(Request $request, callable $next): Response
    {
        $user = $request->session('user');
        $role = is_array($user) ? ($user['role'] ?? null) : null;

        if ($role !== $this->role) {
            $body = View::render('403', ['title' => 'Forbidden']);
            return Response::forbidden($body);
        }

        return $next($request);
    }
}
