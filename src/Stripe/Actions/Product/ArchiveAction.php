<?php

namespace ValentinMorice\LaravelBillingRepository\Stripe\Actions\Product;

use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

class ArchiveAction
{
    public function __construct(
        protected ProviderClientInterface $client
    ) {}

    public function handle(BillingProduct $product): BillingProduct
    {
        $this->client->product()->archive($product->provider_id);
        $product->update(['active' => false]);

        return $product->fresh();
    }
}
