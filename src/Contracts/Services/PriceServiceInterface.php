<?php

namespace ValentinMorice\LaravelBillingRepository\Contracts\Services;

use ValentinMorice\LaravelBillingRepository\Data\DTO\Config\PriceDefinition;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Service\PriceArchiveResult;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Service\PriceSyncResult;
use ValentinMorice\LaravelBillingRepository\Data\Enum\ImmutableFieldStrategy;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

/**
 * Provider-agnostic price service interface
 */
interface PriceServiceInterface
{
    /**
     * Sync a price with the billing provider
     *
     * @param  ImmutableFieldStrategy|null  $strategy  Strategy for handling immutable field changes
     * @param  string|null  $newPriceKey  New key for duplicate strategy
     */
    public function sync(
        BillingProduct $product,
        string $priceKey,
        PriceDefinition $definition,
        ?ImmutableFieldStrategy $strategy = null,
        ?string $newPriceKey = null
    ): PriceSyncResult;

    /**
     * Archive prices that were removed from configuration
     *
     * @param  array<int, string>  $configuredPriceKeys
     */
    public function archiveRemoved(BillingProduct $product, array $configuredPriceKeys): PriceArchiveResult;
}
