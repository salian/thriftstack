<?php

declare(strict_types=1);

final class WebhooksController
{
    private BillingService $billing;
    /** @var array<string, BillingProvider> */
    private array $providers;

    /**
     * @param array<string, BillingProvider> $providers
     */
    public function __construct(BillingService $billing, array $providers)
    {
        $this->billing = $billing;
        $this->providers = $providers;
    }

    public function handle(Request $request): Response
    {
        $providerKey = (string)$request->param('provider', '');
        if ($providerKey === '' || !isset($this->providers[$providerKey])) {
            return Response::notFound('Not Found');
        }

        $payload = (string)file_get_contents('php://input');
        $headers = $this->getHeaders();
        $provider = $this->providers[$providerKey];

        if (!$provider->verifySignature($payload, $headers)) {
            return new Response('Invalid signature', 400, ['Content-Type' => 'text/plain']);
        }

        $data = json_decode($payload, true);
        $eventType = is_array($data) ? $provider->eventType($data) : 'unknown';
        $this->billing->recordWebhookEvent($providerKey, $eventType, $payload);

        return new Response('ok', 200, ['Content-Type' => 'text/plain']);
    }

    private function getHeaders(): array
    {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers)) {
                return $headers;
            }
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = $value;
            }
        }
        return $headers;
    }
}
