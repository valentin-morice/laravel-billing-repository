<?php

namespace ValentinMorice\LaravelStripeRepository\Contracts;

interface PriceResourceInterface
{
    public function create(
        string $productId,
        int $amount,
        string $currency,
        ?array $recurring = null
    ): string;
}
