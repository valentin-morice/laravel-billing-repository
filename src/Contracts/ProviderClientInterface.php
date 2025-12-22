<?php

namespace ValentinMorice\LaravelBillingRepository\Contracts;

use ValentinMorice\LaravelBillingRepository\Contracts\Resources\PriceResourceInterface;
use ValentinMorice\LaravelBillingRepository\Contracts\Resources\ProductResourceInterface;

/**
 * Provider client interface - works with any billing provider (Stripe, Paddle, etc.)
 */
interface ProviderClientInterface
{
    /**
     * Get the product resource interface
     */
    public function product(): ProductResourceInterface;

    /**
     * Get the price resource interface
     */
    public function price(): PriceResourceInterface;
}
