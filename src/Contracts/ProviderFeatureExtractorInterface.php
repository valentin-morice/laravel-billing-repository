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
     * Extract provider-specific features from a raw provider price API object
     * Returns array of features to persist, or null if none exist
     */
    public function extractPriceFeaturesApi(object $providerPrice): ?array;

    /**
     * Extract provider-specific features from a raw provider product API object
     * Returns array of features to persist, or null if none exist
     */
    public function extractProductFeaturesApi(object $providerProduct): ?array;

    /**
     * Extract and clean metadata from a raw provider API object
     * Returns cleaned metadata array, or null if none exist
     */
    public function extractMetadata(object $providerObject): ?array;

    /**
     * Get the provider name this extractor handles
     * Used as the key in comparison objects (e.g., 'stripe', 'paddle')
     */
    public function getProviderName(): string;
}
