<?php

declare(strict_types=1);

final class ApiTokensController
{
    private PDO $pdo;
    private WorkspaceService $workspaces;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->workspaces = new WorkspaceService($pdo, new Audit($pdo));
    }

    public function index(Request $request): Response
    {
        $workspaceId = $this->requireWorkspaceId();
        if ($workspaceId <= 0) {
            return Response::redirect('/teams');
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, name, scopes, last_used_at, expires_at, created_at
             FROM api_tokens
             WHERE workspace_id = ?
             ORDER BY created_at DESC'
        );
        $stmt->execute([$workspaceId]);
        $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $flash = $_SESSION['flash'] ?? null;
        if ($flash) {
            unset($_SESSION['flash']);
        }

        return Response::html(View::render('settings/api_tokens', [
            'title' => 'API Tokens',
            'tokens' => $tokens,
            'message' => $flash['message'] ?? null,
            'error' => $flash['error'] ?? null,
            'newToken' => $flash['token'] ?? null,
        ]));
    }

    public function create(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $workspaceId = $this->requireWorkspaceId();
        if ($workspaceId <= 0) {
            return Response::redirect('/teams');
        }

        $name = trim((string)$request->input('name', ''));
        $scopes = trim((string)$request->input('scopes', ''));
        $expiresAt = trim((string)$request->input('expires_at', ''));

        if ($name === '') {
            $_SESSION['flash'] = ['error' => 'Token name is required.'];
            return Response::redirect('/settings/api-tokens');
        }

        $token = bin2hex(random_bytes(24));
        $hash = hash('sha256', $token);

        $stmt = $this->pdo->prepare(
            'INSERT INTO api_tokens (workspace_id, token_hash, name, scopes, last_used_at, expires_at, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $workspaceId,
            $hash,
            $name,
            $scopes !== '' ? $scopes : null,
            null,
            $expiresAt !== '' ? $expiresAt : null,
            date('Y-m-d H:i:s'),
        ]);

        $_SESSION['flash'] = [
            'message' => 'API token created.',
            'token' => $token,
        ];

        return Response::redirect('/settings/api-tokens');
    }

    public function revoke(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $workspaceId = $this->requireWorkspaceId();
        if ($workspaceId <= 0) {
            return Response::redirect('/teams');
        }

        $tokenId = (int)$request->input('token_id', 0);
        if ($tokenId <= 0) {
            $_SESSION['flash'] = ['error' => 'Select a valid token.'];
            return Response::redirect('/settings/api-tokens');
        }

        $stmt = $this->pdo->prepare('DELETE FROM api_tokens WHERE id = ? AND workspace_id = ?');
        $stmt->execute([$tokenId, $workspaceId]);

        $_SESSION['flash'] = ['message' => 'API token revoked.'];
        return Response::redirect('/settings/api-tokens');
    }

    private function requireWorkspaceId(): int
    {
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $workspace = $this->workspaces->ensureCurrentWorkspace($userId);
        return (int)($workspace['id'] ?? 0);
    }
}
