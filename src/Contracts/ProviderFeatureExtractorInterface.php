<?php

namespace ValentinMorice\LaravelBillingRepository\Contracts;

use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

interface ProviderFeatureExtractorInterface
{
    /**
     * Extract provider-specific features from a price model
     * Returns null if no provider features exist
     */
    public function extractPriceFeatures(BillingPrice $price): ?object;

    /**
     * Extract provider-specific features from a product model
     * Returns null if no provider features exist
     */
    public function extractProductFeatures(BillingProduct $product): ?object;

    /**
     * Get the provider name this extractor handles
     * Used as the key in comparison objects (e.g., 'stripe', 'paddle')
     */
    public function getProviderName(): string;
}
