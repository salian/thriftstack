<?php

declare(strict_types=1);

final class Uploader
{
    private PDO $pdo;
    private string $basePath;

    private const PROFILE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    private const ATTACHMENT_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
        'text/plain',
    ];

    public function __construct(PDO $pdo, string $basePath)
    {
        $this->pdo = $pdo;
        $this->basePath = rtrim($basePath, '/');
    }

    public function uploadProfile(array $file, int $userId): array
    {
        return $this->store($file, $userId, 'profile', self::PROFILE_TYPES, 2 * 1024 * 1024);
    }

    public function uploadAttachment(array $file, int $userId): array
    {
        return $this->store($file, $userId, 'attachment', self::ATTACHMENT_TYPES, 8 * 1024 * 1024);
    }

    private function store(
        array $file,
        int $userId,
        string $type,
        array $allowedTypes,
        int $maxSize
    ): array {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Upload failed.'];
        }

        if (!is_uploaded_file($file['tmp_name'] ?? '')) {
            return ['ok' => false, 'error' => 'Invalid upload.'];
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > $maxSize) {
            return ['ok' => false, 'error' => 'File size is not allowed.'];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!$mime || !in_array($mime, $allowedTypes, true)) {
            return ['ok' => false, 'error' => 'File type is not allowed.'];
        }

        $originalName = basename((string)($file['name'] ?? ''));
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $randomName = bin2hex(random_bytes(16));
        $safeName = $randomName . ($extension ? '.' . strtolower($extension) : '');

        $relativeDir = $type . '/' . date('Y/m');
        $targetDir = $this->basePath . '/' . $relativeDir;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0750, true);
        }

        $relativePath = $relativeDir . '/' . $safeName;
        $targetPath = $this->basePath . '/' . $relativePath;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['ok' => false, 'error' => 'Could not save file.'];
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO uploads (user_id, type, original_name, path, mime_type, size, created_at)
             VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([$userId, $type, $originalName, $relativePath, $mime, $size]);

        return [
            'ok' => true,
            'id' => (int)$this->pdo->lastInsertId(),
            'path' => $relativePath,
            'mime' => $mime,
        ];
    }
}
