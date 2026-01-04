<?php

namespace ValentinMorice\LaravelBillingRepository\Facades\Support;

use ValentinMorice\LaravelBillingRepository\Exceptions\Models\PriceNotFoundException;
use ValentinMorice\LaravelBillingRepository\Exceptions\Models\ProductNotFoundException;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

class BillingResourceRepository
{
    /**
     * Get a product model from database by key
     *
     * @throws ProductNotFoundException
     */
    public function product(string $key): BillingProduct
    {
        return $this->findActiveProduct($key, withPrices: true);
    }

    /**
     * Get all active product models from database
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, BillingProduct>
     */
    public function products(): \Illuminate\Database\Eloquent\Collection
    {
        return BillingProduct::active()->with('prices')->get();
    }

    /**
     * Get a price model from database by product key and price type
     *
     * @throws ProductNotFoundException
     * @throws PriceNotFoundException
     */
    public function price(string $productKey, string $priceType): BillingPrice
    {
        $product = $this->findActiveProduct($productKey, withPrices: true);

        $price = BillingPrice::active()
            ->where('product_id', $product->id)
            ->where('type', $priceType)
            ->first();

        if (! $price) {
            throw PriceNotFoundException::forProductAndType($productKey, $priceType);
        }

        return $price;
    }

    /**
     * Get all active prices for a product
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, BillingPrice>
     *
     * @throws ProductNotFoundException
     */
    public function prices(string $productKey): \Illuminate\Database\Eloquent\Collection
    {
        $product = $this->findActiveProduct($productKey, withPrices: true);

        /** @var \Illuminate\Database\Eloquent\Collection<int, BillingPrice> */
        return $product->prices()->where('active', true)->get();
    }

    /**
     * Get Stripe price ID for use with Cashier
     *
     * @throws ProductNotFoundException
     * @throws PriceNotFoundException
     */
    public function priceId(string $productKey, string $priceType): string
    {
        return $this->price($productKey, $priceType)->provider_id;
    }

    /**
     * Get Stripe product ID for use with Cashier
     *
     * @throws ProductNotFoundException
     */
    public function productId(string $productKey): string
    {
        return $this->findActiveProduct($productKey)->provider_id;
    }

    private function findActiveProduct(string $productKey, bool $withPrices = false): BillingProduct
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
