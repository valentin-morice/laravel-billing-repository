<?php

namespace ValentinMorice\LaravelBillingRepository\Importer\Actions;

use ValentinMorice\LaravelBillingRepository\Data\DTO\Importer\ImportedProduct;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

class UpsertProductAction
{
    /**
     * Upsert product to database (insert or update based on provider_id)
     */
    public function handle(
        string $providerId,
        string $key,
        string $name,
        ?string $description,
        bool $active,
        ?array $metadata = null,
        ?array $providerFeatures = null,
    ): ImportedProduct {
        $existing = BillingProduct::where('provider_id', $providerId)->first();

        if ($existing) {
            $existing->update([
                'name' => $name,
                'description' => $description,
                'metadata' => $metadata,
                'active' => $active,
            ]);

            $this->persistProviderFeatures($existing, $providerFeatures);

            return new ImportedProduct($existing->fresh([config('billing.provider')]), false);
        }

        $product = BillingProduct::create([
            'key' => $key,
            'provider_id' => $providerId,
            'name' => $name,
            'description' => $description,
            'metadata' => $metadata,
            'active' => $active,
        ]);

        $this->persistProviderFeatures($product, $providerFeatures);

        return new ImportedProduct($product->fresh([config('billing.provider')]), true);
    }

    private function persistProviderFeatures(BillingProduct $product, ?array $features): void
    {
        $providerName = config('billing.provider');

        if (empty($features)) {
            $product->{$providerName}()->delete();

            return;
        }

        if ($product->{$providerName}) {
            $product->{$providerName}->update($features);
        } else {
            $product->{$providerName}()->create($features);
        }
    }
}
