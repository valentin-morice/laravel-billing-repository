<?php

namespace ValentinMorice\LaravelBillingRepository\Deployer\Actions;

use ValentinMorice\LaravelBillingRepository\Contracts\ProviderFeatureExtractorInterface;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;

class CreatePriceComparisonObjectAction
{
    public function __construct(
        protected ProviderFeatureExtractorInterface $featureExtractor
    ) {}

    /**
     * Create a comparison object from a BillingPrice model
     * Converts model + relationships to match DTO structure for change detection
     */
    public function handle(BillingPrice $price): object
    {
        $providerName = $this->featureExtractor->getProviderName();
        $providerFeatures = $this->featureExtractor->extractPriceFeatures($price);

        return (object) [
            'amount' => $price->amount,
            'currency' => $price->currency,
            'recurring' => $price->recurring,
            'nickname' => $price->nickname,
            'metadata' => $price->metadata,
            'trialPeriodDays' => $price->trial_period_days,
            $providerName => $providerFeatures,
        ];
    }
}
