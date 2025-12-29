<?php

namespace ValentinMorice\LaravelBillingRepository\Contracts\Services;

use ValentinMorice\LaravelBillingRepository\Data\DTO\Config\PriceDefinition;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Service\PriceArchiveResult;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Service\PriceSyncResult;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

/**
 * Provider-agnostic price service interface
 */
interface PriceServiceInterface
{
    /**
     * Sync a price with the billing provider
     */
    public function sync(BillingProduct $product, string $priceType, PriceDefinition $definition): PriceSyncResult;

    /**
     * Archive prices that were removed from configuration
     *
     * @param  array<int, string>  $configuredPriceTypes
     */
    public function archiveRemoved(BillingProduct $product, array $configuredPriceTypes): PriceArchiveResult;
}
