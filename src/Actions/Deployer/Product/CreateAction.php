<?php

namespace ValentinMorice\LaravelStripeRepository\Actions\Deployer\Product;

use ValentinMorice\LaravelStripeRepository\Contracts\StripeClientInterface;
use ValentinMorice\LaravelStripeRepository\DataTransferObjects\ProductDefinition;
use ValentinMorice\LaravelStripeRepository\Models\StripeProduct;

class CreateAction
{
    public function __construct(
        protected StripeClientInterface $client
    ) {}

    public function handle(string $productKey, ProductDefinition $definition): StripeProduct
    {
        $stripeProductId = $this->client->product()->create(
            $definition->name,
            $definition->description
        );

        return StripeProduct::create([
            'key' => $productKey,
            'stripe_id' => $stripeProductId,
            'name' => $definition->name,
            'description' => $definition->description,
            'active' => true,
        ]);
    }
}
