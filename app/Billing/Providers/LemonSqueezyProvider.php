<?php

declare(strict_types=1);

final class LemonSqueezyProvider extends HmacProvider
{
    public function name(): string
    {
        return 'lemonsqueezy';
    }

    protected function signatureHeader(): string
    {
        return 'X-Signature';
    }
}
