<?php

namespace ValentinMorice\LaravelBillingRepository\Stripe\Resources;

use Stripe\Product;
use Throwable;
use ValentinMorice\LaravelBillingRepository\Contracts\Resources\ProductResourceInterface;
use ValentinMorice\LaravelBillingRepository\Exceptions\Provider\ProviderException;
use ValentinMorice\LaravelBillingRepository\Stripe\Concerns\RetriesStripeRequests;

class ProductResource implements ProductResourceInterface
{
    use RetriesStripeRequests;

    /**
     * @throws ProviderException|Throwable
     */
    public function create(string $name, ?string $description = null): string
    {
        return $this->retryable(function () use ($name, $description) {
            $product = Product::create([
                'name' => $name,
                'description' => $description,
            ]);

            return $product->id;
        });
    }

    /**
     * @throws ProviderException|Throwable
     */
    public function retrieve(string $productId): object
    {
        return $this->retryable(fn () => Product::retrieve($productId));
    }

    /**
     * @throws ProviderException|Throwable
     */
    public function update(string $productId, array $params): object
    {
        return $this->retryable(fn () => Product::update($productId, $params));
    }

    /**
     * @throws ProviderException|Throwable
     */
    public function archive(string $productId): object
    {
        return $this->retryable(fn () => Product::update($productId, ['active' => false]));
    }
}
