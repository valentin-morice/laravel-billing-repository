<?php

namespace ValentinMorice\LaravelBillingRepository\Stripe;

use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\Resources\PriceResourceInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\Resources\ProductResourceInterface;
use ValentinMorice\LaravelBillingRepository\Stripe\Resources\PriceResource;
use ValentinMorice\LaravelBillingRepository\Stripe\Resources\ProductResource;

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
