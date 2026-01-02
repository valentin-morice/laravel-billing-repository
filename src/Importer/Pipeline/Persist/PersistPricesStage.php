<?php

namespace ValentinMorice\LaravelBillingRepository\Importer\Pipeline\Persist;

use Illuminate\Support\Str;
use ValentinMorice\LaravelBillingRepository\Data\DTO\Importer\ImportContext;
use ValentinMorice\LaravelBillingRepository\Importer\Actions\UpsertPriceAction;
use ValentinMorice\LaravelBillingRepository\Importer\Pipeline\Abstract\AbstractPersistStage;

class PersistPricesStage extends AbstractPersistStage
{
    public function __construct(
        protected UpsertPriceAction $upsertPrice,
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
                        $type = $this->determineType($providerPrice, $index);

                        $result = $this->upsertPrice->handle(
                            productId: $billingProduct->id,
                            providerId: $providerPrice->id,
                            type: $type,
                            amount: $providerPrice->unit_amount ?? 0,
                            currency: $providerPrice->currency,
                            recurring: $providerPrice->recurring ? $this->cleanRecurringData($providerPrice->recurring) : null,
                            nickname: $providerPrice->nickname ?? null,
                            active: $providerPrice->active,
                        );

                        $context->recordPriceImport($providerPrice->id, $result);
                    }
                });
        }
    }

    protected function determineType(object $price, int $index): string
    {
        if ($price->nickname) {
            return Str::snake($price->nickname);
        }

        if ($price->recurring) {
            return $price->recurring->interval; // 'month', 'year', etc.
        }

        return $index === 0 ? 'default' : 'variant_'.$index;
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
