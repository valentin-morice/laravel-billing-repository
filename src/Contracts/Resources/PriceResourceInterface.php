<?php

namespace ValentinMorice\LaravelBillingRepository\Contracts\Resources;

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

    /**
     * List all prices for a specific product
     *
     * @param  string  $productId  The provider product ID
     * @return iterable<object> Iterator of price objects from provider
     */
    public function allForProduct(string $productId): iterable;
}
