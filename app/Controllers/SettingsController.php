<?php

declare(strict_types=1);

final class SettingsController
{
    private PDO $pdo;
    private SettingsService $settings;

    public function __construct(PDO $pdo, SettingsService $settings)
    {
        $this->pdo = $pdo;
        $this->settings = $settings;
    }

    public function index(Request $request): Response
    {
        $userId = (int)($request->session('user')['id'] ?? 0);
        return $this->render($userId, null, null);
    }

    public function updateProfile(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $userId = (int)($request->session('user')['id'] ?? 0);
        $name = trim((string)$request->input('name', ''));

        if ($name === '') {
            return $this->render($userId, null, 'Name is required.');
        }

        if (strlen($name) < 2 || strlen($name) > 120) {
            return $this->render($userId, null, 'Name must be between 2 and 120 characters.');
        }

        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('UPDATE users SET name = ?, updated_at = ? WHERE id = ?');
        $stmt->execute([$name, $now, $userId]);

        $_SESSION['user']['name'] = $name;

        return $this->render($userId, 'Profile updated.', null);
    }

    public function updatePreferences(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $userId = (int)($request->session('user')['id'] ?? 0);
        $settings = [
            'notify_email' => $request->input('notify_email') === 'on',
            'notify_in_app' => $request->input('notify_in_app') === 'on',
            'notify_digest' => $request->input('notify_digest') === 'on',
        ];

        $this->settings->saveSettings($userId, $settings);

        return $this->render($userId, 'Preferences updated.', null);
    }

    private function render(int $userId, ?string $message, ?string $error): Response
    {
        $stmt = $this->pdo->prepare('SELECT name, email FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return Response::html(View::render('settings/index', [
            'title' => 'Settings',
            'message' => $message,
            'error' => $error,
            'user' => $user,
            'settings' => $this->settings->getSettings($userId),
        ]));
    }
}
