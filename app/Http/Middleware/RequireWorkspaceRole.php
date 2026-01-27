<?php

declare(strict_types=1);

final class RequireWorkspaceRole
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

        $isAdmin = (int)(Auth::user()['is_system_admin'] ?? 0) === 1;
        $isStaff = (int)(Auth::user()['is_system_staff'] ?? 0) === 1;
        if (($isAdmin || $isStaff) && str_starts_with($request->path(), '/super-admin')) {
            return $next($request);
        }

        $userId = (int)(Auth::user()['id'] ?? 0);
        $service = new WorkspaceService($this->pdo, new Audit($this->pdo));
        $workspace = $service->ensureCurrentWorkspace($userId);

        if (!$workspace) {
            return Response::redirect('/teams');
        }

        $workspaceId = (int)$workspace['id'];
        $role = $service->membershipRole($userId, $workspaceId);
        if ($role === null) {
            return Response::redirect('/teams');
        }

        $permissions = $service->workspacePermissionsForRole($role);
        if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
            $_SESSION['user'] = [];
        }
        $_SESSION['user']['workspace_permissions'] = $permissions;

        if ($this->role !== null && !$service->isRoleAtLeast($role, $this->role)) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        return $next($request);
    }
}
