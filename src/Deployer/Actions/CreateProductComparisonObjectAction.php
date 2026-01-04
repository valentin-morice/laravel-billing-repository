<?php

namespace ValentinMorice\LaravelBillingRepository\Deployer\Actions;

use ValentinMorice\LaravelBillingRepository\Contracts\ProviderFeatureExtractorInterface;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

class CreateProductComparisonObjectAction
{
    public function __construct(
        protected ProviderFeatureExtractorInterface $featureExtractor
    ) {}

    /**
     * Create a comparison object from a BillingProduct model
     * Converts model + relationships to match DTO structure for change detection
     */
    public function handle(BillingProduct $product): object
    {
        $providerName = $this->featureExtractor->getProviderName();
        $providerFeatures = $this->featureExtractor->extractProductFeatures($product);

        return (object) [
            'name' => $product->name,
            'description' => $product->description,
            'metadata' => $product->metadata,
            $providerName => $providerFeatures,
        ];
    }
}
