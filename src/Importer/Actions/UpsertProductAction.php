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
    ): ImportedProduct {
        $existing = BillingProduct::where('provider_id', $providerId)->first();

        if ($existing) {
            $existing->update([
                'key' => $key,
                'name' => $name,
                'description' => $description,
                'active' => $active,
            ]);

            return new ImportedProduct($existing->fresh(), false);
        }

        $product = BillingProduct::create([
            'key' => $key,
            'provider_id' => $providerId,
            'name' => $name,
            'description' => $description,
            'active' => $active,
        ]);

        return new ImportedProduct($product, true);
    }
}
