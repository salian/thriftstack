<?php

declare(strict_types=1);

final class UserRolesController
{
    private Rbac $rbac;

    public function __construct(PDO $pdo)
    {
        $this->rbac = new Rbac($pdo);
    }

    public function index(Request $request): Response
    {
        $users = $this->rbac->users();
        $roles = $this->rbac->roles();

        $rolesByUser = [];
        foreach ($users as $user) {
            $rolesByUser[(int)$user['id']] = $this->rbac->roleForUser((int)$user['id']);
        }

        return Response::html(View::render('admin/rbac/user_roles', [
            'title' => 'User Roles',
            'users' => $users,
            'roles' => $roles,
            'rolesByUser' => $rolesByUser,
        ]));
    }

    public function assign(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $userId = (int)$request->input('user_id', 0);
        $roleId = (int)$request->input('role_id', 0);

        if ($userId > 0 && $roleId > 0) {
            $this->rbac->assignRole($userId, $roleId);
        }

        return Response::redirect('/admin/user-roles');
    }
}
