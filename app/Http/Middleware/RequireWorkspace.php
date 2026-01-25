<?php

declare(strict_types=1);

final class RequireWorkspace
{
    private PDO $pdo;
    private ?string $role;

    public function __construct(PDO $pdo, ?string $role = null)
    {
        $this->pdo = $pdo;
        $this->role = $role;
    }

    public function handle(Request $request, callable $next)
    {
        if (!Auth::check()) {
            return Response::redirect('/login');
        }

        $userId = (int)(Auth::user()['id'] ?? 0);
        $service = new WorkspaceService($this->pdo, new Audit($this->pdo));
        $workspace = $service->ensureCurrentWorkspace($userId);

        if (!$workspace) {
            return Response::redirect('/workspaces');
        }

        $workspaceId = (int)$workspace['id'];
        $role = $service->membershipRole($userId, $workspaceId);
        if ($role === null) {
            return Response::redirect('/workspaces');
        }

        if ($this->role !== null && !$service->isRoleAtLeast($role, $this->role)) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        return $next($request);
    }
}
