<?php

namespace ValentinMorice\LaravelBillingRepository\Contracts\Services;

use ValentinMorice\LaravelBillingRepository\Data\DTO\Config\ProductDefinition;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Service\ProductArchiveResult;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Service\ProductSyncResult;

/**
 * Provider-agnostic product service interface
 */
interface ProductServiceInterface
{
    /**
     * Sync a product with the billing provider
     */
    public function sync(string $productKey, ProductDefinition $definition): ProductSyncResult;

    /**
     * Archive products that were removed from configuration
     *
     * @param  array<int, string>  $configuredProductKeys
     */
    public function archiveRemoved(array $configuredProductKeys): ProductArchiveResult;
}
