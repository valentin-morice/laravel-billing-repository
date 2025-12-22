<?php

namespace ValentinMorice\LaravelBillingRepository\Stripe\Actions\Product;

use ValentinMorice\LaravelBillingRepository\Contracts\ProviderClientInterface;
use ValentinMorice\LaravelBillingRepository\Data\ProductDefinition;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

class UpdateAction
{
    public function __construct(
        protected ProviderClientInterface $client
    ) {}

    public function handle(BillingProduct $product, ProductDefinition $definition): BillingProduct
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

        $this->client->product()->update($product->provider_id, $params);

        $product->update($params);

        return $product->fresh();
    }
}
