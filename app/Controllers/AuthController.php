<?php

declare(strict_types=1);

final class AuthController
{
    private PDO $pdo;
    private array $config;
    private Mailer $mailer;
    private Audit $audit;

    public function __construct(PDO $pdo, array $config)
    {
        $this->pdo = $pdo;
        $this->config = $config;
        $this->mailer = new Mailer($config);
        $this->audit = new Audit($pdo);
    }

    public function showLogin(Request $request): Response
    {
        return Response::html(View::render('auth/login', [
            'title' => 'Login',
            'error' => null,
        ]));
    }

    public function showSignup(Request $request): Response
    {
        return Response::html(View::render('auth/signup', [
            'title' => 'Create account',
            'error' => null,
        ]));
    }

    public function login(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $email = trim((string)$request->input('email', ''));
        $password = (string)$request->input('password', '');

        if ($email === '' || $password === '') {
            return $this->loginError('Email and password are required.');
        }

        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !Password::verify($password, $user['password_hash'])) {
            $this->audit->log('auth.login.failed', null, ['email' => $email]);
            return $this->loginError('Invalid credentials.');
        }

        $requireVerified = filter_var(
            $this->config['auth']['require_verified'] ?? true,
            FILTER_VALIDATE_BOOLEAN
        );

        if ($requireVerified && empty($user['email_verified_at'])) {
            $this->audit->log('auth.login.blocked', (int)$user['id'], ['reason' => 'email_unverified']);
            return $this->loginError('Please verify your email before logging in.');
        }

        $sessionUser = $this->buildSessionUser((int)$user['id']);
        Auth::login($sessionUser);
        $this->audit->log('auth.login.success', (int)$user['id']);

