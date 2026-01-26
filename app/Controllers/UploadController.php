<?php

declare(strict_types=1);

final class UploadController
{
    private PDO $pdo;
    private Audit $audit;
    private string $storagePath;

    public function __construct(PDO $pdo, string $storagePath)
    {
        $this->pdo = $pdo;
        $this->audit = new Audit($pdo);
        $this->storagePath = rtrim($storagePath, '/');
    }

    public function show(Request $request): Response
    {
        $userId = (int)($request->session('user')['id'] ?? 0);
        return $this->renderProfile($userId, null, null);
    }

    public function uploadProfile(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $userId = (int)($request->session('user')['id'] ?? 0);
        $uploader = new Uploader($this->pdo, $this->storagePath . '/uploads');
        $result = $uploader->uploadProfile($_FILES['profile'] ?? [], $userId);

        if (!$result['ok']) {
            return $this->renderProfile($userId, null, $result['error'], 422);
        }

        $this->audit->log('uploads.profile.created', $userId, ['upload_id' => $result['id']]);

        return $this->renderProfile($userId, 'Profile image uploaded.', null);
    }

    public function uploadAttachment(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $userId = (int)($request->session('user')['id'] ?? 0);
        $uploader = new Uploader($this->pdo, $this->storagePath . '/uploads');
        $result = $uploader->uploadAttachment($_FILES['attachment'] ?? [], $userId);

        if (!$result['ok']) {
            return $this->renderProfile($userId, null, $result['error'], 422);
        }

        $this->audit->log('uploads.attachment.created', $userId, ['upload_id' => $result['id']]);

        return $this->renderProfile($userId, 'Upload saved to My Uploads.', null);
    }

    public function updatePassword(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $userId = (int)($request->session('user')['id'] ?? 0);
        $current = (string)$request->input('current_password', '');
        $newPassword = (string)$request->input('new_password', '');
        $confirm = (string)$request->input('confirm_password', '');

        if ($current === '' || $newPassword === '' || $confirm === '') {
            return $this->renderProfile($userId, null, 'All password fields are required.', 422);
        }

        if (strlen($newPassword) < 8) {
            return $this->renderProfile($userId, null, 'New password must be at least 8 characters.', 422);
        }

        if ($newPassword !== $confirm) {
            return $this->renderProfile($userId, null, 'New passwords do not match.', 422);
        }

        $stmt = $this->pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();

        if (!$hash || !Password::verify($current, (string)$hash)) {
            return $this->renderProfile($userId, null, 'Current password is incorrect.', 422);
        }

        $now = date('Y-m-d H:i:s');
        $newHash = Password::hash($newPassword);
        $update = $this->pdo->prepare('UPDATE users SET password_hash = ?, updated_at = ? WHERE id = ?');
        $update->execute([$newHash, $now, $userId]);

        $this->audit->log('auth.password.changed', $userId);

        return $this->renderProfile($userId, 'Password updated.', null);
    }

    public function deactivateAccount(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $userId = (int)($request->session('user')['id'] ?? 0);
        if ($userId <= 0) {
            return Response::redirect('/login');
        }

        $stmt = $this->pdo->prepare('UPDATE users SET status = ?, updated_at = ? WHERE id = ?');
        $stmt->execute(['inactive', date('Y-m-d H:i:s'), $userId]);

        $this->audit->log('account.deactivated', $userId);
        Auth::logout();
        Csrf::rotate();

        return Response::redirect('/login');
    }

    public function download(Request $request): Response
    {
        $userId = (int)($request->session('user')['id'] ?? 0);
        $uploadId = (int)$request->param('id', 0);
        if ($uploadId <= 0) {
            return Response::notFound(View::render('404', ['title' => 'Not Found']));
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, user_id, type, original_name, path, mime_type
             FROM uploads
             WHERE id = ? AND type = "attachment"'
        );
        $stmt->execute([$uploadId]);
        $upload = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$upload) {
            return Response::notFound(View::render('404', ['title' => 'Not Found']));
        }

        if ((int)$upload['user_id'] !== $userId && (Auth::user()['role'] ?? null) !== 'Super Admin') {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $path = $this->storagePath . '/uploads/' . $upload['path'];
        if (!is_file($path)) {
            return Response::notFound(View::render('404', ['title' => 'Not Found']));
        }

        $headers = [
            'Content-Type' => $upload['mime_type'],
            'Content-Disposition' => 'attachment; filename="' . basename($upload['original_name']) . '"',
        ];

        return new Response((string)file_get_contents($path), 200, $headers);
    }

    private function fetchUploads(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, type, original_name, created_at
             FROM uploads
             WHERE user_id = ?
             ORDER BY created_at DESC
             LIMIT 15'
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function renderProfile(int $userId, ?string $message, ?string $error, int $status = 200): Response
    {
        return Response::html(View::render('profile/index', [
            'title' => 'Profile',
            'message' => $message,
            'error' => $error,
            'uploads' => $this->fetchUploads($userId),
            'user' => $_SESSION['user'] ?? [],
        ]), $status);
    }
}
