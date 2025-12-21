<?php

namespace ValentinMorice\LaravelBillingRepository\Stripe;

use ValentinMorice\LaravelBillingRepository\Contracts\PriceResourceInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\ProductResourceInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;

class StripeClient implements ProviderClientInterface
{
    public function __construct(
        protected ?ProductResourceInterface $productResource = null,
        protected ?PriceResourceInterface $priceResource = null
    ) {
        $this->productResource ??= new ProductResource;
        $this->priceResource ??= new PriceResource;
    }

    public function product(): ProductResourceInterface
    {
        return $this->productResource;
    }

    public function price(): PriceResourceInterface
    {
        return $this->priceResource;
    }
}
