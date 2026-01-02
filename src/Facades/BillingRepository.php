<?php

namespace ValentinMorice\LaravelBillingRepository\Facades;

use Illuminate\Support\Collection;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Config\ProductDefinition;
use ValentinMorice\LaravelBillingRepository\Exceptions\Models\PriceNotFoundException;
use ValentinMorice\LaravelBillingRepository\Exceptions\Models\ProductNotFoundException;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

class BillingRepository
{
    // ===== Config-based methods (ProductDefinition from config) =====

    /**
     * Get a product definition from config by key
     *
     * @throws ProductNotFoundException
     */
    public static function getProductDefinition(string $key): ProductDefinition
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
    public static function getAllProductDefinitions(): Collection
    {
        $productsConfig = config('billing.products', []);

        return collect($productsConfig)
            ->map(fn (array $productData) => ProductDefinition::fromArray($productData));
    }

    /**
     * Check if a product definition exists in config
     */
    public static function hasProductDefinition(string $key): bool
    {
        return config("billing.products.{$key}") !== null;
    }

    /**
     * Get all product keys from config
     *
     * @return array<int, string>
     */
    public static function getProductKeys(): array
    {
        return array_keys(config('billing.products', []));
    }

    // ===== Database model methods (BillingProduct from database) =====

    public static function priceId(string $productKey, string $priceType): string
    {
        $product = self::findActiveProduct($productKey, withPrices: true);

        $price = BillingPrice::active()
            ->where('product_id', $product->id)
            ->where('type', $priceType)
            ->first();

        if (! $price) {
            throw PriceNotFoundException::forProductAndType($productKey, $priceType);
        }

        return $price->provider_id;
    }

    public static function productId(string $productKey): string
    {
        $product = self::findActiveProduct($productKey);

        return $product->provider_id;
    }

    public static function prices(string $productKey): Collection
    {
        $product = self::findActiveProduct($productKey, withPrices: true);

        return $product->prices()->where('active', true)->get();
    }

    public static function product(string $productKey): BillingProduct
    {
        return self::findActiveProduct($productKey, withPrices: true);
    }

    private static function findActiveProduct(string $productKey, bool $withPrices = false): BillingProduct
    {
        $query = BillingProduct::active()
            ->where('key', $productKey);

        if ($withPrices) {
            $query->with('prices');
        }

        $product = $query->first();

        if (! $product) {
            throw new ProductNotFoundException("Product '{$productKey}' not found or not active");
        }

        return $product;
    }
}
