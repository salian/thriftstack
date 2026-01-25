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
        return Response::html(View::render('profile/index', [
            'title' => 'Profile',
            'message' => null,
            'error' => null,
            'uploads' => $this->fetchUploads($userId),
        ]));
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
            return Response::html(View::render('profile/index', [
                'title' => 'Profile',
                'message' => null,
                'error' => $result['error'],
                'uploads' => $this->fetchUploads($userId),
            ]), 422);
        }

        $this->audit->log('uploads.profile.created', $userId, ['upload_id' => $result['id']]);

        return Response::html(View::render('profile/index', [
            'title' => 'Profile',
            'message' => 'Profile image uploaded.',
            'error' => null,
            'uploads' => $this->fetchUploads($userId),
        ]));
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
            return Response::html(View::render('profile/index', [
                'title' => 'Profile',
                'message' => null,
                'error' => $result['error'],
                'uploads' => $this->fetchUploads($userId),
            ]), 422);
        }

        $this->audit->log('uploads.attachment.created', $userId, ['upload_id' => $result['id']]);

        return Response::html(View::render('profile/index', [
            'title' => 'Profile',
            'message' => 'Upload saved to My Uploads.',
            'error' => null,
            'uploads' => $this->fetchUploads($userId),
        ]));
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

        if ((int)$upload['user_id'] !== $userId && (Auth::user()['role'] ?? null) !== 'Admin') {
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
}
