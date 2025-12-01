<?php

namespace ValentinMorice\LaravelStripeRepository\Stripe;

use ValentinMorice\LaravelStripeRepository\Contracts\PriceResourceInterface;
use ValentinMorice\LaravelStripeRepository\Contracts\ProductResourceInterface;
use ValentinMorice\LaravelStripeRepository\Contracts\StripeClientInterface;

class StripeClient implements StripeClientInterface
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
