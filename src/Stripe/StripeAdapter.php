<?php

namespace ValentinMorice\LaravelBillingRepository\Stripe;

use ValentinMorice\LaravelBillingRepository\Contracts\ProviderAdapter;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;

class StripeAdapter implements ProviderAdapter
{
    protected ?ProviderClientInterface $client = null;

    public function __construct()
    {
        // Client will be resolved from the container or created lazily
    }

    /**
     * Get the provider name
     */
    public function name(): string
    {
        return 'stripe';
    }

    /**
     * Get the provider client implementation
     */
    public function client(): ProviderClientInterface
    {
        if ($this->client === null) {
            $this->client = app(StripeClient::class);
        }

        return $this->client;
    }
}
