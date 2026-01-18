<?php

namespace ValentinMorice\LaravelBillingRepository\Facades;

use ValentinMorice\LaravelBillingRepository\Data\Enum\Consumer\PriceKey;
use ValentinMorice\LaravelBillingRepository\Data\Enum\Consumer\ProductKey;
use ValentinMorice\LaravelBillingRepository\Exceptions\Models\PriceNotFoundException;
use ValentinMorice\LaravelBillingRepository\Exceptions\Models\ProductNotFoundException;
use ValentinMorice\LaravelBillingRepository\Facades\Support\BillingConfigRepository;
use ValentinMorice\LaravelBillingRepository\Facades\Support\BillingResourceRepository;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

class BillingRepository
{
    /**
     * Access config-based operations (ProductDefinition from config)
     */
    public static function config(): BillingConfigRepository
    {
        return new BillingConfigRepository;
    }

    /**
     * Access resource-based operations (BillingProduct/BillingPrice from database)
     */
    public static function resource(): BillingResourceRepository
    {
        return new BillingResourceRepository;
    }

    /**
     * @throws ProductNotFoundException
     * @throws PriceNotFoundException
     */
    public static function priceId(string|ProductKey $productKey, string|PriceKey $priceKey): string
    {
        $product = BillingProduct::active()
            ->where('key', $productKey)
            ->first();

        if (! $product) {
            throw new ProductNotFoundException("Product '{$productKey}' not found or not active");
        }

        $price = BillingPrice::active()
            ->where('product_id', $product->id)
            ->where('key', $priceKey)
            ->first();

        if (! $price) {
            throw PriceNotFoundException::forProductAndKey($productKey, $priceKey);
        }

        return $price->provider_id;
    }

    /**
     * @throws ProductNotFoundException
     */
    public static function productId(string|ProductKey $productKey): string
    {
        $product = BillingProduct::active()
            ->where('key', $productKey)
            ->first();

        if (! $product) {
            throw new ProductNotFoundException("Product '{$productKey}' not found or not active");
        }

        return $product->provider_id;
    }
}
