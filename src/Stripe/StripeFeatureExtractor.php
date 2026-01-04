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
     * Get the provider name
     */
    public function getProviderName(): string
    {
        return 'stripe';
    }
}
