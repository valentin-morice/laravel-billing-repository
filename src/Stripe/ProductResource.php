<?php

namespace ValentinMorice\LaravelStripeRepository\Stripe;

use Stripe\Product;
use ValentinMorice\LaravelStripeRepository\Contracts\ProductResourceInterface;

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
}
