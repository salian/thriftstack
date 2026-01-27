<?php

declare(strict_types=1);

abstract class HmacProvider implements BillingProvider
{
    protected PaymentGatewaySettingsService $settings;

    public function __construct(PaymentGatewaySettingsService $settings)
    {
        $this->settings = $settings;
    }

    abstract protected function signatureHeader(): string;

    protected function secretKey(): string
    {
        $settings = $this->settings->getProviderSettings($this->name());
        return (string)($settings['webhook_secret'] ?? '');
    }

    protected function extractSignature(array $headers): ?string
    {
        $key = strtolower($this->signatureHeader());
        foreach ($headers as $name => $value) {
            if (strtolower($name) === $key) {
                return is_array($value) ? (string)($value[0] ?? '') : (string)$value;
            }
        }
        return null;
    }

    public function verifySignature(string $payload, array $headers): bool
    {
        $secret = $this->secretKey();
        if ($secret === '') {
            return false;
        }
        $signature = $this->extractSignature($headers);
        if ($signature === null || $signature === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $payload, $secret), $signature);
    }

    public function eventType(array $payload): string
    {
        if (isset($payload['event'])) {
            return (string)$payload['event'];
        }
        if (isset($payload['type'])) {
            return (string)$payload['type'];
        }
        return 'unknown';
    }
}
