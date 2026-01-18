<?php

namespace ValentinMorice\LaravelBillingRepository\Facades\Support;

use Illuminate\Database\Eloquent\Collection;
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
     * @return Collection<int, BillingProduct>
     */
    public function products(): Collection
    {
        return BillingProduct::active()->with('prices')->get();
    }

    /**
     * Get a price model from database by product key and price type
     *
     * @throws ProductNotFoundException
     * @throws PriceNotFoundException
     */
    public function price(string $productKey, string $priceKey): BillingPrice
    {
        $product = $this->findActiveProduct($productKey, withPrices: true);

        $price = BillingPrice::active()
            ->where('product_id', $product->id)
            ->where('key', $priceKey)
            ->first();

        if (! $price) {
            throw PriceNotFoundException::forProductAndKey($productKey, $priceKey);
        }

        return $price;
    }

    /**
     * Get all active prices for a product
     *
     * @return Collection<int, BillingPrice>
     *
     * @throws ProductNotFoundException
     */
    public function prices(string $productKey): Collection
    {
        $product = $this->findActiveProduct($productKey, withPrices: true);

        /** @var Collection<int, BillingPrice> */
        return $product->prices()->where('active', true)->get();
    }

    /**
     * @throws ProductNotFoundException
     */
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
