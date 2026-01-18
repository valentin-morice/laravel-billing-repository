<?php

namespace ValentinMorice\LaravelBillingRepository\Facades\Support;

use Illuminate\Support\Collection;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Config\PriceDefinition;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Config\ProductDefinition;
use ValentinMorice\LaravelBillingRepository\Exceptions\Models\PriceNotFoundException;
use ValentinMorice\LaravelBillingRepository\Exceptions\Models\ProductNotFoundException;

class BillingConfigRepository
{
    /**
     * Get a product definition from config by key
     *
     * @throws ProductNotFoundException
     */
    public function product(string $key): ProductDefinition
    {
        $productData = config("billing.products.{$key}");

        if (! $productData) {
            throw new ProductNotFoundException("Product '{$key}' not found in config.");
        }

        return ProductDefinition::fromArray($productData);
    }

    /**
     * Get all product definitions from config
     *
     * @return Collection<string, ProductDefinition>
     */
    public function products(): Collection
    {
        $productsConfig = config('billing.products', []);

        return collect($productsConfig)
            ->map(fn (array $productData) => ProductDefinition::fromArray($productData));
    }

    /**
     * Check if a product definition exists in config
     */
    public function has(string $key): bool
    {
        return config("billing.products.{$key}") !== null;
    }

    /**
     * Get all product keys from config
     *
     * @return array<int, string>
     */
    public function keys(): array
    {
        return array_keys(config('billing.products', []));
    }

    /**
     * Get a price definition from config by product key and price type
     *
     * @throws ProductNotFoundException
     * @throws PriceNotFoundException
     */
    public function price(string $productKey, string $priceKey): PriceDefinition
    {
        $product = $this->product($productKey);

        if (! isset($product->prices[$priceKey])) {
            throw PriceNotFoundException::forProductAndKey($productKey, $priceKey);
        }

        return $product->prices[$priceKey];
    }
}
