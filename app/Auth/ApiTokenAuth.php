<?php

declare(strict_types=1);

final class ApiTokenAuth
{
    private PDO $pdo;
    /** @var string[] */
    private array $scopes;

    /**
     * @param string[] $scopes
     */
    public function __construct(PDO $pdo, array $scopes = [])
    {
        $this->pdo = $pdo;
        $this->scopes = $scopes;
    }

    public function handle(Request $request, callable $next)
    {
        $token = $this->extractToken();
        if ($token === null) {
            return $this->forbidden($request, 'Missing API token.');
        }

        $hash = hash('sha256', $token);
        $stmt = $this->pdo->prepare(
            'SELECT id, workspace_id, scopes, expires_at
             FROM api_tokens
             WHERE token_hash = ?
             LIMIT 1'
        );
        $stmt->execute([$hash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return $this->forbidden($request, 'Invalid API token.');
        }

        $expiresAt = $row['expires_at'] ?? null;
        if ($expiresAt && strtotime((string)$expiresAt) < time()) {
            return $this->forbidden($request, 'API token expired.');
        }

        $tokenScopes = $this->normalizeScopes((string)($row['scopes'] ?? ''));
        if ($this->scopes) {
            foreach ($this->scopes as $scope) {
                if (!in_array($scope, $tokenScopes, true)) {
                    return $this->forbidden($request, 'API token missing required scope.');
                }
            }
        }

        $_SESSION['workspace_id'] = (int)$row['workspace_id'];
        $_SESSION['api_token_id'] = (int)$row['id'];
        $_SESSION['api_token_scopes'] = $tokenScopes;

        $update = $this->pdo->prepare('UPDATE api_tokens SET last_used_at = ? WHERE id = ?');
        $update->execute([date('Y-m-d H:i:s'), (int)$row['id']]);

        return $next($request);
    }

    private function extractToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if ($header === '') {
            return null;
        }
        if (stripos($header, 'Bearer ') !== 0) {
            return null;
        }
        $token = trim(substr($header, 7));
        return $token !== '' ? $token : null;
    }

    /**
     * @return string[]
     */
    private function normalizeScopes(string $scopes): array
    {
        if ($scopes === '') {
            return [];
        }
        $parts = preg_split('/[,\s]+/', $scopes) ?: [];
        $result = [];
        foreach ($parts as $part) {
            $value = trim($part);
            if ($value !== '') {
                $result[] = $value;
            }
        }
        return array_values(array_unique($result));
    }

    private function forbidden(Request $request, string $message): Response
    {
        if (str_starts_with($request->path(), '/api/')) {
            return new Response(
                (string)json_encode(['ok' => false, 'error' => $message]),
                401,
                ['Content-Type' => 'application/json']
            );
        }
        return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
    }
}
