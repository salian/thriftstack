<?php

declare(strict_types=1);

final class PermissionsController
{
    private Rbac $rbac;

    public function __construct(PDO $pdo)
    {
        $this->rbac = new Rbac($pdo);
    }

    public function index(Request $request): Response
    {
        $permissions = $this->rbac->permissions();

        return Response::html(View::render('admin/rbac/permissions', [
            'title' => 'Permissions',
            'permissions' => $permissions,
        ]));
    }

    public function create(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $name = trim((string)$request->input('name', ''));
        $description = trim((string)$request->input('description', ''));

        if ($name !== '') {
            $this->rbac->createPermission($name, $description);
        }

        return Response::redirect('/admin/permissions');
    }
}
