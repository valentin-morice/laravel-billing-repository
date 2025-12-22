<?php

namespace ValentinMorice\LaravelBillingRepository\Contracts;

/**
 * Provider adapter interface - each billing provider implements this
 */
interface ProviderAdapterInterface
{
    /**
     * Get the provider name (e.g., 'stripe', 'paddle')
     */
    public function name(): string;

    /**
     * Get the provider client implementation
     */
    public function client(): ProviderClientInterface;
}
