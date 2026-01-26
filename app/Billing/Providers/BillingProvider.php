<?php

declare(strict_types=1);

interface BillingProvider
{
    public function name(): string;

    public function verifySignature(string $payload, array $headers): bool;

    public function eventType(array $payload): string;
}
