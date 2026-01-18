<?php

namespace ValentinMorice\LaravelBillingRepository\Importer\Pipeline\Persist;

use Illuminate\Support\Str;
use ValentinMorice\LaravelBillingRepository\Contracts\ProviderFeatureExtractorInterface;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Importer\ImportContext;
use ValentinMorice\LaravelBillingRepository\Importer\Actions\UpsertPriceAction;
use ValentinMorice\LaravelBillingRepository\Importer\Pipeline\Abstract\AbstractPersistStage;

class PersistPricesStage extends AbstractPersistStage
{
    /** @var array<int, array<string, int>> Track used keys per product: [productId => [key => count]] */
    private array $usedKeys = [];

    public function __construct(
        protected UpsertPriceAction $upsertPrice,
        protected ProviderFeatureExtractorInterface $featureExtractor,
    ) {}

    protected function persist(ImportContext $context): void
    {
        foreach ($context->providerPrices as $productProviderId => $prices) {
            $billingProduct = $context->getImportedProduct($productProviderId);

            if (! $billingProduct) {
                continue; // Product wasn't imported, skip prices
            }

            collect($prices)
                ->chunk(500)
                ->each(function ($chunk) use ($context, $billingProduct) {
                    foreach ($chunk as $index => $providerPrice) {
                        $key = $this->determineUniqueKey($billingProduct->id, $providerPrice, $index);

                        $result = $this->upsertPrice->handle(
                            productId: $billingProduct->id,
                            providerId: $providerPrice->id,
                            key: $key,
                            amount: $providerPrice->unit_amount ?? 0,
                            currency: $providerPrice->currency,
                            recurring: $providerPrice->recurring ? $this->cleanRecurringData($providerPrice->recurring) : null,
                            nickname: $providerPrice->nickname ?? null,
                            active: $providerPrice->active,
                            trialPeriodDays: $providerPrice->billing_scheme === 'tiered' ? null : ($providerPrice->trial_period_days ?? null),
                            metadata: $this->featureExtractor->extractMetadata($providerPrice),
                            providerFeatures: $this->featureExtractor->extractPriceFeaturesApi($providerPrice),
                        );

                        $context->recordPriceImport($providerPrice->id, $result);
                    }
                });
        }
    }

    protected function determineUniqueKey(int $productId, object $price, int $index): string
    {
        $baseKey = $this->determineBaseKey($price, $index);

        return $this->makeKeyUnique($productId, $baseKey);
    }

    protected function determineBaseKey(object $price, int $index): string
    {
        if ($price->nickname) {
            return Str::snake($price->nickname);
        }

        if ($price->recurring) {
            return $price->recurring->interval; // 'month', 'year', etc.
        }

        return $index === 0 ? 'default' : 'variant_'.$index;
    }

    protected function makeKeyUnique(int $productId, string $key): string
    {
        if (! isset($this->usedKeys[$productId])) {
            $this->usedKeys[$productId] = [];
        }

        if (! isset($this->usedKeys[$productId][$key])) {
            $this->usedKeys[$productId][$key] = 1;

            return $key;
        }

        $count = ++$this->usedKeys[$productId][$key];

        return $key.'_'.$count;
    }

    /**
     * Extract only relevant fields from Stripe recurring object
     */
    protected function cleanRecurringData(object $recurring): array
    {
        $clean = [
            'interval' => $recurring->interval,
        ];

        if (isset($recurring->interval_count) && $recurring->interval_count !== 1) {
            $clean['interval_count'] = $recurring->interval_count;
        }

        return $clean;
    }
}
