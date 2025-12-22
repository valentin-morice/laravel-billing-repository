<?php

namespace ValentinMorice\LaravelBillingRepository\Contracts\Services;

use ValentinMorice\LaravelBillingRepository\Data\ProductDefinition;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

/**
 * Provider-agnostic product service interface
 */
interface ProductServiceInterface
{
    /**
     * Sync a product with the billing provider
     *
     * @return array{action: string, product: BillingProduct}
     */
    public function sync(string $productKey, ProductDefinition $definition): array;

    /**
     * Archive products that were removed from configuration
     *
     * @param  array<int, string>  $configuredProductKeys
     */
    public function archiveRemoved(array $configuredProductKeys): int;
}
