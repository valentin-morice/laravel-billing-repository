<?php

namespace ValentinMorice\LaravelBillingRepository\Stripe\Resources;

use Stripe\Exception\ApiErrorException;
use Stripe\Product;
use ValentinMorice\LaravelBillingRepository\Contracts\Resources\ProductResourceInterface;

class ProductResource implements ProductResourceInterface
{
    /**
     * @throws ApiErrorException
     */
    public function create(string $name, ?string $description = null): string
    {
        $product = Product::create([
            'name' => $name,
            'description' => $description,
        ]);

        return $product->id;
    }

    /**
     * @throws ApiErrorException
     */
    public function retrieve(string $productId): object
    {
        return Product::retrieve($productId);
    }

    /**
     * @throws ApiErrorException
     */
    public function update(string $productId, array $params): object
    {
        return Product::update($productId, $params);
    }

    /**
     * @throws ApiErrorException
     */
    public function archive(string $productId): object
    {
        return Product::update($productId, ['active' => false]);
    }
}
