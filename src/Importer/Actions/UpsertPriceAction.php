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
        string $type,
        int $amount,
        string $currency,
        ?array $recurring,
        ?string $nickname,
        bool $active,
    ): ImportedPrice {
        $existing = BillingPrice::where('provider_id', $providerId)->first();

        if ($existing) {
            $existing->update([
                'product_id' => $productId,
                'type' => $type,
                'amount' => $amount,
                'currency' => $currency,
                'recurring' => $recurring,
                'nickname' => $nickname,
                'active' => $active,
            ]);

            return new ImportedPrice($existing->fresh(), false);
        }

        $price = BillingPrice::create([
            'product_id' => $productId,
            'provider_id' => $providerId,
            'type' => $type,
            'amount' => $amount,
            'currency' => $currency,
            'recurring' => $recurring,
            'nickname' => $nickname,
            'active' => $active,
        ]);

        return new ImportedPrice($price, true);
    }
}
