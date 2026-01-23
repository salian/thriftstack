<?php

declare(strict_types=1);

final class UsersController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function index(Request $request): Response
    {
        $stmt = $this->pdo->query(
            'SELECT u.id, u.name, u.email, u.status, u.created_at, r.name AS role
             FROM users u
             LEFT JOIN user_roles ur ON ur.user_id = u.id
             LEFT JOIN roles r ON r.id = ur.role_id
             ORDER BY u.created_at DESC'
        );
        $users = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        return Response::html(View::render('admin/users/index', [
            'title' => 'Users',
            'users' => $users,
        ]));
    }
}
