<?php

declare(strict_types=1);

final class UserRolesController
{
    private PDO $pdo;
    private Rbac $rbac;
    private Audit $audit;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->rbac = new Rbac($pdo);
        $this->audit = new Audit($pdo);
    }

    public function index(Request $request): Response
    {
        return Response::notFound(View::render('404', ['title' => 'Not Found']));
    }

    public function assign(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $userId = (int)$request->input('user_id', 0);
        $roleId = (int)$request->input('role_id', 0);

        if ($userId > 0 && $roleId > 0) {
            $roles = $this->rbac->rolesById();
            $newRole = $roles[$roleId]['name'] ?? null;
            $currentRole = $this->rbac->roleForUser($userId);

            if ($currentRole === 'App Super Admin' && $newRole !== 'App Super Admin') {
                $countStmt = $this->pdo->prepare(
                    'SELECT COUNT(DISTINCT u.id)
                     FROM users u
                     INNER JOIN user_app_roles ur ON ur.user_id = u.id
                     INNER JOIN app_roles r ON r.id = ur.app_role_id
                     WHERE r.name = ?'
                );
                $countStmt->execute(['App Super Admin']);
                $superAdminCount = (int)$countStmt->fetchColumn();

                if ($superAdminCount <= 1) {
                    $_SESSION['flash']['message'] = 'At least one App Super Admin is required.';
                    return Response::redirect('/super-admin/usage');
                }
            }

            $this->rbac->assignRole($userId, $roleId);
        }

        return Response::redirect('/super-admin/usage');
    }

    public function updateStatus(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $userId = (int)$request->input('user_id', 0);
        $status = (string)$request->input('status', '');
        $redirect = (string)$request->input('redirect', '/super-admin/usage');
        $actorId = (int)($request->session('user')['id'] ?? 0);

        if (!str_starts_with($redirect, '/super-admin/usage')) {
            $redirect = '/super-admin/usage';
        }

        if ($userId <= 0 || !in_array($status, ['active', 'inactive'], true)) {
            return Response::redirect($redirect);
        }

        if ($actorId > 0 && $userId === $actorId) {
            $_SESSION['flash']['message'] = 'You cannot deactivate your own account.';
            return Response::redirect($redirect);
        }

        $stmt = $this->pdo->prepare('SELECT status FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $current = $stmt->fetchColumn();
        if ($current === false) {
            return Response::redirect($redirect);
        }
        $current = (string)$current;

        if ($current === $status) {
            return Response::redirect($redirect);
        }

        $update = $this->pdo->prepare('UPDATE users SET status = ?, updated_at = ? WHERE id = ?');
        $update->execute([$status, date('Y-m-d H:i:s'), $userId]);

        $action = $status === 'inactive' ? 'users.deactivated' : 'users.reactivated';
        $this->audit->log($action, $actorId, [
            'target_user_id' => $userId,
            'previous_status' => $current,
            'new_status' => $status,
        ]);

        $_SESSION['flash']['message'] = $status === 'inactive'
            ? 'User deactivated.'
            : 'User reactivated.';

        return Response::redirect($redirect);
    }
}
