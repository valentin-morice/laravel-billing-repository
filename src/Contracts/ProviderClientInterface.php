<?php

namespace ValentinMorice\LaravelBillingRepository\Contracts;

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
