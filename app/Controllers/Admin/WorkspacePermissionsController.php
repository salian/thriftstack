<?php

declare(strict_types=1);

final class WorkspacePermissionsController
{
    private PDO $pdo;
    private Rbac $rbac;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->rbac = new Rbac($pdo);
    }

    public function create(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $name = trim((string)$request->input('name', ''));
        $description = trim((string)$request->input('description', ''));

        if ($name !== '') {
            $this->rbac->createWorkspacePermission($name, $description);
        }

        return Response::redirect('/super-admin/settings?tab=workspace-permissions');
    }

    public function updateRolePermissions(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $role = trim((string)$request->input('workspace_role', ''));
        $permissionIds = $request->input('permission_ids', []);

        if ($role !== '' && is_array($permissionIds)) {
            $this->rbac->setWorkspaceRolePermissions($role, $permissionIds);
        }

        return Response::redirect('/super-admin/settings?tab=workspace-roles');
    }
}
