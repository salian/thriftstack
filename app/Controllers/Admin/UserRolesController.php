<?php

declare(strict_types=1);

final class UserRolesController
{
    private PDO $pdo;
    private Audit $audit;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
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

        $actor = $request->session('user');
        $actorIsAdmin = is_array($actor) ? (int)($actor['is_system_admin'] ?? 0) === 1 : false;
        if (!$actorIsAdmin) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $userId = (int)$request->input('user_id', 0);
        $access = (string)$request->input('system_access', '');

        if ($userId <= 0) {
            return Response::redirect('/super-admin/usage');
        }

        $stmt = $this->pdo->prepare('SELECT is_system_admin, is_system_staff FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$current) {
            return Response::redirect('/super-admin/usage');
        }

        $currentAdmin = (int)($current['is_system_admin'] ?? 0) === 1;
        $newAdmin = $access === 'admin';
        $newStaff = $access === 'staff';

        if ($currentAdmin && !$newAdmin) {
            $countStmt = $this->pdo->query('SELECT COUNT(*) FROM users WHERE is_system_admin = 1');
            $superAdminCount = (int)$countStmt->fetchColumn();
            if ($superAdminCount <= 1) {
                $_SESSION['flash']['message'] = 'At least one System Admin is required.';
                return Response::redirect('/super-admin/usage');
            }
        }

        $update = $this->pdo->prepare('UPDATE users SET is_system_admin = ?, is_system_staff = ?, updated_at = ? WHERE id = ?');
        $update->execute([
            $newAdmin ? 1 : 0,
            $newStaff ? 1 : 0,
            date('Y-m-d H:i:s'),
            $userId,
        ]);

        $this->audit->log('system.access.updated', (int)($request->session('user')['id'] ?? 0), [
            'target_user_id' => $userId,
            'system_access' => $access,
        ]);

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
        $actorIsAdmin = (int)($request->session('user')['is_system_admin'] ?? 0) === 1;

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

        $stmt = $this->pdo->prepare('SELECT status, is_system_admin, is_system_staff FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$current) {
            return Response::redirect($redirect);
        }
        $currentStatus = (string)$current['status'];
        $targetIsAdmin = (int)($current['is_system_admin'] ?? 0) === 1;
        $targetIsStaff = (int)($current['is_system_staff'] ?? 0) === 1;
        if (($targetIsAdmin || $targetIsStaff) && !$actorIsAdmin) {
            $_SESSION['flash']['message'] = 'Only System Admins can change status for System Admin/Staff users.';
            return Response::redirect($redirect);
        }

        if ($currentStatus === $status) {
            return Response::redirect($redirect);
        }

        $update = $this->pdo->prepare('UPDATE users SET status = ?, updated_at = ? WHERE id = ?');
        $update->execute([$status, date('Y-m-d H:i:s'), $userId]);

        $action = $status === 'inactive' ? 'users.deactivated' : 'users.reactivated';
        $this->audit->log($action, $actorId, [
            'target_user_id' => $userId,
            'previous_status' => $currentStatus,
            'new_status' => $status,
        ]);

        $_SESSION['flash']['message'] = $status === 'inactive'
            ? 'User deactivated.'
            : 'User reactivated.';

        return Response::redirect($redirect);
    }
}
