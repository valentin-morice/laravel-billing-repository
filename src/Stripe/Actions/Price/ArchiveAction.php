<?php

namespace ValentinMorice\LaravelBillingRepository\Stripe\Actions\Price;

use ValentinMorice\LaravelBillingRepository\Models\BillingPrice;
use ValentinMorice\LaravelBillingRepository\Stripe\Actions\Abstract\AbstractArchiveAction;

class ArchiveAction extends AbstractArchiveAction
{
    public function handle(BillingPrice $price): BillingPrice
    {
        $this->archiveInProvider($price->provider_id);
        $this->markAsInactive($price);

        return $price->fresh();
    }

    protected function archiveInProvider(string $providerId): void
    {
        $this->client->price()->archive($providerId);
    }
}
