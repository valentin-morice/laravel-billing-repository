<?php

namespace ValentinMorice\LaravelStripeRepository\Actions\Deployer\Product;

use ValentinMorice\LaravelStripeRepository\Contracts\StripeClientInterface;
use ValentinMorice\LaravelStripeRepository\DataTransferObjects\ProductDefinition;
use ValentinMorice\LaravelStripeRepository\Models\StripeProduct;

class SyncAction
{
    public function __construct(
        protected StripeClientInterface $client
    ) {}

    public function handle(string $productKey, ProductDefinition $definition): StripeProduct
    {
        $existingProduct = StripeProduct::where('key', $productKey)->first();

        if ($existingProduct) {
            // For now, just retrieve and return existing product
            $this->client->product()->retrieve($existingProduct->stripe_id);

            return $existingProduct;
        }

        // Create new product in Stripe
        $stripeProductId = $this->client->product()->create(
            $definition->name,
            $definition->description
        );

        // Create record in database
        return StripeProduct::create([
            'key' => $productKey,
            'stripe_id' => $stripeProductId,
            'name' => $definition->name,
            'active' => true,
        ]);
    }
}
