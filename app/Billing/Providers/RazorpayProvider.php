<?php

declare(strict_types=1);

final class RazorpayProvider extends HmacProvider
{
    public function name(): string
    {
        return 'razorpay';
    }

    protected function signatureHeader(): string
    {
        return 'X-Razorpay-Signature';
    }
}
