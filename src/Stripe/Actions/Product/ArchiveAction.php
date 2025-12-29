<?php

namespace ValentinMorice\LaravelBillingRepository\Stripe\Actions\Product;

use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;
use ValentinMorice\LaravelBillingRepository\Stripe\Actions\Abstract\AbstractArchiveAction;

class ArchiveAction extends AbstractArchiveAction
{
    public function handle(BillingProduct $product): BillingProduct
    {
        $this->archiveInProvider($product->provider_id);
        $this->markAsInactive($product);

        return $product->fresh();
    }

    protected function archiveInProvider(string $providerId): void
    {
        $this->client->product()->archive($providerId);
    }
}
