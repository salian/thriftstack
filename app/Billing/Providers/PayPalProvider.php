<?php

declare(strict_types=1);

final class PayPalProvider extends HmacProvider
{
    public function name(): string
    {
        return 'paypal';
    }

    protected function signatureHeader(): string
    {
        return 'PayPal-Transmission-Sig';
    }
}
