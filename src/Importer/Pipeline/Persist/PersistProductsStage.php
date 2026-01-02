<?php

namespace ValentinMorice\LaravelBillingRepository\Importer\Pipeline\Persist;

use ValentinMorice\LaravelBillingRepository\Data\DTO\Importer\ImportContext;
use ValentinMorice\LaravelBillingRepository\Importer\Actions\GenerateProductKeyAction;
use ValentinMorice\LaravelBillingRepository\Importer\Actions\UpsertProductAction;
use ValentinMorice\LaravelBillingRepository\Importer\Pipeline\Abstract\AbstractPersistStage;
use ValentinMorice\LaravelBillingRepository\Models\BillingProduct;

class PersistProductsStage extends AbstractPersistStage
{
    public function __construct(
        protected UpsertProductAction $upsertProduct,
        protected GenerateProductKeyAction $generateKey,
    ) {}

    protected function persist(ImportContext $context): void
    {
        $context->providerProducts
            ->chunk(500)
            ->each(function ($chunk) use ($context) {
                foreach ($chunk as $providerProduct) {
                    $existing = BillingProduct::where('provider_id', $providerProduct->id)->first();
                    $key = $existing ? $existing->key : $this->generateKey->handle($providerProduct->name);

                    $result = $this->upsertProduct->handle(
                        providerId: $providerProduct->id,
                        key: $key,
                        name: $providerProduct->name,
                        description: $providerProduct->description ?? null,
                        active: $providerProduct->active,
                    );

                    $context->recordProductImport($providerProduct->id, $result);
                }
            });
    }
}
