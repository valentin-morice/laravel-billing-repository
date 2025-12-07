<?php

namespace ValentinMorice\LaravelStripeRepository\Actions\Deployer\Product;

use ValentinMorice\LaravelStripeRepository\Contracts\StripeClientInterface;
use ValentinMorice\LaravelStripeRepository\DataTransferObjects\ProductDefinition;
use ValentinMorice\LaravelStripeRepository\Models\StripeProduct;

class UpdateAction
{
    public function __construct(
        protected StripeClientInterface $client
    ) {}

    public function handle(StripeProduct $product, ProductDefinition $definition): StripeProduct
    {
        $params = [];

        if ($product->name !== $definition->name) {
            $params['name'] = $definition->name;
        }

        if ($product->description !== $definition->description) {
            $params['description'] = $definition->description;
        }

        if (empty($params)) {
            return $product;
        }

        $this->client->product()->update($product->stripe_id, $params);

        $product->update($params);

        return $product->fresh();
    }
}
