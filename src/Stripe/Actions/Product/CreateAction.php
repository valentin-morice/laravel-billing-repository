<?php

namespace ValentinMorice\LaravelBillingRepository\Stripe\Actions\Product;

use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\Data\ProductDefinition;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

class CreateAction
{
    public function __construct(
        protected ProviderClientInterface $client
    ) {}

    public function handle(string $productKey, ProductDefinition $definition): BillingProduct
    {
        $stripeProductId = $this->client->product()->create(
            $definition->name,
            $definition->description
        );

        return BillingProduct::create([
            'key' => $productKey,
            'provider_id' => $stripeProductId,
            'name' => $definition->name,
            'description' => $definition->description,
            'active' => true,
        ]);
    }
}
