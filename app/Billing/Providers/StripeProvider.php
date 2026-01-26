<?php

declare(strict_types=1);

final class StripeProvider implements BillingProvider
{
    private string $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    public function name(): string
    {
        return 'stripe';
    }

    public function verifySignature(string $payload, array $headers): bool
    {
        if ($this->secret === '') {
            return false;
        }
        $signatureHeader = $this->headerValue($headers, 'Stripe-Signature');
        if ($signatureHeader === null) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $part) {
            $pair = explode('=', trim($part), 2);
            if (count($pair) === 2) {
                $parts[$pair[0]] = $pair[1];
            }
        }

        if (empty($parts['t']) || empty($parts['v1'])) {
            return false;
        }

        $signedPayload = $parts['t'] . '.' . $payload;
        $expected = hash_hmac('sha256', $signedPayload, $this->secret);

        return hash_equals($expected, $parts['v1']);
    }

    public function eventType(array $payload): string
    {
        if (isset($payload['type'])) {
            return (string)$payload['type'];
        }
        return 'unknown';
    }

    private function headerValue(array $headers, string $name): ?string
    {
        $target = strtolower($name);
        foreach ($headers as $key => $value) {
            if (strtolower($key) === $target) {
                return is_array($value) ? (string)($value[0] ?? '') : (string)$value;
            }
        }
        return null;
    }
}