        return Response::redirect('/dashboard');
    }

    public function logout(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        Auth::logout();
        Csrf::rotate();

        return Response::redirect('/login');
    }

    public function signup(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $name = trim((string)$request->input('name', ''));
        $email = trim((string)$request->input('email', ''));
        $password = (string)$request->input('password', '');

        if ($name === '' || $email === '' || $password === '') {
            return $this->signupError('All fields are required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->signupError('Please provide a valid email address.');
        }

        if (strlen($password) < 8) {
            return $this->signupError('Password must be at least 8 characters.');
        }

        $exists = $this->pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $exists->execute([$email]);
        if ($exists->fetchColumn()) {
            return $this->signupError('An account with that email already exists.');
        }

        $now = date('Y-m-d H:i:s');
        $hash = Password::hash($password);
        $insert = $this->pdo->prepare(
            'INSERT INTO users (name, email, password_hash, email_verified_at, status, created_at, updated_at)
             VALUES (?, ?, ?, NULL, ?, ?, ?)'
        );
        $insert->execute([$name, $email, $hash, 'active', $now, $now]);
        $userId = (int)$this->pdo->lastInsertId();

        $token = $this->createEmailVerificationToken($userId);
        $this->sendVerificationEmail($email, $token);
        $this->audit->log('auth.signup', $userId, ['email' => $email]);

        return Response::html(View::render('auth/verify', [
            'title' => 'Verify your email',
            'message' => 'Account created. Check your email to verify your address.',
        ]));
    }

    public function verify(Request $request): Response
    {
        $token = (string)$request->query('token', '');
        if ($token === '') {
            return Response::html(View::render('auth/verify', [
                'title' => 'Verify your email',
                'message' => 'Verification token missing.',
            ]), 400);
        }

        $tokenHash = $this->hashToken($token);
        $stmt = $this->pdo->prepare(
            'SELECT id, user_id, expires_at FROM email_verifications WHERE token_hash = ? LIMIT 1'
        );
        $stmt->execute([$tokenHash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return Response::html(View::render('auth/verify', [
                'title' => 'Verify your email',
                'message' => 'Verification token is invalid.',
            ]), 400);
        }

        if (strtotime($row['expires_at']) < time()) {
            return Response::html(View::render('auth/verify', [
                'title' => 'Verify your email',
                'message' => 'Verification token has expired.',
            ]), 400);
        }

        $now = date('Y-m-d H:i:s');
        $update = $this->pdo->prepare('UPDATE users SET email_verified_at = ?, updated_at = ? WHERE id = ?');
        $update->execute([$now, $now, (int)$row['user_id']]);

        $delete = $this->pdo->prepare('DELETE FROM email_verifications WHERE id = ?');
        $delete->execute([(int)$row['id']]);
        $this->audit->log('auth.email.verified', (int)$row['user_id']);

        return Response::html(View::render('auth/verify', [
            'title' => 'Verify your email',
            'message' => 'Your email is verified. You can now log in.',
        ]));
    }

    public function showForgot(Request $request): Response
    {
        return Response::html(View::render('auth/forgot', [
            'title' => 'Reset password',
            'message' => null,
        ]));
    }

    public function sendReset(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $email = trim((string)$request->input('email', ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $userId = $stmt->fetchColumn();

            if ($userId) {
                $token = $this->createPasswordResetToken((int)$userId);
                $this->sendResetEmail($email, $token);
                $this->audit->log('auth.password.reset_requested', (int)$userId);
            }
        }

        return Response::html(View::render('auth/forgot', [
            'title' => 'Reset password',
            'message' => 'If the address exists, a reset link has been sent.',
        ]));
    }

    public function showReset(Request $request): Response
    {
        $token = (string)$request->query('token', '');
        return Response::html(View::render('auth/reset', [
            'title' => 'Set new password',
            'token' => $token,
            'error' => null,
            'message' => null,
        ]));
    }

    public function reset(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $token = (string)$request->input('token', '');
        $password = (string)$request->input('password', '');

        if ($token === '' || strlen($password) < 8) {
            return Response::html(View::render('auth/reset', [
                'title' => 'Set new password',
                'token' => $token,
                'error' => 'Token and a valid password are required.',
                'message' => null,
            ]), 422);
        }

        $tokenHash = $this->hashToken($token);
        $stmt = $this->pdo->prepare(
            'SELECT id, user_id, expires_at FROM password_resets WHERE token_hash = ? AND used_at IS NULL LIMIT 1'
        );
        $stmt->execute([$tokenHash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || strtotime($row['expires_at']) < time()) {
            return Response::html(View::render('auth/reset', [
                'title' => 'Set new password',
                'token' => $token,
                'error' => 'Reset token is invalid or expired.',
                'message' => null,
            ]), 400);
        }

        $now = date('Y-m-d H:i:s');
        $hash = Password::hash($password);
        $update = $this->pdo->prepare('UPDATE users SET password_hash = ?, updated_at = ? WHERE id = ?');
        $update->execute([$hash, $now, (int)$row['user_id']]);

        $mark = $this->pdo->prepare('UPDATE password_resets SET used_at = ? WHERE id = ?');
        $mark->execute([$now, (int)$row['id']]);
        $this->audit->log('auth.password.reset_completed', (int)$row['user_id']);

        return Response::html(View::render('auth/reset', [
            'title' => 'Set new password',
            'token' => '',
            'error' => null,
            'message' => 'Password updated. You can now log in.',
        ]));
    }

    private function loginError(string $message): Response
    {
        return Response::html(View::render('auth/login', [
            'title' => 'Login',
            'error' => $message,
        ]), 422);
    }

    private function signupError(string $message): Response
    {
        return Response::html(View::render('auth/signup', [
            'title' => 'Create account',
            'error' => $message,
        ]), 422);
    }

    private function buildSessionUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.name, u.email, r.name AS role
             FROM users u
             LEFT JOIN user_roles ur ON ur.user_id = u.id
             LEFT JOIN roles r ON r.id = ur.role_id
             WHERE u.id = ?
             LIMIT 1'
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $permissions = $this->fetchPermissions((int)($user['id'] ?? 0));

        return [
            'id' => (int)($user['id'] ?? $userId),
            'name' => $user['name'] ?? '',
            'email' => $user['email'] ?? '',
            'role' => $user['role'] ?? null,
            'permissions' => $permissions,
        ];
    }

    private function fetchPermissions(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT DISTINCT p.name
             FROM permissions p
             INNER JOIN role_permissions rp ON rp.permission_id = p.id
             INNER JOIN user_roles ur ON ur.role_id = rp.role_id
             WHERE ur.user_id = ?'
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    private function createEmailVerificationToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $tokenHash = $this->hashToken($token);
        $expires = date('Y-m-d H:i:s', time() + 60 * 60 * 48);

        $stmt = $this->pdo->prepare(
            'INSERT INTO email_verifications (user_id, token_hash, expires_at, created_at)
             VALUES (?, ?, ?, NOW())'
        );
        $stmt->execute([$userId, $tokenHash, $expires]);

        return $token;
    }

    private function createPasswordResetToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $tokenHash = $this->hashToken($token);
        $expires = date('Y-m-d H:i:s', time() + 60 * 60 * 2);

        $stmt = $this->pdo->prepare(
            'INSERT INTO password_resets (user_id, token_hash, expires_at, created_at)
             VALUES (?, ?, ?, NOW())'
        );
        $stmt->execute([$userId, $tokenHash, $expires]);

        return $token;
    }

    private function sendVerificationEmail(string $email, string $token): void
    {
        $url = rtrim($this->config['app']['url'] ?? '', '/');
        $link = $url . '/verify?token=' . urlencode($token);
        $message = "Verify your email address by clicking the link:\n\n{$link}\n\nThis link expires in 48 hours.";

        $this->mailer->send($email, 'Verify your email', $message);
    }

    private function sendResetEmail(string $email, string $token): void
    {
        $url = rtrim($this->config['app']['url'] ?? '', '/');
        $link = $url . '/reset?token=' . urlencode($token);
        $message = "Reset your password by clicking the link:\n\n{$link}\n\nThis link expires in 2 hours.";

        $this->mailer->send($email, 'Reset your password', $message);
    }

    private function hashToken(string $token): string
    {
        $key = (string)($this->config['app']['key'] ?? '');
        return hash_hmac('sha256', $token, $key);
    }
}
