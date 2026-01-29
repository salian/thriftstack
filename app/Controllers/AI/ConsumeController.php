<?php

declare(strict_types=1);

final class ConsumeController
{
    private PDO $pdo;
    private CreditConsumer $consumer;
    private TokenScopeValidator $scopeValidator;
    private RateLimiter $rateLimiter;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->consumer = new CreditConsumer($pdo);
        $this->scopeValidator = new TokenScopeValidator();
        $this->rateLimiter = new RateLimiter($pdo);
    }

    public function consume(Request $request): Response
    {
        $userId = (int)($request->session('user')['id'] ?? 0);
        $workspaceService = new WorkspaceService($this->pdo, new Audit($this->pdo));
        $workspaceId = (int)($workspaceService->currentWorkspaceId() ?? 0);
        if ($workspaceId <= 0 || $workspaceService->membershipRole($userId, $workspaceId) === null) {
            return $this->json(
                ['ok' => false, 'error' => ['code' => 'forbidden', 'message' => 'Workspace access required.']],
                403
            );
        }

        if (!$this->hasApiScope('credits.consume')) {
            return $this->json(
                ['ok' => false, 'error' => ['code' => 'forbidden', 'message' => 'Missing credits.consume scope.']],
                403
            );
        }

        $usageType = trim((string)$request->input('usage_type', ''));
        if ($usageType === '' || strlen($usageType) > 50 || !preg_match('/^[a-z0-9._-]+$/i', $usageType)) {
            return $this->json(
                ['ok' => false, 'error' => ['code' => 'invalid_usage_type', 'message' => 'Invalid usage_type.']],
                422
            );
        }

        $credits = (int)$request->input('credits', 0);
        if ($credits <= 0) {
            return $this->json(
                ['ok' => false, 'error' => ['code' => 'invalid_credits', 'message' => 'Credits must be positive.']],
                422
            );
        }

        $limitCheck = $this->rateLimiter->checkLimit($workspaceId, $credits);
        if (!$limitCheck['ok']) {
            return $this->json(
                [
                    'ok' => false,
                    'error' => ['code' => 'rate_limit_exceeded', 'message' => $limitCheck['error'] ?? 'Limit exceeded.'],
                    'remaining_daily' => $limitCheck['remaining_daily'],
                    'remaining_monthly' => $limitCheck['remaining_monthly'],
                ],
                429
            );
        }

        $metadata = null;
        $metadataRaw = $request->input('metadata');
        if (is_string($metadataRaw) && $metadataRaw !== '') {
            $decoded = json_decode($metadataRaw, true);
            if (is_array($decoded)) {
                $metadata = $decoded;
            }
        }

        $result = $this->consumer->consume($workspaceId, $credits, $usageType, $metadata);
        if (!$result['ok']) {
            $code = $result['error'] === 'Insufficient credits.' ? 'insufficient_credits' : 'consume_failed';
            return $this->json(
                [
                    'ok' => false,
                    'balance_after' => $result['balance'],
                    'error' => ['code' => $code, 'message' => $result['error'] ?? 'Unable to consume credits.'],
                ],
                $code === 'insufficient_credits' ? 409 : 400
            );
        }

        return $this->json([
            'ok' => true,
            'balance_after' => $result['balance'],
        ]);
    }

    public function balance(Request $request): Response
    {
        $userId = (int)($request->session('user')['id'] ?? 0);
        $workspaceService = new WorkspaceService($this->pdo, new Audit($this->pdo));
        $workspaceId = (int)($workspaceService->currentWorkspaceId() ?? 0);
        if ($workspaceId <= 0 || $workspaceService->membershipRole($userId, $workspaceId) === null) {
            return $this->json(
                ['ok' => false, 'error' => ['code' => 'forbidden', 'message' => 'Workspace access required.']],
                403
            );
        }

        if (!$this->hasApiScope('credits.read')) {
            return $this->json(
                ['ok' => false, 'error' => ['code' => 'forbidden', 'message' => 'Missing credits.read scope.']],
                403
            );
        }

        $stmt = $this->pdo->prepare('SELECT ai_credit_balance FROM workspaces WHERE id = ? LIMIT 1');
        $stmt->execute([$workspaceId]);
        $balance = (int)($stmt->fetchColumn() ?? 0);

        return $this->json([
            'ok' => true,
            'balance' => $balance,
        ]);
    }

    private function hasApiScope(string $scope): bool
    {
        if (!empty($_SESSION['api_token_id'])) {
            $scopes = $_SESSION['api_token_scopes'] ?? [];
            if (!is_array($scopes)) {
                $scopes = [];
            }
            return $this->scopeValidator->requireScope($scopes, $scope);
        }
        return true;
    }

    private function json(array $payload, int $status = 200): Response
    {
        return new Response((string)json_encode($payload), $status, ['Content-Type' => 'application/json']);
    }
}
