<?php

namespace ValentinMorice\LaravelBillingRepository\Contracts\Services;

use ValentinMorice\LaravelBillingRepository\Data\PriceDefinition;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

/**
 * Provider-agnostic price service interface
 */
interface PriceServiceInterface
{
    /**
     * Sync a price with the billing provider
     *
     * @return array{action: string, price?: BillingPrice, old?: BillingPrice, new?: BillingPrice}
     */
    public function sync(BillingProduct $product, string $priceType, PriceDefinition $definition): array;

    /**
     * Archive prices that were removed from configuration
     *
     * @param  array<int, string>  $configuredPriceTypes
     */
    public function archiveRemoved(BillingProduct $product, array $configuredPriceTypes): int;
}
