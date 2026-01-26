<?php

declare(strict_types=1);

abstract class HmacProvider implements BillingProvider
{
    protected string $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    abstract protected function signatureHeader(): string;

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

    protected function expectedSignature(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->secret);
    }

    public function verifySignature(string $payload, array $headers): bool
    {
        if ($this->secret === '') {
            return false;
        }
        $signature = $this->extractSignature($headers);
        if ($signature === null || $signature === '') {
            return false;
        }

        return hash_equals($this->expectedSignature($payload), $signature);
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
