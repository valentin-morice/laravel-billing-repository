<?php

namespace ValentinMorice\LaravelBillingRepository\Stripe\Actions\Price;

use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;

class ArchiveAction
{
    public function __construct(
        protected ProviderClientInterface $client
    ) {}

    public function handle(BillingPrice $price): BillingPrice
    {
        $this->client->price()->archive($price->provider_id);
        $price->update(['active' => false]);

        return $price->fresh();
    }
}
