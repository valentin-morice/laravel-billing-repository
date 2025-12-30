<?php

namespace ValentinMorice\LaravelBillingRepository\Facades;

use Illuminate\Support\Collection;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

class BillingRepository
{
    public static function priceId(string $productKey, string $priceType): string
    {
        $product = self::findActiveProduct($productKey, withPrices: true);

        $price = BillingPrice::active()
            ->where('product_id', $product->id)
            ->where('type', $priceType)
            ->first();

        if (! $price) {
            throw new \InvalidArgumentException("Price '{$priceType}' not found for product '{$productKey}'");
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
            throw new \InvalidArgumentException(
                "Product '{$productKey}' not found."
            );
        }

        return $product;
    }
}
