<?php

namespace ValentinMorice\LaravelBillingRepository\Contracts;

interface PriceResourceInterface
{
    public function create(
        string $productId,
        int $amount,
        string $currency,
        ?array $recurring = null,
        ?string $nickname = null
    ): string;

    public function archive(string $priceId): object;
}
