<?php

namespace ValentinMorice\LaravelBillingRepository\Importer\Actions;

use ValentinMorice\LaravelBillingRepository\Data\DTO\Importer\ImportedPrice;
use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;

class UpsertPriceAction
{
    /**
     * Upsert price to database (insert or update based on provider_id)
     */
    public function handle(
        int $productId,
        string $providerId,
        string $key,
        int $amount,
        string $currency,
        ?array $recurring,
        ?string $nickname,
        bool $active,
        ?int $trialPeriodDays = null,
        ?array $metadata = null,
        ?array $providerFeatures = null,
    ): ImportedPrice {
        $existing = BillingPrice::where('provider_id', $providerId)->first();

        if ($existing) {
            $existing->update([
                'product_id' => $productId,
                'amount' => $amount,
                'currency' => $currency,
                'recurring' => $recurring,
                'nickname' => $nickname,
                'metadata' => $metadata,
                'trial_period_days' => $trialPeriodDays,
                'active' => $active,
            ]);

            $this->persistProviderFeatures($existing, $providerFeatures);

            return new ImportedPrice($existing->fresh([config('billing.provider')]), false);
        }

        $price = BillingPrice::create([
            'product_id' => $productId,
            'provider_id' => $providerId,
            'key' => $key,
            'amount' => $amount,
            'currency' => $currency,
            'recurring' => $recurring,
            'nickname' => $nickname,
            'metadata' => $metadata,
            'trial_period_days' => $trialPeriodDays,
            'active' => $active,
        ]);

        $this->persistProviderFeatures($price, $providerFeatures);

        return new ImportedPrice($price->fresh([config('billing.provider')]), true);
    }

    private function persistProviderFeatures(BillingPrice $price, ?array $features): void
    {
        $providerName = config('billing.provider');

        if (empty($features)) {
            $price->{$providerName}()->delete();

            return;
        }

        if ($price->{$providerName}) {
            $price->{$providerName}->update($features);
        } else {
            $price->{$providerName}()->create($features);
        }
    }
}
