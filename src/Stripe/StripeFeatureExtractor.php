<?php

namespace ValentinMorice\LaravelBillingRepository\Stripe;

use ValentinMorice\LaravelBillingRepository\Contracts\ProviderFeatureExtractorInterface;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

class StripeFeatureExtractor implements ProviderFeatureExtractorInterface
{
    /**
     * Extract Stripe-specific features from a price model
     */
    public function extractPriceFeatures(BillingPrice $price): ?object
    {
        if (! $price->stripe) {
            return null;
        }

        $data = [];

        if ($price->stripe->tax_behavior !== null) {
            $data['tax_behavior'] = $price->stripe->tax_behavior;
        }

        if ($price->stripe->lookup_key !== null) {
            $data['lookup_key'] = $price->stripe->lookup_key;
        }

        return ! empty($data) ? (object) $data : null;
    }

    /**
     * Extract Stripe-specific features from a product model
     */
    public function extractProductFeatures(BillingProduct $product): ?object
    {
        if (! $product->stripe) {
            return null;
        }

        $data = [];

        if ($product->stripe->tax_code !== null) {
            $data['tax_code'] = $product->stripe->tax_code;
        }

        if ($product->stripe->statement_descriptor !== null) {
            $data['statement_descriptor'] = $product->stripe->statement_descriptor;
        }

        return ! empty($data) ? (object) $data : null;
    }

    /**
     * Extract Stripe-specific features from a raw API price object
     */
    public function extractPriceFeaturesApi(object $providerPrice): ?array
    {
        $features = [];

        if (isset($providerPrice->tax_behavior)) {
            $features['tax_behavior'] = $providerPrice->tax_behavior;
        }

        if (isset($providerPrice->lookup_key)) {
            $features['lookup_key'] = $providerPrice->lookup_key;
        }

        return empty($features) ? null : $features;
    }

    /**
     * Extract Stripe-specific features from a raw API product object
     */
    public function extractProductFeaturesApi(object $providerProduct): ?array
    {
        $features = [];

        if (isset($providerProduct->tax_code)) {
            $features['tax_code'] = $providerProduct->tax_code;
        }

        if (isset($providerProduct->statement_descriptor)) {
            $features['statement_descriptor'] = $providerProduct->statement_descriptor;
        }

        return empty($features) ? null : $features;
    }

    /**
     * Extract and clean metadata from Stripe API object
     */
    public function extractMetadata(object $providerObject): ?array
    {
        if (! isset($providerObject->metadata) || ! $providerObject->metadata) {
            return null;
        }

        $metadata = $providerObject->metadata;

        // Use Reflection to access private _values property from StripeObject
        if (is_object($metadata)) {
            try {
                $reflection = new \ReflectionProperty($metadata::class, '_values');
                $values = $reflection->getValue($metadata);

                return is_array($values) && ! empty($values) ? $values : null;
            } catch (\ReflectionException) {
                return null;
            }
        }

        return null;
    }

    /**
     * Get the provider name
     */
    public function getProviderName(): string
    {
        return 'stripe';
    }
}
