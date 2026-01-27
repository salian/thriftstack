<?php

declare(strict_types=1);

final class RequireWorkspacePermission
{
    private PDO $pdo;
    private string $permission;

    public function __construct(PDO $pdo, string $permission)
    {
        $this->pdo = $pdo;
        $this->permission = $permission;
    }

    public function handle(Request $request, callable $next)
    {
        if (!Auth::check()) {
            return Response::redirect('/login');
        }

        if ((int)(Auth::user()['is_system_admin'] ?? 0) === 1) {
            return $next($request);
        }

        $userId = (int)(Auth::user()['id'] ?? 0);
        $service = new WorkspaceService($this->pdo, new Audit($this->pdo));
        $workspace = $service->ensureCurrentWorkspace($userId);

        if (!$workspace) {
            return Response::redirect('/teams');
        }

        $workspaceId = (int)$workspace['id'];
        if (!$service->hasWorkspacePermission($userId, $workspaceId, $this->permission)) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        return $next($request);
    }
}
