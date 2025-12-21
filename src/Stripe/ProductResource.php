<?php

namespace ValentinMorice\LaravelBillingRepository\Stripe;

use Stripe\Product;
use ValentinMorice\LaravelBillingRepository\Contracts\ProductResourceInterface;

class ProductResource implements ProductResourceInterface
{
    public function create(string $name, ?string $description = null): string
    {
        $product = Product::create([
            'name' => $name,
            'description' => $description,
        ]);

        return $product->id;
    }

    public function retrieve(string $productId): object
    {
        return Product::retrieve($productId);
    }

    public function update(string $productId, array $params): object
    {
        return Product::update($productId, $params);
    }

    public function archive(string $productId): object
    {
        return Product::update($productId, ['active' => false]);
    }
}
